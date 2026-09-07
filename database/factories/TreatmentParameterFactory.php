<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\TreatmentParameter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreatmentParameter>
 */
class TreatmentParameterFactory extends Factory
{
    protected $model = TreatmentParameter::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'parameter_type' => TreatmentParameter::TYPE_WBC_COUNT,
            'label' => 'WBC Count',
            'value' => (string) $this->faker->numberBetween(3000, 11000),
            'unit' => 'cells/mcL',
            'recorded_at' => now(),
            'is_verified' => false,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['is_verified' => true]);
    }
}
