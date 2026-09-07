<?php

namespace App\Filament\Resources\BloodDonorResource\Pages;

use App\Filament\Resources\BloodDonorResource;
use Filament\Resources\Pages\ListRecords;

class ListBloodDonors extends ListRecords
{
    protected static string $resource = BloodDonorResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
