<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Services\Payments\DonationCompletionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, DonationCompletionService $completion): Response
    {
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $secret,
            );
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            return response('Invalid payload or signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $donationId = $session->metadata->donation_id ?? null;

            $donation = $donationId ? Donation::query()->find($donationId) : null;

            if ($donation && $session->payment_status === 'paid') {
                $completion->complete($donation, ['stripe_session' => $session->toArray()]);
            }
        }

        return response('OK', 200);
    }
}
