<?php

namespace App\Filament\Resources\BloodDonorResource\Pages;

use App\Filament\Resources\BloodDonorResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBloodDonor extends ViewRecord
{
    protected static string $resource = BloodDonorResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
