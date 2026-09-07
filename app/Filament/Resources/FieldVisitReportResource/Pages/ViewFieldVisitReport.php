<?php

namespace App\Filament\Resources\FieldVisitReportResource\Pages;

use App\Filament\Resources\FieldVisitReportResource;
use Filament\Resources\Pages\ViewRecord;

class ViewFieldVisitReport extends ViewRecord
{
    protected static string $resource = FieldVisitReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
