<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Facades\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase0FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_seeder_creates_all_base_roles(): void
    {
        foreach (['super_admin', 'executive_admin', 'verification_admin', 'volunteer', 'user'] as $role) {
            $this->assertTrue(
                \Spatie\Permission\Models\Role::query()->where('name', $role)->exists(),
                "Expected role [{$role}] to exist after seeding."
            );
        }
    }

    public function test_staff_roles_can_access_the_admin_panel(): void
    {
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');

        $panel = \Filament\Facades\Filament::getPanel('admin');

        $this->assertTrue($volunteer->canAccessPanel($panel));
    }

    public function test_plain_authenticated_users_cannot_access_the_admin_panel(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('user');

        $panel = \Filament\Facades\Filament::getPanel('admin');

        $this->assertFalse($donor->canAccessPanel($panel));
    }

    public function test_guests_without_any_role_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create();

        $panel = \Filament\Facades\Filament::getPanel('admin');

        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_admin_panel_dark_mode_is_disabled(): void
    {
        // Client decision (2026-09-07): light theme everywhere, no dark mode.
        $panel = \Filament\Facades\Filament::getPanel('admin');

        $this->assertFalse($panel->hasDarkMode());
    }

    public function test_settings_helper_reads_and_writes_values(): void
    {
        Settings::set('site_name', 'Manobik Fund');
        Settings::set('donation_reward_percent', 5, type: 'integer');

        $this->assertSame('Manobik Fund', setting('site_name'));
        $this->assertSame(5, setting('donation_reward_percent'));
        $this->assertSame('fallback', setting('missing_key', 'fallback'));
    }
}
