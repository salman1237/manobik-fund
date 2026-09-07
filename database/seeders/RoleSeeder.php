<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Base roles for the platform. Per-resource permissions are generated
     * separately by filament-shield (`php artisan shield:generate`) as
     * Filament resources are built out in later phases.
     */
    protected const ROLES = [
        'super_admin',
        'executive_admin',
        'verification_admin',
        'volunteer',
        'user',
    ];

    public function run(): void
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $role) {
            Role::query()->firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
