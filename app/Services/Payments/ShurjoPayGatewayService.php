<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Donation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Local (BD) payments via ShurjoPay (BDT, bKash, Nagad, cards) - spec §2.
 * No official Laravel package exists, so this is the "thin HTTP service
 * class" the spec calls for.
 *
 * NOTE: field names below follow ShurjoPay's publicly documented v2 flow
 * (get_token -> secret-pay -> verification) as of this writing. Confirm
 * exact field/response names against the live sandbox once real merchant
 * credentials are available (spec's Client Decisions log: real key wiring
 * deferred to Phase 4 completion) - integration test payloads here are
 * illustrative, not captured from a real sandbox response.
 */
class ShurjoPayGatewayService implements PaymentGateway
{
    protected function baseUrl(): string
    {
        return rtrim(config('services.shurjopay.base_url'), '/');
    }

    protected function getToken(): array
    {
        return Cache::remember('shurjopay.token', now()->addMinutes(50), function () {
            $response = Http::asJson()->post($this->baseUrl().'/get_token', [
                'username' => config('services.shurjopay.username'),
                'password' => config('services.shurjopay.password'),
            ])->throw();

            return $response->json();
        });
    }

    public function createCheckout(Donation $donation, string $successUrl, string $cancelUrl): string
    {
        $token = $this->getToken();
        $executeUrl = $token['execute_url'] ?? $this->baseUrl();

        $response = Http::withToken($token['token'] ?? null)
            ->asJson()
            ->post($executeUrl.'/secret-pay', [
                'prefix' => 'MF',
                'token' => $token['token'] ?? null,
                'store_id' => $token['store_id'] ?? null,
                'amount' => number_format($donation->amount / 100, 2, '.', ''),
                'order_id' => 'donation-'.$donation->id,
                'currency' => $donation->currency,
                'customer_name' => $donation->donor_name,
                'customer_email' => $donation->donor_email,
                'customer_phone' => 'N/A',
                'customer_address' => 'N/A',
                'customer_city' => 'N/A',
                'customer_post_code' => 'N/A',
                'client_ip' => request()->ip(),
                'return_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ])->throw()->json();

        if (empty($response['checkout_url'])) {
            throw new RuntimeException('ShurjoPay did not return a checkout URL.');
        }

        $donation->update([
            'transaction_id' => $response['sp_order_id'] ?? ('donation-'.$donation->id),
            'gateway_meta' => $response,
        ]);

        return $response['checkout_url'];
    }

    /**
     * Server-side verification of a completed (or attempted) payment,
     * called from the return-URL controller rather than trusted from
     * client-supplied query params.
     */
    public function verify(string $orderId): array
    {
        $token = $this->getToken();
        $executeUrl = $token['execute_url'] ?? $this->baseUrl();

        $response = Http::withToken($token['token'] ?? null)
            ->asJson()
            ->post($executeUrl.'/verification', [
                'order_id' => $orderId,
            ])->throw()->json();

        // ShurjoPay's verification response is documented as an array with
        // one entry per attempt against that order_id.
        return is_array($response) && array_is_list($response) ? ($response[0] ?? []) : $response;
    }

    /**
     * ShurjoPay does document a refund endpoint, but its exact request
     * shape hasn't been verified against a live sandbox (same caveat as
     * createCheckout/verify above) - refusing to guess at fields for an
     * operation that moves real money. Wire this up once real merchant
     * credentials are available; until then, gateway-refund approvals on
     * ShurjoPay donations should be handled manually by Executive Admin.
     */
    public function refund(Donation $donation): void
    {
        throw new RuntimeException(
            'ShurjoPay refund integration is not yet implemented - exact API fields are unverified without live sandbox credentials. Process this refund manually for now.'
        );
    }

    public function isSuccessful(array $verificationResult): bool
    {
        return (int) ($verificationResult['sp_code'] ?? 0) === 1000;
    }
}
