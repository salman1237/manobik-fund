<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_event_assigns_the_default_user_role(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($user->hasAnyRole(['user', 'super_admin', 'executive_admin', 'verification_admin', 'volunteer']));

        event(new Registered($user));

        $this->assertTrue($user->fresh()->hasRole('user'));
    }

    public function test_registration_event_does_not_override_an_existing_role(): void
    {
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');

        event(new Registered($volunteer));

        $this->assertTrue($volunteer->fresh()->hasRole('volunteer'));
        $this->assertFalse($volunteer->fresh()->hasRole('user'));
    }

    public function test_dashboard_redirects_staff_to_the_admin_panel(): void
    {
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');

        $response = $this->actingAs($volunteer)->get('/dashboard');

        $response->assertRedirect('/control');
    }

    public function test_dashboard_renders_the_public_view_for_plain_users(): void
    {
        $donor = User::factory()->create();
        $donor->assignRole('user');

        $response = $this->actingAs($donor)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard');
    }

    public function test_guests_are_redirected_away_from_the_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_donation_seeker_capability_requires_a_verified_email(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('user');

        $this->assertFalse($user->isDonationSeeker());

        $user->markEmailAsVerified();

        $this->assertTrue($user->fresh()->isDonationSeeker());
    }
}
