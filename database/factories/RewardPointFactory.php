<?php

namespace Database\Factories;

use App\Models\Donation;
use App\Models\RewardPoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RewardPoint>
 */
class RewardPointFactory extends Factory
{
    protected $model = RewardPoint::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'donation_id' => Donation::factory(),
            'points' => $this->faker->numberBetween(1, 100),
        ];
    }
}
