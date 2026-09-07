<?php

namespace App\Policies;

use App\Models\Ambulance;
use App\Models\User;

class AmbulancePolicy
{
    /**
     * Public directory listing needs no authorization; this only gates the
     * internal Filament resource (spec §6 Phase 9: "Admin/Volunteer CRUD").
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Ambulance $ambulance): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Ambulance $ambulance): bool
    {
        return $user->isStaff();
    }
}
