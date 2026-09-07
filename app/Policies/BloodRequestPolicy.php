<?php

namespace App\Policies;

use App\Models\BloodRequest;
use App\Models\User;

class BloodRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff, or the requester themselves (if they were authenticated when
     * they posted it), may update a request's status.
     */
    public function update(User $user, BloodRequest $bloodRequest): bool
    {
        return $user->isStaff() || $bloodRequest->requested_by === $user->id;
    }
}
