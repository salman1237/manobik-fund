<?php

namespace App\Services;

use App\Exceptions\InvalidCampaignTransition;
use App\Models\Campaign;
use App\Models\FieldVisitReport;
use App\Models\User;
use App\Notifications\CampaignPublishedNotification;
use App\Notifications\CampaignRejectedNotification;
use App\Notifications\VolunteerAssignedNotification;
use Illuminate\Support\Facades\DB;

/**
 * The campaign verification pipeline state machine (spec section 6, Phase 3;
 * spec section 8 explicitly calls this out as the most failure-prone part of
 * the system and asks for it to be tested before any UI is built on top).
 *
 * draft -> pending_verification -> field_visit -> executive_review -> published
 *                    \                  \               \
 *                     -----------------> rejected <-------
 */
class CampaignVerificationService
{
    public function assignVolunteer(Campaign $campaign, User $volunteer, User $actor): Campaign
    {
        if ($campaign->status !== Campaign::STATUS_PENDING_VERIFICATION) {
            throw new InvalidCampaignTransition(
                "Cannot assign a volunteer to a campaign in status [{$campaign->status}]; expected [".Campaign::STATUS_PENDING_VERIFICATION.'].'
            );
        }

        if (! $volunteer->hasRole('volunteer')) {
            throw new InvalidCampaignTransition("User [{$volunteer->id}] does not have the volunteer role.");
        }

        DB::transaction(function () use ($campaign, $volunteer, $actor) {
            $campaign->update([
                'assigned_volunteer_id' => $volunteer->id,
                'volunteer_assigned_at' => now(),
                'status' => Campaign::STATUS_FIELD_VISIT,
            ]);

            activity()
                ->performedOn($campaign)
                ->causedBy($actor)
                ->withProperties(['volunteer_id' => $volunteer->id])
                ->log('Volunteer assigned for field visit');
        });

        $volunteer->notify(new VolunteerAssignedNotification($campaign));

        return $campaign->fresh();
    }

    public function submitFieldReport(Campaign $campaign, User $volunteer, array $data): FieldVisitReport
    {
        if ($campaign->status !== Campaign::STATUS_FIELD_VISIT) {
            throw new InvalidCampaignTransition(
                "Cannot submit a field report for a campaign in status [{$campaign->status}]; expected [".Campaign::STATUS_FIELD_VISIT.'].'
            );
        }

        if ($campaign->assigned_volunteer_id !== $volunteer->id) {
            throw new InvalidCampaignTransition("Volunteer [{$volunteer->id}] is not assigned to campaign [{$campaign->id}].");
        }

        return DB::transaction(function () use ($campaign, $volunteer, $data) {
            $report = $campaign->fieldVisitReports()->create([
                'volunteer_id' => $volunteer->id,
                'notes' => $data['notes'],
                'images' => $data['images'] ?? null,
                'recommendation' => $data['recommendation'],
            ]);

            activity()
                ->performedOn($campaign)
                ->causedBy($volunteer)
                ->withProperties(['field_visit_report_id' => $report->id, 'recommendation' => $report->recommendation])
                ->log('Field visit report submitted');

            return $report;
        });
    }

    public function forwardToExecutive(Campaign $campaign, User $actor): Campaign
    {
        if ($campaign->status !== Campaign::STATUS_FIELD_VISIT) {
            throw new InvalidCampaignTransition(
                "Cannot forward a campaign in status [{$campaign->status}]; expected [".Campaign::STATUS_FIELD_VISIT.'].'
            );
        }

        if ($campaign->fieldVisitReports()->doesntExist()) {
            throw new InvalidCampaignTransition('Cannot forward a campaign with no field visit report.');
        }

        $campaign->update(['status' => Campaign::STATUS_EXECUTIVE_REVIEW]);

        activity()
            ->performedOn($campaign)
            ->causedBy($actor)
            ->log('Forwarded to Executive Admin for final review');

        return $campaign->fresh();
    }

    public function reject(Campaign $campaign, User $actor, string $reason): Campaign
    {
        if (! in_array($campaign->status, [
            Campaign::STATUS_PENDING_VERIFICATION,
            Campaign::STATUS_FIELD_VISIT,
            Campaign::STATUS_EXECUTIVE_REVIEW,
        ], true)) {
            throw new InvalidCampaignTransition("Cannot reject a campaign in status [{$campaign->status}].");
        }

        $campaign->update([
            'status' => Campaign::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);

        activity()
            ->performedOn($campaign)
            ->causedBy($actor)
            ->withProperties(['reason' => $reason])
            ->log('Campaign rejected');

        $campaign->seeker->notify(new CampaignRejectedNotification($campaign));

        return $campaign->fresh();
    }

    public function publish(Campaign $campaign, User $actor): Campaign
    {
        if ($campaign->status !== Campaign::STATUS_EXECUTIVE_REVIEW) {
            throw new InvalidCampaignTransition(
                "Cannot publish a campaign in status [{$campaign->status}]; expected [".Campaign::STATUS_EXECUTIVE_REVIEW.'].'
            );
        }

        $campaign->update([
            'status' => Campaign::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        activity()
            ->performedOn($campaign)
            ->causedBy($actor)
            ->log('Campaign published');

        $campaign->seeker->notify(new CampaignPublishedNotification($campaign));

        return $campaign->fresh();
    }
}
