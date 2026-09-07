<?php

namespace Tests\Feature;

use App\Filament\Resources\CampaignResource\Pages\ListCampaigns;
use App\Filament\Resources\FieldVisitReportResource\Pages\ListFieldVisitReports;
use App\Models\Campaign;
use App\Models\FieldVisitReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase3FilamentUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_plain_authenticated_user_cannot_reach_the_control_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->get('/control/campaigns');

        $response->assertForbidden();
    }

    public function test_verification_admin_can_view_the_campaign_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('verification_admin');
        Campaign::factory()->pendingVerification()->create();

        $response = $this->actingAs($admin)->get('/control/campaigns');

        $response->assertOk();
    }

    public function test_volunteers_only_see_campaigns_assigned_to_them_in_the_table(): void
    {
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');

        $assigned = Campaign::factory()->create([
            'status' => Campaign::STATUS_FIELD_VISIT,
            'assigned_volunteer_id' => $volunteer->id,
            'title' => 'Assigned To Me',
        ]);
        Campaign::factory()->pendingVerification()->create(['title' => 'Not Mine']);

        Livewire::actingAs($volunteer)->test(ListCampaigns::class)
            ->assertCanSeeTableRecords([$assigned])
            ->assertCountTableRecords(1);
    }

    public function test_assign_volunteer_action_is_only_visible_to_verification_staff_on_pending_campaigns(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('verification_admin');
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');

        $pending = Campaign::factory()->pendingVerification()->create();
        $draft = Campaign::factory()->create(['status' => Campaign::STATUS_DRAFT]);

        Livewire::actingAs($admin)->test(ListCampaigns::class)
            ->assertTableActionVisible('assignVolunteer', $pending)
            ->assertTableActionHidden('assignVolunteer', $draft)
            ->callTableAction('assignVolunteer', $pending, data: ['volunteer_id' => $volunteer->id]);

        $this->assertSame(Campaign::STATUS_FIELD_VISIT, $pending->fresh()->status);
        $this->assertSame($volunteer->id, $pending->fresh()->assigned_volunteer_id);
    }

    public function test_publish_action_is_hidden_from_verification_admin_but_visible_to_executive_admin(): void
    {
        $verificationAdmin = User::factory()->create();
        $verificationAdmin->assignRole('verification_admin');
        $executiveAdmin = User::factory()->create();
        $executiveAdmin->assignRole('executive_admin');

        $campaign = Campaign::factory()->create(['status' => Campaign::STATUS_EXECUTIVE_REVIEW]);

        Livewire::actingAs($verificationAdmin)->test(ListCampaigns::class)
            ->assertTableActionHidden('publish', $campaign);

        Livewire::actingAs($executiveAdmin)->test(ListCampaigns::class)
            ->assertTableActionVisible('publish', $campaign)
            ->callTableAction('publish', $campaign);

        $this->assertSame(Campaign::STATUS_PUBLISHED, $campaign->fresh()->status);
    }

    public function test_reject_action_sets_status_and_reason_from_the_table(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('verification_admin');
        $campaign = Campaign::factory()->pendingVerification()->create();

        Livewire::actingAs($admin)->test(ListCampaigns::class)
            ->callTableAction('reject', $campaign, data: ['reason' => 'Insufficient documentation']);

        $campaign->refresh();
        $this->assertSame(Campaign::STATUS_REJECTED, $campaign->status);
        $this->assertSame('Insufficient documentation', $campaign->rejection_reason);
    }

    public function test_the_campaign_view_infolist_page_renders_with_documents_and_reports(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('verification_admin');
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');

        $campaign = Campaign::factory()->create([
            'status' => Campaign::STATUS_FIELD_VISIT,
            'assigned_volunteer_id' => $volunteer->id,
        ]);
        $campaign->documents()->create([
            'type' => 'medical_report',
            'file_path' => 'campaign-documents/1/report.pdf',
            'uploaded_by' => $campaign->seeker_id,
        ]);
        FieldVisitReport::factory()->create([
            'campaign_id' => $campaign->id,
            'volunteer_id' => $volunteer->id,
        ]);

        $response = $this->actingAs($admin)->get("/control/campaigns/{$campaign->id}");

        $response->assertOk();
    }

    public function test_the_field_visit_report_view_infolist_page_renders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('verification_admin');
        $report = FieldVisitReport::factory()->create();

        $response = $this->actingAs($admin)->get("/control/field-visit-reports/{$report->id}");

        $response->assertOk();
    }

    public function test_field_visit_report_list_is_scoped_to_the_volunteers_own_reports(): void
    {
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');
        $otherVolunteer = User::factory()->create();
        $otherVolunteer->assignRole('volunteer');

        $ownReport = FieldVisitReport::factory()->create(['volunteer_id' => $volunteer->id]);
        FieldVisitReport::factory()->create(['volunteer_id' => $otherVolunteer->id]);

        Livewire::actingAs($volunteer)->test(ListFieldVisitReports::class)
            ->assertCanSeeTableRecords([$ownReport])
            ->assertCountTableRecords(1);
    }
}
