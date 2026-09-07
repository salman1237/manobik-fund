<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Disbursement>
 */
class DisbursementFactory extends Factory
{
    protected $model = Disbursement::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'amount' => $this->faker->numberBetween(10000, 500000),
            'deposit_slip_file' => 'deposit-slips/'.$this->faker->uuid().'.pdf',
            'disbursed_by' => User::factory(),
            'disbursed_at' => now(),
        ];
    }
}
