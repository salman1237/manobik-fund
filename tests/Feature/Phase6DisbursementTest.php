<?php

namespace Tests\Feature;

use App\Exceptions\InvalidCampaignTransition;
use App\Filament\Resources\CampaignResource\Pages\ListCampaigns;
use App\Models\Campaign;
use App\Models\User;
use App\Notifications\FundsDisbursedNotification;
use App\Services\CampaignDisbursementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class Phase6DisbursementTest extends TestCase
{
    use RefreshDatabase;

    protected function executiveAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('executive_admin');

        return $user;
    }

    public function test_disbursement_records_a_transfer_and_marks_the_campaign_funded(): void
    {
        Notification::fake();

        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        $campaign = Campaign::factory()->published()->create(['seeker_id' => $seeker->id]);
        $admin = $this->executiveAdmin();

        $disbursement = app(CampaignDisbursementService::class)->disburse(
            $campaign,
            $admin,
            50000,
            'deposit-slips/slip.pdf',
            Campaign::STATUS_FUNDED,
        );

        $this->assertDatabaseHas('disbursements', [
            'id' => $disbursement->id,
            'campaign_id' => $campaign->id,
            'amount' => 50000,
            'disbursed_by' => $admin->id,
        ]);
        $this->assertSame(Campaign::STATUS_FUNDED, $campaign->fresh()->status);
        Notification::assertSentTo($seeker, FundsDisbursedNotification::class);
    }

    public function test_disbursement_can_log_fund_utilization_entries(): void
    {
        Notification::fake();
        $campaign = Campaign::factory()->published()->create();

        app(CampaignDisbursementService::class)->disburse(
            $campaign,
            $this->executiveAdmin(),
            100000,
            'deposit-slips/slip.pdf',
            Campaign::STATUS_COMPLETED,
            [
                ['category' => 'surgery', 'amount' => '700.00', 'description' => 'Operation cost'],
                ['category' => 'medication', 'amount' => '300.00'],
            ],
        );

        $this->assertDatabaseHas('fund_utilizations', [
            'campaign_id' => $campaign->id,
            'category' => 'surgery',
            'amount' => 70000,
        ]);
        $this->assertDatabaseHas('fund_utilizations', [
            'campaign_id' => $campaign->id,
            'category' => 'medication',
            'amount' => 30000,
        ]);
        $this->assertSame(Campaign::STATUS_COMPLETED, $campaign->fresh()->status);
    }

    public function test_disbursement_cannot_happen_outside_published_or_funded_status(): void
    {
        $campaign = Campaign::factory()->create(['status' => Campaign::STATUS_EXECUTIVE_REVIEW]);

        $this->expectException(InvalidCampaignTransition::class);

        app(CampaignDisbursementService::class)->disburse(
            $campaign,
            $this->executiveAdmin(),
            10000,
            'deposit-slips/slip.pdf',
            Campaign::STATUS_FUNDED,
        );
    }

    public function test_disbursement_rejects_an_invalid_target_status(): void
    {
        $campaign = Campaign::factory()->published()->create();

        $this->expectException(InvalidCampaignTransition::class);

        app(CampaignDisbursementService::class)->disburse(
            $campaign,
            $this->executiveAdmin(),
            10000,
            'deposit-slips/slip.pdf',
            Campaign::STATUS_REJECTED,
        );
    }

    public function test_only_executive_admin_and_super_admin_may_disburse(): void
    {
        $campaign = Campaign::factory()->published()->create();

        $verificationAdmin = User::factory()->create();
        $verificationAdmin->assignRole('verification_admin');

        $this->assertFalse($verificationAdmin->can('disburse', $campaign));
        $this->assertTrue($this->executiveAdmin()->can('disburse', $campaign));
    }

    public function test_the_disburse_action_is_only_visible_on_published_or_funded_campaigns(): void
    {
        $admin = $this->executiveAdmin();
        $published = Campaign::factory()->published()->create();
        $draft = Campaign::factory()->create(['status' => Campaign::STATUS_DRAFT]);

        Livewire::actingAs($admin)->test(ListCampaigns::class)
            ->assertTableActionVisible('disburse', $published)
            ->assertTableActionHidden('disburse', $draft);
    }

    public function test_the_disburse_action_uploads_a_deposit_slip_and_records_the_disbursement(): void
    {
        Storage::fake('public');
        Notification::fake();

        $admin = $this->executiveAdmin();
        $campaign = Campaign::factory()->published()->create();
        $slip = UploadedFile::fake()->create('slip.pdf', 100, 'application/pdf');

        Livewire::actingAs($admin)->test(ListCampaigns::class)
            ->callTableAction('disburse', $campaign, data: [
                'amount' => 500,
                'new_status' => Campaign::STATUS_FUNDED,
                'deposit_slip' => [$slip],
            ]);

        $campaign->refresh();
        $this->assertSame(Campaign::STATUS_FUNDED, $campaign->status);
        $this->assertSame(1, $campaign->disbursements()->count());
    }

    public function test_the_public_campaign_page_shows_the_transparency_section_once_disbursed(): void
    {
        Notification::fake();
        $campaign = Campaign::factory()->published()->create();

        app(CampaignDisbursementService::class)->disburse(
            $campaign,
            $this->executiveAdmin(),
            25000,
            'deposit-slips/public-slip.pdf',
            Campaign::STATUS_FUNDED,
        );

        $response = $this->get(route('campaigns.show', $campaign));

        $response->assertOk();
        $response->assertSee('Transparency: Fund Disbursement');
        $response->assertSee('250.00 BDT disbursed');
        $response->assertSee('View Deposit Slip');
    }
}
