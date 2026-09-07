<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Filament\Resources\RefundRequestResource\Pages\ListRefundRequests;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\RefundRequest;
use App\Models\User;
use App\Services\Payments\DonationCompletionService;
use App\Services\Payments\StripeGatewayService;
use App\Services\RefundRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase7RewardsAndRefundsTest extends TestCase
{
    use RefreshDatabase;

    protected function executiveAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('executive_admin');

        return $user;
    }

    // --- Reward points ---

    public function test_completing_a_donation_awards_the_default_percentage_as_points(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('user');
        $donation = Donation::factory()->create([
            'user_id' => $donor->id,
            'amount' => 100000, // 1000.00 in major unit
            'status' => Donation::STATUS_PENDING,
        ]);

        app(DonationCompletionService::class)->complete($donation);

        // default 1% of 1000 = 10 points
        $this->assertDatabaseHas('reward_points', [
            'donation_id' => $donation->id,
            'user_id' => $donor->id,
            'points' => 10,
        ]);
        $this->assertSame(10, $donor->humanityBadgePoints());
    }

    public function test_reward_percent_is_configurable_via_settings(): void
    {
        \App\Support\Facades\Settings::set('donation_reward_percent', 5, type: 'integer');

        $donor = User::factory()->create();
        $donor->assignRole('user');
        $donation = Donation::factory()->create([
            'user_id' => $donor->id,
            'amount' => 100000,
            'status' => Donation::STATUS_PENDING,
        ]);

        app(DonationCompletionService::class)->complete($donation);

        $this->assertSame(50, $donor->humanityBadgePoints());
    }

    public function test_guest_donations_do_not_award_reward_points(): void
    {
        $donation = Donation::factory()->create([
            'user_id' => null,
            'amount' => 100000,
            'status' => Donation::STATUS_PENDING,
        ]);

        app(DonationCompletionService::class)->complete($donation);

        $this->assertDatabaseMissing('reward_points', ['donation_id' => $donation->id]);
    }

    public function test_completing_a_donation_twice_does_not_double_award_points(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('user');
        $donation = Donation::factory()->create([
            'user_id' => $donor->id,
            'amount' => 100000,
            'status' => Donation::STATUS_PENDING,
        ]);

        $service = app(DonationCompletionService::class);
        $service->complete($donation);
        $service->complete($donation->fresh());

        $this->assertSame(1, \App\Models\RewardPoint::query()->where('donation_id', $donation->id)->count());
    }

    // --- Refund requests ---

    public function test_a_donor_can_request_a_refund_on_their_own_completed_donation(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('user');
        $donation = Donation::factory()->completed()->create(['user_id' => $donor->id]);

        $refundRequest = app(RefundRequestService::class)->request($donation, $donor, 'Made a mistake with the amount');

        $this->assertDatabaseHas('refund_requests', [
            'id' => $refundRequest->id,
            'donation_id' => $donation->id,
            'user_id' => $donor->id,
            'status' => RefundRequest::STATUS_PENDING,
        ]);
    }

    public function test_a_donor_cannot_request_a_refund_on_someone_elses_donation(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('user');
        $intruder = User::factory()->create();
        $intruder->assignRole('user');
        $donation = Donation::factory()->completed()->create(['user_id' => $owner->id]);

        $this->expectException(\InvalidArgumentException::class);

        app(RefundRequestService::class)->request($donation, $intruder, 'Not my donation');
    }

    public function test_a_pending_donation_is_not_refund_eligible(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('user');
        $donation = Donation::factory()->create(['user_id' => $donor->id, 'status' => Donation::STATUS_PENDING]);

        $this->assertFalse($donation->isRefundEligible());
    }

    public function test_a_donation_cannot_have_two_pending_refund_requests(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('user');
        $donation = Donation::factory()->completed()->create(['user_id' => $donor->id]);

        app(RefundRequestService::class)->request($donation, $donor, 'First request');

        $this->assertFalse($donation->fresh()->isRefundEligible());
        $this->expectException(\RuntimeException::class);
        app(RefundRequestService::class)->request($donation->fresh(), $donor, 'Second request');
    }

    public function test_approving_with_gateway_refund_calls_the_gateway_and_marks_the_donation_refunded(): void
    {
        $refundCalled = false;
        $this->app->instance(StripeGatewayService::class, new class($refundCalled) implements PaymentGateway
        {
            public function __construct(public mixed &$called) {}

            public function createCheckout(Donation $donation, string $successUrl, string $cancelUrl): string
            {
                return 'unused';
            }

            public function refund(Donation $donation): void
            {
                $this->called = true;
            }
        });

        $donor = User::factory()->create();
        $donor->assignRole('user');
        $campaign = Campaign::factory()->published()->create(['raised_amount' => 100000]);
        $donation = Donation::factory()->completed()->create([
            'user_id' => $donor->id,
            'campaign_id' => $campaign->id,
            'gateway' => Donation::GATEWAY_STRIPE,
            'amount' => 30000,
        ]);
        $refundRequest = RefundRequest::factory()->create(['donation_id' => $donation->id, 'user_id' => $donor->id]);

        app(RefundRequestService::class)->approveWithGatewayRefund($refundRequest, $this->executiveAdmin());

        $this->assertSame(Donation::STATUS_REFUNDED, $donation->fresh()->status);
        $this->assertSame(70000, $campaign->fresh()->raised_amount);
        $this->assertSame(RefundRequest::STATUS_APPROVED, $refundRequest->fresh()->status);
    }

    public function test_approving_with_credit_redirect_moves_the_raised_amount_between_campaigns(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('user');
        $originalCampaign = Campaign::factory()->published()->create(['raised_amount' => 100000]);
        $redirectCampaign = Campaign::factory()->published()->create(['raised_amount' => 20000]);
        $donation = Donation::factory()->completed()->create([
            'user_id' => $donor->id,
            'campaign_id' => $originalCampaign->id,
            'amount' => 30000,
        ]);
        $refundRequest = RefundRequest::factory()->create(['donation_id' => $donation->id, 'user_id' => $donor->id]);

        app(RefundRequestService::class)->approveWithCreditRedirect($refundRequest, $this->executiveAdmin(), $redirectCampaign);

        $this->assertSame(70000, $originalCampaign->fresh()->raised_amount);
        $this->assertSame(50000, $redirectCampaign->fresh()->raised_amount);
        $this->assertSame($redirectCampaign->id, $donation->fresh()->campaign_id);
        $this->assertSame(RefundRequest::RESOLUTION_CREDIT_REDIRECT, $refundRequest->fresh()->resolution_type);
    }

    public function test_rejecting_a_refund_request_records_the_reason(): void
    {
        $refundRequest = RefundRequest::factory()->create();

        app(RefundRequestService::class)->reject($refundRequest, $this->executiveAdmin(), 'Outside refund window');

        $this->assertSame(RefundRequest::STATUS_REJECTED, $refundRequest->fresh()->status);
        $this->assertSame('Outside refund window', $refundRequest->fresh()->rejection_reason);
    }

    public function test_only_executive_admin_and_super_admin_can_resolve_refund_requests(): void
    {
        $refundRequest = RefundRequest::factory()->create();
        $verificationAdmin = User::factory()->create();
        $verificationAdmin->assignRole('verification_admin');

        $this->assertFalse($verificationAdmin->can('resolve', $refundRequest));
        $this->assertTrue($this->executiveAdmin()->can('resolve', $refundRequest));
    }

    public function test_the_my_donations_page_shows_points_and_donation_history(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('user');
        $campaign = Campaign::factory()->published()->create(['title' => 'Visible Donation Target']);
        $donation = Donation::factory()->completed()->create([
            'user_id' => $donor->id,
            'campaign_id' => $campaign->id,
            'amount' => 100000,
        ]);
        app(DonationCompletionService::class)->complete(Donation::factory()->create([
            'user_id' => $donor->id,
            'amount' => 100000,
            'status' => Donation::STATUS_PENDING,
        ]));

        $response = $this->actingAs($donor)->get(route('donations.index'));

        $response->assertOk();
        $response->assertSee('Visible Donation Target');
        $response->assertSee('10 pts');
        $response->assertSee('Request Refund');
    }

    public function test_the_profile_page_shows_humanity_badge_points(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('user');
        app(DonationCompletionService::class)->complete(Donation::factory()->create([
            'user_id' => $donor->id,
            'amount' => 500000,
            'status' => Donation::STATUS_PENDING,
        ]));

        $response = $this->actingAs($donor)->get(route('profile'));

        $response->assertOk();
        $response->assertSee('Humanity Badges');
        $response->assertSee('50 pts');
    }

    public function test_refund_actions_are_hidden_once_a_request_is_already_resolved(): void
    {
        $admin = $this->executiveAdmin();
        $pending = RefundRequest::factory()->create();
        $approved = RefundRequest::factory()->create(['status' => RefundRequest::STATUS_APPROVED]);

        Livewire::actingAs($admin)->test(ListRefundRequests::class)
            ->assertTableActionVisible('reject', $pending)
            ->assertTableActionHidden('reject', $approved);
    }
}
