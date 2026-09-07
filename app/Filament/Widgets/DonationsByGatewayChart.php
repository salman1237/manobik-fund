<?php

namespace App\Filament\Widgets;

use App\Models\Donation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class DonationsByGatewayChart extends ChartWidget
{
    protected static ?string $heading = 'Donations by Gateway';

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $totals = Donation::query()
            ->where('status', Donation::STATUS_COMPLETED)
            ->selectRaw('gateway, SUM(amount) as total')
            ->groupBy('gateway')
            ->pluck('total', 'gateway');

        return [
            'datasets' => [[
                'data' => $totals->values()->map(fn ($v) => $v / 100)->all(),
                'backgroundColor' => ['#059669', '#0891b2', '#d97706'],
            ]],
            'labels' => $totals->keys()->map(fn ($g) => ucfirst($g))->all(),
        ];
    }
}
