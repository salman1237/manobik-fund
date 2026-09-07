<?php

namespace App\Filament\Resources\AmbulanceResource\Pages;

use App\Filament\Resources\AmbulanceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAmbulance extends CreateRecord
{
    protected static string $resource = AmbulanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['added_by'] = Auth::id();

        return $data;
    }
}
