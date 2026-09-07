<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'donor_name' => $this->faker->name(),
            'donor_email' => $this->faker->safeEmail(),
            'amount' => $this->faker->numberBetween(10000, 500000),
            'currency' => 'BDT',
            'gateway' => Donation::GATEWAY_SHURJOPAY,
            'transaction_id' => $this->faker->unique()->uuid(),
            'status' => Donation::STATUS_PENDING,
            'is_anonymous' => false,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => Donation::STATUS_COMPLETED]);
    }
}
