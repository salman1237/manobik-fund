<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $target = $this->faker->numberBetween(50000, 2000000); // poisha

        return [
            'seeker_id' => User::factory(),
            'category' => $this->faker->randomElement([
                Campaign::CATEGORY_TREATMENT,
                Campaign::CATEGORY_EMERGENCY,
                Campaign::CATEGORY_CAMP,
                Campaign::CATEGORY_EDUCATION,
            ]),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraphs(3, true),
            'hospital_name' => $this->faker->company().' Hospital',
            'latitude' => $this->faker->latitude(20, 26),
            'longitude' => $this->faker->longitude(88, 92),
            'target_amount' => $target,
            'raised_amount' => 0,
            'status' => Campaign::STATUS_DRAFT,
            'deadline' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => Campaign::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function pendingVerification(): static
    {
        return $this->state(fn () => [
            'status' => Campaign::STATUS_PENDING_VERIFICATION,
        ]);
    }
}
