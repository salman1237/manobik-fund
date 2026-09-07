<?php

namespace Database\Factories;

use App\Models\BloodDonor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BloodDonor>
 */
class BloodDonorFactory extends Factory
{
    protected $model = BloodDonor::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'blood_group' => $this->faker->randomElement(BloodDonor::BLOOD_GROUPS),
            'latitude' => $this->faker->latitude(23.6, 23.9),
            'longitude' => $this->faker->longitude(90.3, 90.5),
            'is_available' => true,
        ];
    }
}
