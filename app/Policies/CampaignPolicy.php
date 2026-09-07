<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    /**
     * Gates access to the Filament CampaignResource list itself. Row-level
     * scoping (e.g. Volunteers only seeing their assigned campaigns) is
     * handled separately in CampaignResource::getEloquentQuery().
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Public/published campaigns are visible to everyone (including guests,
     * handled separately in the controller). This gate covers authenticated
     * access to a campaign that isn't public yet: the owning Seeker, or
     * staff reviewing it.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        return $campaign->isPublic()
            || $campaign->seeker_id === $user->id
            || $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isDonationSeeker();
    }

    /**
     * A Seeker never sees other users' campaigns for editing (spec §3),
     * and only while the campaign is still a draft.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        return $campaign->seeker_id === $user->id && $campaign->isEditableBySeeker();
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $campaign->seeker_id === $user->id && $campaign->isEditableBySeeker();
    }

    /**
     * Seekers may post progress updates on their own campaign only once
     * it has actually been published (spec §4.1).
     */
    public function postUpdate(User $user, Campaign $campaign): bool
    {
        return $campaign->seeker_id === $user->id && $campaign->isPublic();
    }

    /**
     * Verification Admin and above assign a Volunteer for a field visit.
     */
    public function assignVolunteer(User $user, Campaign $campaign): bool
    {
        return $user->hasAnyRole(['verification_admin', 'executive_admin', 'super_admin']);
    }

    /**
     * Only the Volunteer actually assigned to this campaign may report on it.
     */
    public function submitFieldReport(User $user, Campaign $campaign): bool
    {
        return $user->hasRole('volunteer') && $campaign->assigned_volunteer_id === $user->id;
    }

    /**
     * Verification Admin and above forward a field-visited campaign onward.
     */
    public function forwardToExecutive(User $user, Campaign $campaign): bool
    {
        return $user->hasAnyRole(['verification_admin', 'executive_admin', 'super_admin']);
    }

    /**
     * Verification Admin and above may reject at either review stage.
     */
    public function reject(User $user, Campaign $campaign): bool
    {
        return $user->hasAnyRole(['verification_admin', 'executive_admin', 'super_admin']);
    }

    /**
     * Only Executive Admin and Super Admin publish (spec §3 role table).
     */
    public function publish(User $user, Campaign $campaign): bool
    {
        return $user->hasAnyRole(['executive_admin', 'super_admin']);
    }

    /**
     * Only Executive Admin and Super Admin move money (spec §3 role table:
     * "Can Disburse Funds?").
     */
    public function disburse(User $user, Campaign $campaign): bool
    {
        return $user->hasAnyRole(['executive_admin', 'super_admin']);
    }
}
