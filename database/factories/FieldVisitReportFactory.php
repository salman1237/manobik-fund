<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\FieldVisitReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldVisitReport>
 */
class FieldVisitReportFactory extends Factory
{
    protected $model = FieldVisitReport::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'volunteer_id' => User::factory(),
            'notes' => $this->faker->paragraph(),
            'recommendation' => FieldVisitReport::RECOMMENDATION_APPROVE,
        ];
    }
}
