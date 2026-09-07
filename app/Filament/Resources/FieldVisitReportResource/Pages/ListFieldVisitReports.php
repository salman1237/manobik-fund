<?php

namespace App\Filament\Resources\FieldVisitReportResource\Pages;

use App\Filament\Resources\FieldVisitReportResource;
use Filament\Resources\Pages\ListRecords;

class ListFieldVisitReports extends ListRecords
{
    protected static string $resource = FieldVisitReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
