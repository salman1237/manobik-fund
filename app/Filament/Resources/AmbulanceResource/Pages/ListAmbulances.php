<?php

namespace App\Filament\Resources\AmbulanceResource\Pages;

use App\Filament\Resources\AmbulanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmbulances extends ListRecords
{
    protected static string $resource = AmbulanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
