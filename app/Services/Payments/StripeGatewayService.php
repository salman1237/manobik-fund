<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Donation;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

/**
 * International payments (USD/EUR/GBP) via Stripe Checkout, per spec §2.
 */
class StripeGatewayService implements PaymentGateway
{
    public function __construct(protected StripeClient $client) {}

    public function createCheckout(Donation $donation, string $successUrl, string $cancelUrl): string
    {
        /** @var Session $session */
        $session = $this->client->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $donation->donor_email,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $donation->id,
            'metadata' => [
                'donation_id' => $donation->id,
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($donation->currency),
                    'unit_amount' => $donation->amount,
                    'product_data' => [
                        'name' => $donation->campaign?->title ?? 'Donation to Manobik Fund',
                    ],
                ],
            ]],
        ]);

        $donation->update([
            'transaction_id' => $session->id,
            'gateway_meta' => ['checkout_session_id' => $session->id],
        ]);

        return $session->url;
    }
}
