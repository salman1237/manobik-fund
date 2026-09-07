<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
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
}
