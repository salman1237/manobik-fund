<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Livewire\Donations\DonationForm;
use App\Models\Campaign;
use App\Models\Donation;
use App\Services\Payments\ShurjoPayGatewayService;
use App\Services\Payments\StripeGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Stripe\WebhookSignature;
use Tests\TestCase;

class Phase4DonationsTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeGateway(string $checkoutUrl = 'https://gateway.example.test/checkout/123'): PaymentGateway
    {
        return new class($checkoutUrl) implements PaymentGateway
        {
            public function __construct(protected string $url) {}

            public function createCheckout(Donation $donation, string $successUrl, string $cancelUrl): string
            {
                $donation->update(['transaction_id' => 'fake-txn-'.$donation->id]);

                return $this->url;
            }

            public function refund(Donation $donation): void
            {
                //
            }
        };
    }

    public function test_a_guest_can_donate_via_shurjopay_and_is_redirected_to_the_checkout_url(): void
    {
        $this->app->instance(ShurjoPayGatewayService::class, $this->fakeGateway('https://shurjopay.example.test/checkout/abc'));

        $campaign = Campaign::factory()->published()->create();

        Livewire::test(DonationForm::class, ['campaign' => $campaign])
            ->set('gateway', Donation::GATEWAY_SHURJOPAY)
            ->set('amount', 500)
            ->set('currency', 'BDT')
            ->set('donorName', 'Anonymous Donor')
            ->set('donorEmail', 'donor@example.com')
            ->call('donate')
            ->assertRedirect('https://shurjopay.example.test/checkout/abc');

        $this->assertDatabaseHas('donations', [
            'campaign_id' => $campaign->id,
            'donor_email' => 'donor@example.com',
            'amount' => 50000,
            'currency' => 'BDT',
            'gateway' => 'shurjopay',
            'status' => 'pending',
        ]);
    }

    public function test_a_guest_can_donate_via_stripe_and_is_redirected_to_the_checkout_url(): void
    {
        $this->app->instance(StripeGatewayService::class, $this->fakeGateway('https://checkout.stripe.test/session/xyz'));

        $campaign = Campaign::factory()->published()->create();

        Livewire::test(DonationForm::class, ['campaign' => $campaign])
            ->set('gateway', Donation::GATEWAY_STRIPE)
            ->set('amount', 25)
            ->set('currency', 'USD')
            ->set('donorName', 'International Donor')
            ->set('donorEmail', 'intl@example.com')
            ->call('donate')
            ->assertRedirect('https://checkout.stripe.test/session/xyz');

        $this->assertDatabaseHas('donations', [
            'campaign_id' => $campaign->id,
            'amount' => 2500,
            'currency' => 'USD',
            'gateway' => 'stripe',
        ]);
    }

    public function test_donation_amount_must_be_positive(): void
    {
        $campaign = Campaign::factory()->published()->create();

        Livewire::test(DonationForm::class, ['campaign' => $campaign])
            ->set('amount', 0)
            ->set('donorName', 'Someone')
            ->set('donorEmail', 'someone@example.com')
            ->call('donate')
            ->assertHasErrors(['amount']);
    }

    public function test_an_authenticated_donor_is_linked_to_their_donation(): void
    {
        $this->app->instance(ShurjoPayGatewayService::class, $this->fakeGateway());

        $user = \App\Models\User::factory()->create();
        $user->assignRole('user');
        $campaign = Campaign::factory()->published()->create();

        Livewire::actingAs($user)->test(DonationForm::class, ['campaign' => $campaign])
            ->set('amount', 100)
            ->call('donate');

        $this->assertDatabaseHas('donations', [
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_the_stripe_webhook_completes_a_pending_donation_and_increments_the_campaign(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
        Notification::fake();

        $campaign = Campaign::factory()->published()->create(['raised_amount' => 1000]);
        $donation = Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'gateway' => Donation::GATEWAY_STRIPE,
            'amount' => 5000,
            'status' => Donation::STATUS_PENDING,
            'transaction_id' => 'cs_test_123',
        ]);

        $payload = json_encode([
            'id' => 'evt_test',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'metadata' => ['donation_id' => (string) $donation->id],
                ],
            ],
        ]);

        $signature = WebhookSignature::generateSignatureHeader($payload, 'whsec_test_secret');

        $response = $this->call('POST', '/webhooks/stripe', [], [], [], [
            'HTTP_Stripe-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();

        $donation->refresh();
        $this->assertSame(Donation::STATUS_COMPLETED, $donation->status);
        $this->assertSame(6000, $campaign->fresh()->raised_amount);
    }

    public function test_the_stripe_webhook_rejects_an_invalid_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

        $response = $this->call('POST', '/webhooks/stripe', [], [], [], [
            'HTTP_Stripe-Signature' => 'invalid',
            'CONTENT_TYPE' => 'application/json',
        ], '{"type":"checkout.session.completed"}');

        $response->assertStatus(400);
    }

    public function test_the_stripe_webhook_is_idempotent_on_duplicate_delivery(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
        Notification::fake();

        $campaign = Campaign::factory()->published()->create(['raised_amount' => 0]);
        $donation = Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'gateway' => Donation::GATEWAY_STRIPE,
            'amount' => 5000,
            'status' => Donation::STATUS_PENDING,
            'transaction_id' => 'cs_test_dup',
        ]);

        $payload = json_encode([
            'id' => 'evt_test_dup',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_dup',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'metadata' => ['donation_id' => (string) $donation->id],
                ],
            ],
        ]);
        $signature = WebhookSignature::generateSignatureHeader($payload, 'whsec_test_secret');

        // Deliver the same webhook twice.
        for ($i = 0; $i < 2; $i++) {
            $this->call('POST', '/webhooks/stripe', [], [], [], [
                'HTTP_Stripe-Signature' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ], $payload)->assertOk();
        }

        $this->assertSame(5000, $campaign->fresh()->raised_amount);
    }

    public function test_the_shurjopay_return_page_completes_the_donation_on_successful_verification(): void
    {
        Http::fake([
            '*/get_token' => Http::response(['token' => 'tok_abc', 'execute_url' => 'https://sandbox.shurjopayment.com/api'], 200),
            '*/verification' => Http::response([[
                'sp_code' => 1000,
                'bank_trx_id' => 'BANK123',
            ]], 200),
        ]);
        Notification::fake();

        $campaign = Campaign::factory()->published()->create(['raised_amount' => 0]);
        $donation = Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'gateway' => Donation::GATEWAY_SHURJOPAY,
            'amount' => 20000,
            'status' => Donation::STATUS_PENDING,
            'transaction_id' => 'donation-order-1',
        ]);

        $response = $this->get(route('donations.success', $donation));

        $response->assertOk();
        $this->assertSame(Donation::STATUS_COMPLETED, $donation->fresh()->status);
        $this->assertSame(20000, $campaign->fresh()->raised_amount);
    }

    public function test_the_shurjopay_return_page_does_not_complete_the_donation_on_failed_verification(): void
    {
        Http::fake([
            '*/get_token' => Http::response(['token' => 'tok_abc', 'execute_url' => 'https://sandbox.shurjopayment.com/api'], 200),
            '*/verification' => Http::response([[
                'sp_code' => 1001,
            ]], 200),
        ]);

        $campaign = Campaign::factory()->published()->create(['raised_amount' => 0]);
        $donation = Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'gateway' => Donation::GATEWAY_SHURJOPAY,
            'status' => Donation::STATUS_PENDING,
            'transaction_id' => 'donation-order-2',
        ]);

        $response = $this->get(route('donations.success', $donation));

        $response->assertOk();
        $this->assertSame(Donation::STATUS_PENDING, $donation->fresh()->status);
        $this->assertSame(0, $campaign->fresh()->raised_amount);
    }
}
