<?php

namespace Tests\Feature;

use App\Filament\Resources\BloodDriveEventResource\Pages\CreateBloodDriveEvent;
use App\Filament\Resources\BloodRequestResource\Pages\ListBloodRequests;
use App\Livewire\Blood\DonorProfileForm;
use App\Livewire\Blood\RequestForm;
use App\Models\BloodDonor;
use App\Models\BloodDriveEvent;
use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase8BloodNetworkTest extends TestCase
{
    use RefreshDatabase;

    // --- Donor profile ---

    public function test_a_user_can_register_as_a_blood_donor(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        Livewire::actingAs($user)->test(DonorProfileForm::class)
            ->set('bloodGroup', 'O+')
            ->set('latitude', 23.75)
            ->set('longitude', 90.39)
            ->call('save');

        $this->assertDatabaseHas('blood_donors', [
            'user_id' => $user->id,
            'blood_group' => 'O+',
        ]);
    }

    public function test_a_user_cannot_register_a_second_donor_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        BloodDonor::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($user->can('create', BloodDonor::class));
    }

    public function test_a_user_can_toggle_their_own_availability(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        BloodDonor::factory()->create(['user_id' => $user->id, 'is_available' => true]);

        Livewire::actingAs($user)->test(DonorProfileForm::class)
            ->call('toggleAvailability');

        $this->assertFalse($user->fresh()->bloodDonorProfile->is_available);
    }

    public function test_a_user_cannot_update_someone_elses_donor_profile(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $profile = BloodDonor::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($intruder->can('update', $profile));
    }

    // --- Proximity search ---

    public function test_nearby_finds_donors_within_radius_and_excludes_far_ones(): void
    {
        // Dhaka
        $near = BloodDonor::factory()->create([
            'blood_group' => 'O+',
            'latitude' => 23.7500,
            'longitude' => 90.3900,
            'is_available' => true,
        ]);
        // Chittagong (~200+ km away)
        BloodDonor::factory()->create([
            'blood_group' => 'O+',
            'latitude' => 22.3569,
            'longitude' => 91.7832,
            'is_available' => true,
        ]);

        $results = BloodDonor::nearby(23.7461, 90.3742, 25, 'O+');

        $this->assertCount(1, $results);
        $this->assertSame($near->id, $results->first()->id);
    }

    public function test_nearby_excludes_unavailable_donors(): void
    {
        BloodDonor::factory()->create([
            'latitude' => 23.7500,
            'longitude' => 90.3900,
            'is_available' => false,
        ]);

        $results = BloodDonor::nearby(23.7461, 90.3742, 25);

        $this->assertCount(0, $results);
    }

    public function test_nearby_filters_by_blood_group(): void
    {
        BloodDonor::factory()->create(['blood_group' => 'A+', 'latitude' => 23.75, 'longitude' => 90.39]);
        $match = BloodDonor::factory()->create(['blood_group' => 'B+', 'latitude' => 23.75, 'longitude' => 90.39]);

        $results = BloodDonor::nearby(23.7461, 90.3742, 25, 'B+');

        $this->assertCount(1, $results);
        $this->assertSame($match->id, $results->first()->id);
    }

    // --- Blood requests ---

    public function test_a_guest_can_post_a_blood_request(): void
    {
        Livewire::test(RequestForm::class)
            ->set('requesterName', 'Karim Uddin')
            ->set('requesterPhone', '01700000000')
            ->set('bloodGroup', 'AB-')
            ->set('urgency', 'critical')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('blood_requests', [
            'requester_name' => 'Karim Uddin',
            'blood_group' => 'AB-',
            'urgency' => 'critical',
            'requested_by' => null,
            'status' => 'open',
        ]);
    }

    public function test_an_authenticated_users_request_records_their_user_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        Livewire::actingAs($user)->test(RequestForm::class)
            ->set('requesterName', 'Fatima')
            ->set('requesterPhone', '01800000000')
            ->set('bloodGroup', 'B+')
            ->call('submit');

        $this->assertDatabaseHas('blood_requests', [
            'requester_name' => 'Fatima',
            'requested_by' => $user->id,
        ]);
    }

    // --- Public pages ---

    public function test_public_blood_pages_render(): void
    {
        $this->get(route('blood.donors'))->assertOk();
        $this->get(route('blood.requests.create'))->assertOk();
        $this->get(route('blood.drives'))->assertOk();

        BloodRequest::factory()->create(['requester_name' => 'Visible Requester']);
        $this->get(route('blood.requests.index'))->assertOk()->assertSee('Visible Requester');

        BloodDriveEvent::factory()->create(['title' => 'City Blood Camp']);
        $this->get(route('blood.drives'))->assertOk()->assertSee('City Blood Camp');
    }

    // --- Filament admin ---

    public function test_only_staff_can_organize_a_blood_drive(): void
    {
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');
        $donor = User::factory()->create();
        $donor->assignRole('user');

        $this->assertTrue($volunteer->can('create', BloodDriveEvent::class));
        $this->assertFalse($donor->can('create', BloodDriveEvent::class));
    }

    public function test_a_volunteer_can_create_a_blood_drive_via_filament(): void
    {
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');

        Livewire::actingAs($volunteer)->test(CreateBloodDriveEvent::class)
            ->set('data.title', 'University Blood Camp')
            ->set('data.location', 'Dhaka University')
            ->set('data.scheduled_at', now()->addWeek()->toDateTimeString())
            ->set('data.status', BloodDriveEvent::STATUS_UPCOMING)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('blood_drive_events', [
            'title' => 'University Blood Camp',
            'organized_by' => $volunteer->id,
        ]);
    }

    public function test_mark_fulfilled_action_closes_an_open_request(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('verification_admin');
        $request = BloodRequest::factory()->create(['status' => BloodRequest::STATUS_OPEN]);

        Livewire::actingAs($admin)->test(ListBloodRequests::class)
            ->assertTableActionVisible('markFulfilled', $request)
            ->callTableAction('markFulfilled', $request);

        $this->assertSame(BloodRequest::STATUS_FULFILLED, $request->fresh()->status);
    }
}
