<?php

namespace Database\Factories;

use App\Models\BloodRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BloodRequest>
 */
class BloodRequestFactory extends Factory
{
    protected $model = BloodRequest::class;

    public function definition(): array
    {
        return [
            'requester_name' => $this->faker->name(),
            'requester_phone' => $this->faker->phoneNumber(),
            'blood_group' => $this->faker->randomElement(\App\Models\BloodDonor::BLOOD_GROUPS),
            'hospital_name' => $this->faker->company().' Hospital',
            'urgency' => BloodRequest::URGENCY_NORMAL,
            'status' => BloodRequest::STATUS_OPEN,
        ];
    }
}
