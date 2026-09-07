<?php

namespace App\Filament\Resources\BloodDriveEventResource\Pages;

use App\Filament\Resources\BloodDriveEventResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBloodDriveEvent extends CreateRecord
{
    protected static string $resource = BloodDriveEventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organized_by'] = Auth::id();

        return $data;
    }
}
