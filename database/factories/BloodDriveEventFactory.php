<?php

namespace Database\Factories;

use App\Models\BloodDriveEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BloodDriveEvent>
 */
class BloodDriveEventFactory extends Factory
{
    protected $model = BloodDriveEvent::class;

    public function definition(): array
    {
        return [
            'organized_by' => User::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'location' => $this->faker->address(),
            'scheduled_at' => now()->addWeek(),
            'status' => BloodDriveEvent::STATUS_UPCOMING,
        ];
    }
}
