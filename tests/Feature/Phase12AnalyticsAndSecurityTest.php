<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Filament\Widgets\DisbursementHistoryChart;
use App\Filament\Widgets\DonationsByCategoryChart;
use App\Filament\Widgets\DonationsByGatewayChart;
use App\Filament\Widgets\FinancialOverview;
use App\Livewire\Blood\RequestForm;
use App\Livewire\Donations\DonationForm;
use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\Donation;
use App\Models\User;
use App\Notifications\VolunteerAssignedNotification;
use App\Services\Payments\ShurjoPayGatewayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class Phase12AnalyticsAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeGateway(): PaymentGateway
    {
        return new class implements PaymentGateway
        {
            public function createCheckout(Donation $donation, string $successUrl, string $cancelUrl): string
            {
                $donation->update(['transaction_id' => 'fake-'.$donation->id]);

                return 'https://gateway.example.test/checkout';
            }

            public function refund(Donation $donation): void {}
        };
    }

    // --- Rate limiting ---

    public function test_the_donation_endpoint_is_rate_limited_per_ip(): void
    {
        RateLimiter::clear('donate:127.0.0.1');
        $this->app->instance(ShurjoPayGatewayService::class, $this->fakeGateway());

        $campaign = Campaign::factory()->published()->create();

        for ($i = 0; $i < 5; $i++) {
            Livewire::withQueryParams([])->test(DonationForm::class, ['campaign' => $campaign])
                ->set('amount', 100)
                ->set('donorName', 'Donor '.$i)
                ->set('donorEmail', "donor{$i}@example.com")
                ->call('donate')
                ->assertHasNoErrors();
        }

        $this->assertSame(5, Donation::query()->count());

        Livewire::test(DonationForm::class, ['campaign' => $campaign])
            ->set('amount', 100)
            ->set('donorName', 'One Too Many')
            ->set('donorEmail', 'toomany@example.com')
            ->call('donate')
            ->assertHasErrors(['amount']);

        $this->assertSame(5, Donation::query()->count());
    }

    public function test_the_blood_request_endpoint_is_rate_limited_per_ip(): void
    {
        RateLimiter::clear('blood-request:127.0.0.1');

        for ($i = 0; $i < 3; $i++) {
            Livewire::test(RequestForm::class)
                ->set('requesterName', 'Requester '.$i)
                ->set('requesterPhone', '0170000000'.$i)
                ->set('bloodGroup', 'O+')
                ->call('submit')
                ->assertHasNoErrors();
        }

        $this->assertSame(3, \App\Models\BloodRequest::query()->count());

        Livewire::test(RequestForm::class)
            ->set('requesterName', 'One Too Many')
            ->set('requesterPhone', '01700000009')
            ->set('bloodGroup', 'O+')
            ->call('submit')
            ->assertHasErrors(['requesterPhone']);

        $this->assertSame(3, \App\Models\BloodRequest::query()->count());
    }

    // --- Notifications are queued ---

    public function test_notifications_implement_should_queue_for_production_readiness(): void
    {
        $classes = [
            \App\Notifications\CampaignForwardedNotification::class,
            \App\Notifications\CampaignPublishedNotification::class,
            \App\Notifications\CampaignRejectedNotification::class,
            \App\Notifications\CriticalBloodRequestNotification::class,
            \App\Notifications\DonationReceiptNotification::class,
            \App\Notifications\DonationReceivedNotification::class,
            \App\Notifications\FundsDisbursedNotification::class,
            \App\Notifications\RefundRequestResolvedNotification::class,
            VolunteerAssignedNotification::class,
        ];

        foreach ($classes as $class) {
            $this->assertContains(ShouldQueue::class, class_implements($class), "{$class} should implement ShouldQueue.");
        }
    }

    // --- Analytics widgets ---

    public function test_only_super_admin_can_view_the_financial_widgets(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $executiveAdmin = User::factory()->create();
        $executiveAdmin->assignRole('executive_admin');

        $this->actingAs($superAdmin);
        $this->assertTrue(FinancialOverview::canView());
        $this->assertTrue(DonationsByGatewayChart::canView());
        $this->assertTrue(DonationsByCategoryChart::canView());
        $this->assertTrue(DisbursementHistoryChart::canView());

        $this->actingAs($executiveAdmin);
        $this->assertFalse(FinancialOverview::canView());
        $this->assertFalse(DonationsByGatewayChart::canView());
    }

    public function test_the_financial_overview_widget_shows_live_totals(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        Donation::factory()->completed()->create(['amount' => 100000, 'gateway' => Donation::GATEWAY_SHURJOPAY]);
        Donation::factory()->completed()->create(['amount' => 50000, 'gateway' => Donation::GATEWAY_STRIPE]);
        Donation::factory()->create(['amount' => 999999, 'status' => Donation::STATUS_PENDING]);
        Disbursement::factory()->create(['amount' => 75000]);

        Livewire::actingAs($superAdmin)->test(FinancialOverview::class)
            ->assertSee('1,500.00') // total raised: (100000+50000)/100
            ->assertSee('750.00')  // total disbursed
            ->assertSee('2');       // completed donation count
    }

    public function test_the_gateway_chart_groups_completed_donations_by_gateway(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        Donation::factory()->completed()->create(['amount' => 200000, 'gateway' => Donation::GATEWAY_STRIPE]);
        Donation::factory()->completed()->create(['amount' => 100000, 'gateway' => Donation::GATEWAY_SHURJOPAY]);

        $widget = new DonationsByGatewayChart();
        $this->actingAs($superAdmin);
        $data = (fn () => $this->getData())->call($widget);

        $this->assertSame(['Shurjopay', 'Stripe'], $data['labels']);
        $this->assertEquals([1000, 2000], $data['datasets'][0]['data']);
    }

    public function test_the_category_chart_groups_completed_donations_by_campaign_category(): void
    {
        $treatment = Campaign::factory()->published()->create(['category' => Campaign::CATEGORY_TREATMENT]);
        $education = Campaign::factory()->published()->create(['category' => Campaign::CATEGORY_EDUCATION]);

        Donation::factory()->completed()->create(['campaign_id' => $treatment->id, 'amount' => 300000]);
        Donation::factory()->completed()->create(['campaign_id' => $education->id, 'amount' => 100000]);

        $widget = new DonationsByCategoryChart();
        $data = (fn () => $this->getData())->call($widget);

        $treatmentIndex = array_search('Treatment', $data['labels']);
        $educationIndex = array_search('Education', $data['labels']);

        $this->assertEquals(3000, $data['datasets'][0]['data'][$treatmentIndex]);
        $this->assertEquals(1000, $data['datasets'][0]['data'][$educationIndex]);
    }

    public function test_the_admin_dashboard_route_is_reachable_for_super_admin(): void
    {
        // Filament's dashboard widgets lazy-load via a follow-up Livewire
        // request rather than being present in the initial HTML, so widget
        // content itself is covered directly via Livewire::test() above -
        // this just confirms the page (and therefore widget discovery) at
        // least boots without error for the role that should see it.
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)->get('/control')->assertOk();
    }

    public function test_the_disbursement_history_chart_groups_by_month(): void
    {
        Disbursement::factory()->create(['amount' => 50000, 'disbursed_at' => now()]);
        Disbursement::factory()->create(['amount' => 25000, 'disbursed_at' => now()->subMonths(2)]);

        $widget = new DisbursementHistoryChart();
        $data = (fn () => $this->getData())->call($widget);

        $this->assertEquals(500, end($data['datasets'][0]['data']));
        $this->assertSame(6, count($data['labels']));
    }
}
