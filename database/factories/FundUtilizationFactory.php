<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\FundUtilization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FundUtilization>
 */
class FundUtilizationFactory extends Factory
{
    protected $model = FundUtilization::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'category' => $this->faker->randomElement([
                FundUtilization::CATEGORY_SURGERY,
                FundUtilization::CATEGORY_MEDICATION,
                FundUtilization::CATEGORY_ICU,
                FundUtilization::CATEGORY_POST_OP,
            ]),
            'amount' => $this->faker->numberBetween(5000, 200000),
            'description' => $this->faker->sentence(),
        ];
    }
}
