<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Widgets\StaffOverview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_super_admin_can_view_the_staff_overview_widget(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin);
        $this->assertTrue(StaffOverview::canView());

        $executiveAdmin = User::factory()->create();
        $executiveAdmin->assignRole('executive_admin');
        $this->actingAs($executiveAdmin);
        $this->assertFalse(StaffOverview::canView());
    }

    public function test_the_staff_overview_widget_shows_a_live_count_and_links_to_the_user_list(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');
        $executive = User::factory()->create();
        $executive->assignRole('executive_admin');

        Livewire::actingAs($superAdmin)->test(StaffOverview::class)
            ->assertSee('Staff Users')
            ->assertSee('3') // super admin + volunteer + executive admin
            ->assertSee('Volunteer: 1')
            ->assertSee('Executive Admin: 1');
    }

    public function test_a_verification_admin_cannot_reach_the_staff_user_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('verification_admin');

        $response = $this->actingAs($admin)->get('/control/users');

        $response->assertForbidden();
    }

    public function test_a_volunteer_cannot_reach_the_staff_user_list(): void
    {
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');

        $response = $this->actingAs($volunteer)->get('/control/users');

        $response->assertForbidden();
    }

    public function test_a_super_admin_can_view_the_staff_user_list(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->get('/control/users');

        $response->assertOk();
    }

    public function test_a_super_admin_can_create_a_volunteer_account_that_is_pre_verified(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        Livewire::actingAs($superAdmin)->test(CreateUser::class)
            ->fillForm([
                'name' => 'New Volunteer',
                'email' => 'volunteer@example.com',
                'password' => 'a-secure-password',
                'roles' => [\Spatie\Permission\Models\Role::where('name', 'volunteer')->first()->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $volunteer = User::where('email', 'volunteer@example.com')->first();

        $this->assertNotNull($volunteer);
        $this->assertTrue($volunteer->hasRole('volunteer'));
        $this->assertNotNull($volunteer->email_verified_at);
        $this->assertTrue($volunteer->isStaff());
    }

    public function test_a_super_admin_cannot_delete_their_own_account_via_the_policy(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertFalse($superAdmin->can('delete', $superAdmin));
    }

    public function test_a_super_admin_can_delete_another_staff_account(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');

        $this->assertTrue($superAdmin->can('delete', $volunteer));
    }
}
