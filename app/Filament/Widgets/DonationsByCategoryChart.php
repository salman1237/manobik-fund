<?php

namespace App\Filament\Widgets;

use App\Models\Campaign;
use App\Models\Donation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class DonationsByCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Donations by Campaign Category';

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $totals = Donation::query()
            ->join('campaigns', 'campaigns.id', '=', 'donations.campaign_id')
            ->where('donations.status', Donation::STATUS_COMPLETED)
            ->selectRaw('campaigns.category as category, SUM(donations.amount) as total')
            ->groupBy('campaigns.category')
            ->pluck('total', 'category');

        $categories = [
            Campaign::CATEGORY_TREATMENT,
            Campaign::CATEGORY_EMERGENCY,
            Campaign::CATEGORY_CAMP,
            Campaign::CATEGORY_EDUCATION,
        ];

        return [
            'datasets' => [[
                'label' => 'Raised (BDT-equivalent major units)',
                'data' => array_map(fn ($c) => ($totals[$c] ?? 0) / 100, $categories),
                'backgroundColor' => '#059669',
            ]],
            'labels' => array_map('ucfirst', $categories),
        ];
    }
}
