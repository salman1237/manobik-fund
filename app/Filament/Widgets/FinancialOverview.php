<?php

namespace App\Filament\Widgets;

use App\Models\Disbursement;
use App\Models\Donation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * spec §6 Phase 12: "Super Admin financial analytics dashboard ... total
 * raised, per-gateway breakdown, per-category breakdown, disbursement
 * history." All figures are computed live from donations/disbursements on
 * every render - no cached/precomputed totals, per the client's "no fake or
 * static numbers" decision (spec's Client Decisions log).
 */
class FinancialOverview extends BaseWidget
{
    public static function canView(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    protected function getStats(): array
    {
        $totalRaised = Donation::query()->where('status', Donation::STATUS_COMPLETED)->sum('amount');
        $totalDisbursed = Disbursement::query()->sum('amount');
        $donationCount = Donation::query()->where('status', Donation::STATUS_COMPLETED)->count();

        return [
            Stat::make('Total Raised', number_format($totalRaised / 100, 2))
                ->description('Across all completed donations'),
            Stat::make('Total Disbursed', number_format($totalDisbursed / 100, 2))
                ->description('Across all campaigns'),
            Stat::make('Completed Donations', $donationCount)
                ->description('All-time count'),
        ];
    }
}
