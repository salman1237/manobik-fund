<?php

namespace App\Filament\Resources\BloodDriveEventResource\Pages;

use App\Filament\Resources\BloodDriveEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBloodDriveEvent extends EditRecord
{
    protected static string $resource = BloodDriveEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
