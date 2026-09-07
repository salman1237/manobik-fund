<?php

namespace App\Filament\Resources\BloodDriveEventResource\Pages;

use App\Filament\Resources\BloodDriveEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBloodDriveEvents extends ListRecords
{
    protected static string $resource = BloodDriveEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
