<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignUpdate>
 */
class CampaignUpdateFactory extends Factory
{
    protected $model = CampaignUpdate::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'posted_by' => User::factory(),
            'content' => $this->faker->paragraph(),
        ];
    }
}
