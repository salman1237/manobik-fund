<?php

namespace Tests\Feature;

use App\Filament\Resources\CampaignResource\Pages\CreateCampaign;
use App\Livewire\Campaigns\CampaignWizard;
use App\Livewire\Campaigns\SubmitTreatmentParameter;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase10ModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_executive_admin_can_create_an_emergency_campaign_directly_and_it_is_published_immediately(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('executive_admin');

        Livewire::actingAs($admin)->test(CreateCampaign::class)
            ->set('data.category', Campaign::CATEGORY_EMERGENCY)
            ->set('data.title', 'Flood Relief for Sylhet')
            ->set('data.description', 'Emergency flood relief operation for affected families.')
            ->set('data.hospital_name', 'Sylhet Relief Camp')
            ->set('data.target_amount', 500000)
            ->set('data.deadline', now()->addMonth()->toDateString())
            ->call('create')
            ->assertHasNoFormErrors();

        $campaign = Campaign::query()->where('title', 'Flood Relief for Sylhet')->firstOrFail();
        $this->assertSame(Campaign::STATUS_PUBLISHED, $campaign->status);
        $this->assertSame($admin->id, $campaign->seeker_id);
        $this->assertSame(50000000, $campaign->target_amount);
        $this->assertNotNull($campaign->published_at);
    }

    public function test_a_verification_admin_cannot_create_campaigns_directly(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('verification_admin');

        $this->assertFalse($admin->can('createDirectly', Campaign::class));
    }

    public function test_a_plain_seeker_cannot_create_campaigns_directly_via_filament(): void
    {
        $seeker = User::factory()->create();
        $seeker->assignRole('user');

        $response = $this->actingAs($seeker)->get('/control/campaigns/create');

        $response->assertForbidden();
    }

    public function test_treatment_category_cannot_be_created_through_the_direct_admin_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('executive_admin');

        Livewire::actingAs($admin)->test(CreateCampaign::class)
            ->set('data.category', Campaign::CATEGORY_TREATMENT)
            ->set('data.title', 'Sneaking In A Treatment Campaign')
            ->set('data.description', 'This should not be allowed through this form.')
            ->set('data.target_amount', 10000)
            ->set('data.deadline', now()->addMonth()->toDateString())
            ->call('create');

        $this->assertDatabaseMissing('campaigns', ['title' => 'Sneaking In A Treatment Campaign']);
    }

    public function test_the_seeker_wizard_skips_the_hospital_step_for_education_campaigns(): void
    {
        $seeker = User::factory()->create();
        $seeker->assignRole('user');

        Livewire::actingAs($seeker)->test(CampaignWizard::class)
            ->set('category', Campaign::CATEGORY_EDUCATION)
            ->set('title', 'Nursing Training Program for Rural Youth')
            ->set('description', str_repeat('Training the next generation of nurses. ', 3))
            ->set('targetAmountTaka', 20000)
            ->set('deadline', now()->addMonths(2)->toDateString())
            ->call('saveStepOne')
            ->assertSet('step', 3);
    }

    public function test_the_seeker_wizard_does_not_skip_the_hospital_step_for_treatment_campaigns(): void
    {
        $seeker = User::factory()->create();
        $seeker->assignRole('user');

        Livewire::actingAs($seeker)->test(CampaignWizard::class)
            ->set('category', Campaign::CATEGORY_TREATMENT)
            ->set('title', 'Help Rahim Fight Kidney Failure')
            ->set('description', str_repeat('This patient urgently needs dialysis support. ', 3))
            ->set('targetAmountTaka', 20000)
            ->set('deadline', now()->addMonths(2)->toDateString())
            ->call('saveStepOne')
            ->assertSet('step', 2);
    }

    public function test_education_campaigns_cannot_receive_treatment_parameter_submissions(): void
    {
        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        $campaign = Campaign::factory()->published()->create([
            'seeker_id' => $seeker->id,
            'category' => Campaign::CATEGORY_EDUCATION,
        ]);

        Livewire::actingAs($seeker)->test(SubmitTreatmentParameter::class, ['campaign' => $campaign])
            ->set('label', 'Not applicable')
            ->set('value', '1')
            ->call('submit')
            ->assertForbidden();

        $this->assertSame(0, $campaign->treatmentParameters()->count());
    }

    public function test_treatment_campaigns_can_still_receive_treatment_parameter_submissions(): void
    {
        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        $campaign = Campaign::factory()->published()->create([
            'seeker_id' => $seeker->id,
            'category' => Campaign::CATEGORY_TREATMENT,
        ]);

        Livewire::actingAs($seeker)->test(SubmitTreatmentParameter::class, ['campaign' => $campaign])
            ->set('label', 'Pain level today')
            ->set('value', '3')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, $campaign->treatmentParameters()->count());
    }

    public function test_the_education_campaign_public_page_shows_no_treatment_tracking_charts(): void
    {
        $campaign = Campaign::factory()->published()->create(['category' => Campaign::CATEGORY_EDUCATION]);

        $response = $this->get(route('campaigns.show', $campaign));

        $response->assertOk();
        $response->assertDontSee('Treatment Milestones');
    }
}
