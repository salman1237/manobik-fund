<?php

namespace App\Filament\Widgets;

use App\Models\Disbursement;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class DisbursementHistoryChart extends ChartWidget
{
    protected static ?string $heading = 'Disbursement History (Last 6 Months)';

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $i) => now()->subMonths($i)->format('Y-m'));

        // Grouped in PHP rather than a driver-specific SQL date-format
        // function (MySQL's DATE_FORMAT vs. SQLite's strftime), matching
        // the portable approach already used for blood-donor proximity
        // search - this dataset is small enough that it costs nothing.
        $totalsByMonth = Disbursement::query()
            ->get(['amount', 'disbursed_at'])
            ->groupBy(fn (Disbursement $d) => $d->disbursed_at->format('Y-m'))
            ->map(fn ($group) => $group->sum('amount') / 100);

        return [
            'datasets' => [[
                'label' => 'Disbursed',
                'data' => $months->map(fn ($m) => $totalsByMonth[$m] ?? 0)->all(),
                'borderColor' => '#059669',
                'backgroundColor' => '#05966933',
            ]],
            'labels' => $months->map(fn ($m) => \Illuminate\Support\Carbon::createFromFormat('Y-m', $m)->format('M Y'))->all(),
        ];
    }
}
