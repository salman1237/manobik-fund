<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use App\Models\Campaign;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    /**
     * Admin-created emergency/camp/education campaigns skip the
     * verification pipeline entirely (spec §4.3: "typically created/
     * managed directly by admins") - the creating admin is both author
     * and approver here, so it goes live immediately rather than sitting
     * in a queue waiting for itself to be reviewed.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // The form's Select only offers these 3, but validate again server
        // side too - treatment campaigns must always go through the Seeker
        // wizard + verification pipeline, never this shortcut.
        if (! in_array($data['category'], [Campaign::CATEGORY_EMERGENCY, Campaign::CATEGORY_CAMP, Campaign::CATEGORY_EDUCATION], true)) {
            throw ValidationException::withMessages(['category' => 'Treatment campaigns must be created by a Seeker through the standard verification pipeline.']);
        }

        $data['seeker_id'] = Auth::id();
        $data['target_amount'] = (int) round($data['target_amount'] * 100);
        $data['status'] = Campaign::STATUS_PUBLISHED;
        $data['published_at'] = now();

        return $data;
    }
}
