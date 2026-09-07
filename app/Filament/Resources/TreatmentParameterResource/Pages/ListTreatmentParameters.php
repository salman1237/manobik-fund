<?php

namespace App\Filament\Resources\TreatmentParameterResource\Pages;

use App\Filament\Resources\TreatmentParameterResource;
use Filament\Resources\Pages\ListRecords;

class ListTreatmentParameters extends ListRecords
{
    protected static string $resource = TreatmentParameterResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
