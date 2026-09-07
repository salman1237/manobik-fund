<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignDocument>
 */
class CampaignDocumentFactory extends Factory
{
    protected $model = CampaignDocument::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'type' => $this->faker->randomElement([
                CampaignDocument::TYPE_MEDICAL_REPORT,
                CampaignDocument::TYPE_ID_PROOF,
                CampaignDocument::TYPE_HOSPITAL_BILL,
                CampaignDocument::TYPE_OTHER,
            ]),
            'file_path' => 'campaign-documents/'.$this->faker->uuid().'.pdf',
            'uploaded_by' => User::factory(),
        ];
    }
}
