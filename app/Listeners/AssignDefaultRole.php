<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;

class AssignDefaultRole
{
    /**
     * Every newly registered account is a base Authenticated User (spec §3).
     * "Donation Seeker" is not a role - it's a capability gated on email
     * verification, checked directly on the User model where needed.
     */
    public function handle(Registered $event): void
    {
        if (! $event->user->hasAnyRole(['super_admin', 'executive_admin', 'verification_admin', 'volunteer', 'user'])) {
            $event->user->assignRole('user');
        }
    }
}
