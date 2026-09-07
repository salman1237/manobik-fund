<?php

namespace Database\Factories;

use App\Models\Donation;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RefundRequest>
 */
class RefundRequestFactory extends Factory
{
    protected $model = RefundRequest::class;

    public function definition(): array
    {
        return [
            'donation_id' => Donation::factory(),
            'user_id' => User::factory(),
            'reason' => $this->faker->sentence(),
            'status' => RefundRequest::STATUS_PENDING,
        ];
    }
}
