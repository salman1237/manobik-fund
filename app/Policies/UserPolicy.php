<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Staff account management (including role assignment) is
     * super_admin-only - unlike other internal resources, this one can
     * grant privileges, so it isn't opened up to the wider "isStaff()" group.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('super_admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * A super_admin may deactivate other accounts but never their own -
     * otherwise a lone super_admin could lock themselves out entirely.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('super_admin') && $user->isNot($model);
    }
}
