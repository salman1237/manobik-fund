<?php

namespace App\Policies;

use App\Models\BloodDriveEvent;
use App\Models\User;

class BloodDriveEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * "Volunteers help manage donation drive events" (spec §4.4) - any
     * staff role may organize one, not only Volunteers, since Verification/
     * Executive/Super Admin can do everything a Volunteer can plus more.
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, BloodDriveEvent $event): bool
    {
        return $event->organized_by === $user->id || $user->hasAnyRole(['executive_admin', 'super_admin']);
    }
}
