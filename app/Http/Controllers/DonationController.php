<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Services\Payments\DonationCompletionService;
use App\Services\Payments\ShurjoPayGatewayService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class DonationController extends Controller
{
    /**
     * Both gateways' donor-facing return URL. Stripe's authoritative
     * confirmation is the webhook (below); this also verifies directly so
     * local/dev environments without a public webhook URL still complete
     * the donation - safe to run twice (spec §8: idempotent).
     */
    public function success(Donation $donation, StripeClient $stripe, DonationCompletionService $completion): View
    {
        if ($donation->gateway === Donation::GATEWAY_STRIPE && $donation->transaction_id) {
            try {
                /** @var Session $session */
                $session = $stripe->checkout->sessions->retrieve($donation->transaction_id);

                if ($session->payment_status === 'paid') {
                    $completion->complete($donation, ['stripe_session' => $session->toArray()]);
                }
            } catch (\Throwable $e) {
                Log::warning('Stripe session verification on return failed', ['donation_id' => $donation->id, 'error' => $e->getMessage()]);
            }
        }

        if ($donation->gateway === Donation::GATEWAY_SHURJOPAY && $donation->transaction_id) {
            try {
                $gateway = app(ShurjoPayGatewayService::class);
                $result = $gateway->verify($donation->transaction_id);

                if ($gateway->isSuccessful($result)) {
                    $completion->complete($donation, $result);
                }
            } catch (\Throwable $e) {
                Log::warning('ShurjoPay verification on return failed', ['donation_id' => $donation->id, 'error' => $e->getMessage()]);
            }
        }

        return view('donations.success', ['donation' => $donation->fresh()]);
    }

    public function cancel(Donation $donation): View
    {
        return view('donations.cancel', ['donation' => $donation]);
    }
}
