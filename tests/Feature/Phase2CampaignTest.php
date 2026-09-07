<?php

namespace Tests\Feature;

use App\Livewire\Campaigns\CampaignWizard;
use App\Livewire\Campaigns\PostCampaignUpdate;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class Phase2CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_users_cannot_start_a_campaign(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->get(route('seeker.campaigns.create'));

        $response->assertForbidden();
    }

    public function test_the_create_and_edit_pages_render_over_http_for_an_authorized_seeker(): void
    {
        $seeker = User::factory()->create();
        $seeker->assignRole('user');

        $this->actingAs($seeker)->get(route('seeker.campaigns.create'))
            ->assertOk()
            ->assertSeeLivewire(CampaignWizard::class);

        $draft = Campaign::factory()->create(['seeker_id' => $seeker->id]);

        $this->actingAs($seeker)->get(route('seeker.campaigns.edit', $draft))
            ->assertOk()
            ->assertSeeLivewire(CampaignWizard::class);

        $this->actingAs($seeker)->get(route('seeker.campaigns.show', $draft))
            ->assertOk();
    }

    public function test_a_verified_donation_seeker_can_complete_all_four_wizard_steps(): void
    {
        Storage::fake('local');

        $seeker = User::factory()->create();
        $seeker->assignRole('user');

        $component = Livewire::actingAs($seeker)->test(CampaignWizard::class)
            ->set('category', Campaign::CATEGORY_TREATMENT)
            ->set('title', 'Help Rahim Fight Kidney Failure')
            ->set('description', str_repeat('This patient urgently needs dialysis support. ', 3))
            ->set('targetAmountTaka', 50000)
            ->set('deadline', now()->addMonths(2)->toDateString())
            ->call('saveStepOne')
            ->assertSet('step', 2);

        $campaign = Campaign::query()->where('seeker_id', $seeker->id)->firstOrFail();
        $this->assertSame(Campaign::STATUS_DRAFT, $campaign->status);
        $this->assertSame(5000000, $campaign->target_amount);

        $component->set('hospitalName', 'Dhaka Medical College Hospital')
            ->set('latitude', 23.7256)
            ->set('longitude', 90.3987)
            ->call('saveStepTwo')
            ->assertSet('step', 3);

        $this->assertSame('Dhaka Medical College Hospital', $campaign->fresh()->hospital_name);

        $component->set('medicalReport', UploadedFile::fake()->create('report.pdf', 200, 'application/pdf'))
            ->call('saveStepThree')
            ->assertSet('step', 4);

        $this->assertSame(1, $campaign->fresh()->documents()->count());
        $this->assertSame('medical_report', $campaign->fresh()->documents()->first()->type);

        $component->set('bankAccountName', 'Rahim Uddin')
            ->set('bankAccountNumber', '1234567890')
            ->set('bankName', 'City Bank')
            ->call('saveStepFourAndSubmit');

        $campaign->refresh();
        $this->assertSame(Campaign::STATUS_PENDING_VERIFICATION, $campaign->status);
        $this->assertSame('Rahim Uddin', $campaign->bank_account_details['account_name']);
        $this->assertSame('1234567890', $campaign->bank_account_details['account_number']);
    }

    public function test_bank_account_details_are_encrypted_at_rest(): void
    {
        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        $campaign = Campaign::factory()->create(['seeker_id' => $seeker->id]);

        Livewire::actingAs($seeker)->test(CampaignWizard::class, ['campaign' => $campaign])
            ->set('bankAccountName', 'Rahim Uddin')
            ->set('bankAccountNumber', '999888777')
            ->set('bankName', 'City Bank')
            ->call('saveStepFourAndSubmit');

        $raw = \DB::table('campaigns')->where('id', $campaign->id)->value('bank_account_details');

        $this->assertStringNotContainsString('999888777', $raw);
        $this->assertStringNotContainsString('Rahim Uddin', $raw);
    }

    public function test_a_seeker_cannot_edit_another_seekers_draft_campaign(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('user');
        $intruder = User::factory()->create();
        $intruder->assignRole('user');

        $campaign = Campaign::factory()->create(['seeker_id' => $owner->id]);

        $response = $this->actingAs($intruder)->get(route('seeker.campaigns.edit', $campaign));

        $response->assertForbidden();
    }

    public function test_a_seeker_cannot_edit_a_campaign_once_it_leaves_draft_status(): void
    {
        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        $campaign = Campaign::factory()->pendingVerification()->create(['seeker_id' => $seeker->id]);

        $response = $this->actingAs($seeker)->get(route('seeker.campaigns.edit', $campaign));

        $response->assertForbidden();
    }

    public function test_seeker_can_post_updates_only_once_campaign_is_published(): void
    {
        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        $draft = Campaign::factory()->create(['seeker_id' => $seeker->id]);

        Livewire::actingAs($seeker)->test(PostCampaignUpdate::class, ['campaign' => $draft])
            ->set('content', 'Patient is stable today.')
            ->call('post')
            ->assertForbidden();

        $this->assertSame(0, $draft->updates()->count());

        $published = Campaign::factory()->published()->create(['seeker_id' => $seeker->id]);

        Livewire::actingAs($seeker)->test(PostCampaignUpdate::class, ['campaign' => $published])
            ->set('content', 'Patient is stable today.')
            ->call('post');

        $this->assertSame(1, $published->updates()->count());
    }

    public function test_seeker_dashboard_only_lists_the_authenticated_users_own_campaigns(): void
    {
        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        $other = User::factory()->create();
        $other->assignRole('user');

        Campaign::factory()->create(['seeker_id' => $seeker->id, 'title' => 'My Own Campaign']);
        Campaign::factory()->create(['seeker_id' => $other->id, 'title' => 'Someone Elses Campaign']);

        $response = $this->actingAs($seeker)->get(route('seeker.campaigns.index'));

        $response->assertOk();
        $response->assertSee('My Own Campaign');
        $response->assertDontSee('Someone Elses Campaign');
    }
}
