<?php

namespace App\Policies;

use App\Models\BloodDonor;
use App\Models\User;

class BloodDonorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Every authenticated user may register/maintain exactly one donor
     * profile of their own (spec §4.4/§3).
     */
    public function create(User $user): bool
    {
        return ! $user->bloodDonorProfile()->exists();
    }

    public function update(User $user, BloodDonor $bloodDonor): bool
    {
        return $bloodDonor->user_id === $user->id;
    }
}
