<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Quick-access card for staff account management, right on the dashboard -
 * UserResource lives under an "Administration" nav group that sorts below
 * everything else, so this puts a visible shortcut where it's actually seen.
 */
class StaffOverview extends BaseWidget
{
    // Pinned above the financial widgets - this is a navigation shortcut
    // first, a stat second, and it should be the first thing a Super Admin
    // sees rather than buried under the charts.
    protected static ?int $sort = -10;

    // Filament widgets lazy-load by default via a follow-up Livewire AJAX
    // request. UserResource::getUrl() needs the current panel resolved,
    // which isn't reliably available on that separate request cycle -
    // rendering eagerly (in the same request as the rest of the page,
    // which we know has panel context) avoids that failure mode entirely.
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    protected function getStats(): array
    {
        $counts = User::query()
            ->role(UserResource::STAFF_ROLES)
            ->get()
            ->flatMap(fn (User $user) => $user->roles->pluck('name'))
            ->countBy();

        $breakdown = collect(UserResource::STAFF_ROLES)
            ->map(fn (string $role) => ucwords(str_replace('_', ' ', $role)).': '.($counts[$role] ?? 0))
            ->implode(' · ');

        return [
            Stat::make('Staff Users', User::role(UserResource::STAFF_ROLES)->count())
                ->description($breakdown)
                ->descriptionIcon('heroicon-o-users')
                ->icon('heroicon-o-user-group')
                ->url(UserResource::getUrl('index'))
                ->color('primary'),
        ];
    }
}
