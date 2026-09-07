<?php

namespace Database\Factories;

use App\Models\Ambulance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ambulance>
 */
class AmbulanceFactory extends Factory
{
    protected $model = Ambulance::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Ambulance',
            'driver_contact' => $this->faker->phoneNumber(),
            'vehicle_type' => $this->faker->randomElement(Ambulance::VEHICLE_TYPES),
            'district' => $this->faker->randomElement(['Dhaka', 'Chattogram', 'Khulna', 'Rajshahi', 'Sylhet']),
            'latitude' => $this->faker->latitude(20, 26),
            'longitude' => $this->faker->longitude(88, 92),
            'is_available' => true,
            'added_by' => User::factory(),
        ];
    }
}
