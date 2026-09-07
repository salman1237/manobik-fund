<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\TreatmentParameter;
use App\Models\User;

class TreatmentParameterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Only the Seeker or an admin can add parameter entries (spec §4.2),
     * only once the campaign is actually live, and never for education
     * campaigns (spec Phase 10: no medical dimension to track there).
     */
    public function create(User $user, Campaign $campaign): bool
    {
        return ($campaign->seeker_id === $user->id || $user->isStaff())
            && $campaign->isPublic()
            && $campaign->needsMedicalTracking();
    }

    /**
     * Verification/Executive/Super Admin sign off before a parameter
     * appears publicly, to prevent fabricated progress data (spec §4.2).
     */
    public function verify(User $user, TreatmentParameter $parameter): bool
    {
        return $user->hasAnyRole(['verification_admin', 'executive_admin', 'super_admin']);
    }
}
