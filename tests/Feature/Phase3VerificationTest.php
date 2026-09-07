<?php

namespace Tests\Feature;

use App\Exceptions\InvalidCampaignTransition;
use App\Models\Campaign;
use App\Models\FieldVisitReport;
use App\Models\User;
use App\Notifications\CampaignPublishedNotification;
use App\Notifications\CampaignRejectedNotification;
use App\Notifications\VolunteerAssignedNotification;
use App\Services\CampaignVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Tests the verification pipeline state machine directly against the
 * service layer, per spec section 8: "Write feature tests for the
 * verification pipeline state machine ... before building the UI around
 * it." No Filament UI is exercised here.
 */
class Phase3VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected CampaignVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CampaignVerificationService::class);
    }

    protected function verificationAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('verification_admin');

        return $user;
    }

    protected function executiveAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('executive_admin');

        return $user;
    }

    protected function volunteer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('volunteer');

        return $user;
    }

    public function test_assigning_a_volunteer_moves_a_pending_campaign_to_field_visit(): void
    {
        Notification::fake();

        $campaign = Campaign::factory()->pendingVerification()->create();
        $volunteer = $this->volunteer();
        $admin = $this->verificationAdmin();

        $result = $this->service->assignVolunteer($campaign, $volunteer, $admin);

        $this->assertSame(Campaign::STATUS_FIELD_VISIT, $result->status);
        $this->assertSame($volunteer->id, $result->assigned_volunteer_id);
        $this->assertNotNull($result->volunteer_assigned_at);

        Notification::assertSentTo($volunteer, VolunteerAssignedNotification::class);

        $this->assertDatabaseHas(Activity::class, [
            'subject_id' => $campaign->id,
            'subject_type' => Campaign::class,
            'causer_id' => $admin->id,
            'description' => 'Volunteer assigned for field visit',
        ]);
    }

    public function test_a_volunteer_cannot_be_assigned_to_a_campaign_that_is_not_pending_verification(): void
    {
        $campaign = Campaign::factory()->create(['status' => Campaign::STATUS_DRAFT]);

        $this->expectException(InvalidCampaignTransition::class);

        $this->service->assignVolunteer($campaign, $this->volunteer(), $this->verificationAdmin());
    }

    public function test_a_non_volunteer_cannot_be_assigned_a_field_visit(): void
    {
        $campaign = Campaign::factory()->pendingVerification()->create();
        $notAVolunteer = User::factory()->create();
        $notAVolunteer->assignRole('user');

        $this->expectException(InvalidCampaignTransition::class);

        $this->service->assignVolunteer($campaign, $notAVolunteer, $this->verificationAdmin());
    }

    public function test_the_assigned_volunteer_can_submit_a_field_report(): void
    {
        $volunteer = $this->volunteer();
        $campaign = Campaign::factory()->create([
            'status' => Campaign::STATUS_FIELD_VISIT,
            'assigned_volunteer_id' => $volunteer->id,
        ]);

        $report = $this->service->submitFieldReport($campaign, $volunteer, [
            'notes' => 'Visited the hospital, patient confirmed.',
            'recommendation' => FieldVisitReport::RECOMMENDATION_APPROVE,
        ]);

        $this->assertDatabaseHas('field_visit_reports', [
            'id' => $report->id,
            'campaign_id' => $campaign->id,
            'volunteer_id' => $volunteer->id,
            'recommendation' => 'approve',
        ]);

        // Submitting a report does not itself change the campaign status -
        // Verification Admin still has to review it and forward.
        $this->assertSame(Campaign::STATUS_FIELD_VISIT, $campaign->fresh()->status);
    }

    public function test_a_report_cannot_be_submitted_by_a_volunteer_who_is_not_assigned(): void
    {
        $assignedVolunteer = $this->volunteer();
        $otherVolunteer = $this->volunteer();
        $campaign = Campaign::factory()->create([
            'status' => Campaign::STATUS_FIELD_VISIT,
            'assigned_volunteer_id' => $assignedVolunteer->id,
        ]);

        $this->expectException(InvalidCampaignTransition::class);

        $this->service->submitFieldReport($campaign, $otherVolunteer, [
            'notes' => 'Trying to report on a campaign I am not assigned to.',
            'recommendation' => FieldVisitReport::RECOMMENDATION_APPROVE,
        ]);
    }

    public function test_a_report_cannot_be_submitted_outside_the_field_visit_status(): void
    {
        $volunteer = $this->volunteer();
        $campaign = Campaign::factory()->pendingVerification()->create([
            'assigned_volunteer_id' => $volunteer->id,
        ]);

        $this->expectException(InvalidCampaignTransition::class);

        $this->service->submitFieldReport($campaign, $volunteer, [
            'notes' => 'Too early.',
            'recommendation' => FieldVisitReport::RECOMMENDATION_APPROVE,
        ]);
    }

    public function test_forwarding_to_executive_requires_an_existing_field_report(): void
    {
        $campaign = Campaign::factory()->create(['status' => Campaign::STATUS_FIELD_VISIT]);

        $this->expectException(InvalidCampaignTransition::class);

        $this->service->forwardToExecutive($campaign, $this->verificationAdmin());
    }

    public function test_forwarding_to_executive_succeeds_once_a_report_exists(): void
    {
        $volunteer = $this->volunteer();
        $campaign = Campaign::factory()->create([
            'status' => Campaign::STATUS_FIELD_VISIT,
            'assigned_volunteer_id' => $volunteer->id,
        ]);

        $this->service->submitFieldReport($campaign, $volunteer, [
            'notes' => 'All good.',
            'recommendation' => FieldVisitReport::RECOMMENDATION_APPROVE,
        ]);

        $result = $this->service->forwardToExecutive($campaign, $this->verificationAdmin());

        $this->assertSame(Campaign::STATUS_EXECUTIVE_REVIEW, $result->status);
    }

    public function test_forwarding_fails_from_a_status_other_than_field_visit(): void
    {
        $campaign = Campaign::factory()->pendingVerification()->create();

        $this->expectException(InvalidCampaignTransition::class);

        $this->service->forwardToExecutive($campaign, $this->verificationAdmin());
    }

    public function test_a_campaign_can_be_rejected_from_any_review_stage(): void
    {
        Notification::fake();

        foreach ([
            Campaign::STATUS_PENDING_VERIFICATION,
            Campaign::STATUS_FIELD_VISIT,
            Campaign::STATUS_EXECUTIVE_REVIEW,
        ] as $status) {
            $seeker = User::factory()->create();
            $seeker->assignRole('user');
            $campaign = Campaign::factory()->create(['status' => $status, 'seeker_id' => $seeker->id]);

            $result = $this->service->reject($campaign, $this->verificationAdmin(), 'Missing documents');

            $this->assertSame(Campaign::STATUS_REJECTED, $result->status);
            $this->assertSame('Missing documents', $result->rejection_reason);
            Notification::assertSentTo($seeker, CampaignRejectedNotification::class);
        }
    }

    public function test_a_draft_or_already_published_campaign_cannot_be_rejected(): void
    {
        $draft = Campaign::factory()->create(['status' => Campaign::STATUS_DRAFT]);

        $this->expectException(InvalidCampaignTransition::class);

        $this->service->reject($draft, $this->verificationAdmin(), 'too early');
    }

    public function test_publishing_requires_executive_review_status(): void
    {
        $campaign = Campaign::factory()->create(['status' => Campaign::STATUS_FIELD_VISIT]);

        $this->expectException(InvalidCampaignTransition::class);

        $this->service->publish($campaign, $this->executiveAdmin());
    }

    public function test_publishing_from_executive_review_marks_the_campaign_live(): void
    {
        Notification::fake();

        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        $campaign = Campaign::factory()->create([
            'status' => Campaign::STATUS_EXECUTIVE_REVIEW,
            'seeker_id' => $seeker->id,
        ]);

        $result = $this->service->publish($campaign, $this->executiveAdmin());

        $this->assertSame(Campaign::STATUS_PUBLISHED, $result->status);
        $this->assertNotNull($result->published_at);
        $this->assertTrue($result->isPublic());
        Notification::assertSentTo($seeker, CampaignPublishedNotification::class);
    }

    public function test_full_happy_path_from_pending_verification_to_published(): void
    {
        Notification::fake();

        $seeker = User::factory()->create();
        $seeker->assignRole('user');
        $volunteer = $this->volunteer();
        $verificationAdmin = $this->verificationAdmin();
        $executiveAdmin = $this->executiveAdmin();

        $campaign = Campaign::factory()->pendingVerification()->create(['seeker_id' => $seeker->id]);

        $this->service->assignVolunteer($campaign, $volunteer, $verificationAdmin);
        $this->assertSame(Campaign::STATUS_FIELD_VISIT, $campaign->fresh()->status);

        $this->service->submitFieldReport($campaign, $volunteer, [
            'notes' => 'Confirmed patient and hospital.',
            'recommendation' => FieldVisitReport::RECOMMENDATION_APPROVE,
        ]);

        $this->service->forwardToExecutive($campaign, $verificationAdmin);
        $this->assertSame(Campaign::STATUS_EXECUTIVE_REVIEW, $campaign->fresh()->status);

        $this->service->publish($campaign, $executiveAdmin);
        $this->assertSame(Campaign::STATUS_PUBLISHED, $campaign->fresh()->status);
    }

    // --- Policy-level authorization (who is even allowed to call the service) ---

    public function test_only_verification_admin_and_above_may_assign_volunteers(): void
    {
        $campaign = Campaign::factory()->pendingVerification()->create();

        $this->assertTrue($this->verificationAdmin()->can('assignVolunteer', $campaign));
        $this->assertTrue($this->executiveAdmin()->can('assignVolunteer', $campaign));

        $volunteer = $this->volunteer();
        $this->assertFalse($volunteer->can('assignVolunteer', $campaign));
    }

    public function test_only_executive_admin_and_super_admin_may_publish(): void
    {
        $campaign = Campaign::factory()->create(['status' => Campaign::STATUS_EXECUTIVE_REVIEW]);

        $this->assertTrue($this->executiveAdmin()->can('publish', $campaign));
        $this->assertFalse($this->verificationAdmin()->can('publish', $campaign));
    }

    public function test_only_the_assigned_volunteer_may_submit_a_field_report(): void
    {
        $assigned = $this->volunteer();
        $other = $this->volunteer();
        $campaign = Campaign::factory()->create([
            'status' => Campaign::STATUS_FIELD_VISIT,
            'assigned_volunteer_id' => $assigned->id,
        ]);

        $this->assertTrue($assigned->can('submitFieldReport', $campaign));
        $this->assertFalse($other->can('submitFieldReport', $campaign));
    }
}
