<?php

namespace Tests\Feature;

use App\Filament\Resources\AmbulanceResource\Pages\CreateAmbulance;
use App\Models\Ambulance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Phase9AmbulanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_staff_can_manage_ambulances(): void
    {
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');
        $donor = User::factory()->create();
        $donor->assignRole('user');
        $ambulance = Ambulance::factory()->create();

        $this->assertTrue($volunteer->can('create', Ambulance::class));
        $this->assertTrue($volunteer->can('update', $ambulance));
        $this->assertFalse($donor->can('create', Ambulance::class));
        $this->assertFalse($donor->can('update', $ambulance));
    }

    public function test_a_volunteer_can_create_an_ambulance_listing_via_filament(): void
    {
        $volunteer = User::factory()->create();
        $volunteer->assignRole('volunteer');

        Livewire::actingAs($volunteer)->test(CreateAmbulance::class)
            ->set('data.name', 'City Emergency Ambulance')
            ->set('data.driver_contact', '01711223344')
            ->set('data.vehicle_type', 'van')
            ->set('data.district', 'Dhaka')
            ->set('data.is_available', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('ambulances', [
            'name' => 'City Emergency Ambulance',
            'district' => 'Dhaka',
            'added_by' => $volunteer->id,
        ]);
    }

    public function test_the_public_directory_only_lists_available_ambulances(): void
    {
        Ambulance::factory()->create(['name' => 'Available Van', 'is_available' => true]);
        Ambulance::factory()->create(['name' => 'Out Of Service Van', 'is_available' => false]);

        $response = $this->get(route('ambulances.index'));

        $response->assertOk();
        $response->assertSee('Available Van');
        $response->assertDontSee('Out Of Service Van');
    }

    public function test_the_public_directory_can_be_filtered_by_district(): void
    {
        Ambulance::factory()->create(['name' => 'Dhaka Ambulance', 'district' => 'Dhaka', 'is_available' => true]);
        Ambulance::factory()->create(['name' => 'Sylhet Ambulance', 'district' => 'Sylhet', 'is_available' => true]);

        $response = $this->get(route('ambulances.index', ['district' => 'Dhaka']));

        $response->assertOk();
        $response->assertSee('Dhaka Ambulance');
        $response->assertDontSee('Sylhet Ambulance');
    }

    public function test_the_directory_page_renders_the_map_container(): void
    {
        Ambulance::factory()->create(['latitude' => 23.75, 'longitude' => 90.39]);

        $response = $this->get(route('ambulances.index'));

        $response->assertOk();
        $response->assertSee('id="ambulance-map"', false);
    }
}
