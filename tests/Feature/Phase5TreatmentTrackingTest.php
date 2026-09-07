<?php

namespace Tests\Feature;

use App\Filament\Resources\TreatmentParameterResource\Pages\ListTreatmentParameters;
use App\Livewire\Campaigns\SubmitTreatmentParameter;
use App\Models\Campaign;
use App\Models\FundUtilization;
use App\Models\TreatmentParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase5TreatmentTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_seeker_can_submit_a_treatment_parameter_on_their_own_published_campaign(): void
    {
        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        // Pinned to treatment: needsMedicalTracking() (Phase 10) blocks
        // parameter submission for education campaigns, and the factory's
        // category is otherwise random - this test is specifically about
        // the submission path, so it shouldn't be flaky based on category.
        $campaign = Campaign::factory()->published()->create([
            'seeker_id' => $seeker->id,
            'category' => Campaign::CATEGORY_TREATMENT,
        ]);

        Livewire::actingAs($seeker)->test(SubmitTreatmentParameter::class, ['campaign' => $campaign])
            ->set('parameterType', TreatmentParameter::TYPE_PAIN_SCALE)
            ->set('label', 'Daily Pain Level')
            ->set('value', '4')
            ->set('recordedAt', now()->toDateString())
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('treatment_parameters', [
            'campaign_id' => $campaign->id,
            'label' => 'Daily Pain Level',
            'value' => '4',
            'is_verified' => false,
        ]);
    }

    public function test_a_seeker_cannot_submit_parameters_before_the_campaign_is_published(): void
    {
        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        $draft = Campaign::factory()->create(['seeker_id' => $seeker->id, 'status' => Campaign::STATUS_DRAFT]);

        Livewire::actingAs($seeker)->test(SubmitTreatmentParameter::class, ['campaign' => $draft])
            ->set('label', 'Too Early')
            ->set('value', '1')
            ->call('submit')
            ->assertForbidden();
    }

    public function test_a_non_owner_cannot_submit_parameters_for_someone_elses_campaign(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('user');
        $intruder = User::factory()->create();
        $intruder->assignRole('user');
        $campaign = Campaign::factory()->published()->create([
            'seeker_id' => $owner->id,
            'category' => Campaign::CATEGORY_TREATMENT,
        ]);

        Livewire::actingAs($intruder)->test(SubmitTreatmentParameter::class, ['campaign' => $campaign])
            ->set('label', 'Not Mine')
            ->set('value', '1')
            ->call('submit')
            ->assertForbidden();
    }

    public function test_newly_submitted_parameters_are_not_verified_and_do_not_appear_on_the_public_page(): void
    {
        $campaign = Campaign::factory()->published()->create();
        TreatmentParameter::factory()->create([
            'campaign_id' => $campaign->id,
            'label' => 'Unverified Secret Value',
            'is_verified' => false,
        ]);
        TreatmentParameter::factory()->verified()->create([
            'campaign_id' => $campaign->id,
            'label' => 'Verified Public Value',
        ]);

        $response = $this->get(route('campaigns.show', $campaign));

        $response->assertOk();
        $response->assertDontSee('Unverified Secret Value');
        $response->assertSee('Verified Public Value');
    }

    public function test_public_chart_data_groups_vitals_milestones_timeline_and_fund_utilization(): void
    {
        $campaign = Campaign::factory()->published()->create();

        TreatmentParameter::factory()->verified()->create([
            'campaign_id' => $campaign->id,
            'parameter_type' => TreatmentParameter::TYPE_WBC_COUNT,
            'value' => '5000',
            'recorded_at' => now()->subDays(2),
        ]);
        TreatmentParameter::factory()->verified()->create([
            'campaign_id' => $campaign->id,
            'parameter_type' => TreatmentParameter::TYPE_WBC_COUNT,
            'value' => '6200',
            'recorded_at' => now()->subDay(),
        ]);
        TreatmentParameter::factory()->verified()->create([
            'campaign_id' => $campaign->id,
            'parameter_type' => TreatmentParameter::TYPE_MILESTONE,
            'label' => 'Chemo Cycle 2 of 6',
            'value' => 'In Progress',
        ]);
        TreatmentParameter::factory()->verified()->create([
            'campaign_id' => $campaign->id,
            'parameter_type' => TreatmentParameter::TYPE_HOSPITAL_DAYS,
            'label' => 'Days Admitted',
            'value' => '12',
            'unit' => 'days',
        ]);
        FundUtilization::factory()->create(['campaign_id' => $campaign->id, 'category' => 'surgery', 'amount' => 10000]);
        FundUtilization::factory()->create(['campaign_id' => $campaign->id, 'category' => 'surgery', 'amount' => 5000]);
        FundUtilization::factory()->create(['campaign_id' => $campaign->id, 'category' => 'medication', 'amount' => 2000]);

        $data = $campaign->publicChartData();

        $this->assertArrayHasKey('wbc_count', $data['vitals']);
        $this->assertCount(2, $data['vitals']['wbc_count']['points']);
        $this->assertSame(5000.0, $data['vitals']['wbc_count']['points'][0]['y']);
        $this->assertSame(6200.0, $data['vitals']['wbc_count']['points'][1]['y']);

        $this->assertCount(1, $data['milestones']);
        $this->assertSame('Chemo Cycle 2 of 6', $data['milestones'][0]['label']);

        $this->assertSame('12', $data['timeline']['value']);

        $this->assertEqualsCanonicalizing([
            ['category' => 'surgery', 'amount' => 15000],
            ['category' => 'medication', 'amount' => 2000],
        ], $data['fundUtilization']);
    }

    public function test_only_verification_staff_can_verify_a_parameter_and_it_becomes_permanently_public(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('verification_admin');
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');

        $parameter = TreatmentParameter::factory()->create(['is_verified' => false]);

        Livewire::actingAs($volunteer)->test(ListTreatmentParameters::class)
            ->assertTableActionHidden('verify', $parameter);

        Livewire::actingAs($admin)->test(ListTreatmentParameters::class)
            ->assertTableActionVisible('verify', $parameter)
            ->callTableAction('verify', $parameter);

        $parameter->refresh();
        $this->assertTrue($parameter->is_verified);
        $this->assertSame($admin->id, $parameter->verified_by);
    }
}
