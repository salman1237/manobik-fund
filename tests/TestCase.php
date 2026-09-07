<?php

namespace Tests;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Roles are foundational reference data (spec §3) that user-related
     * code assumes exists - e.g. AssignDefaultRole assigns the 'user' role
     * on every registration. Auto-seed them whenever a test uses
     * RefreshDatabase, so individual tests don't need to remember to.
     */
    protected bool $seed = true;

    protected string $seeder = RoleSeeder::class;
}
