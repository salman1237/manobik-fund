<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FieldVisitReportResource\Pages;
use App\Models\FieldVisitReport;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FieldVisitReportResource extends Resource
{
    protected static ?string $model = FieldVisitReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Field Visit Reports';

    /**
     * Reports are an immutable audit trail, created only via the "Submit
     * Field Report" action on CampaignResource - never edited/deleted here.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Volunteers get a heavily restricted view of only their own
        // submitted reports (spec §7).
        if ($user?->hasRole('volunteer') && ! $user->hasAnyRole(['verification_admin', 'executive_admin', 'super_admin'])) {
            $query->where('volunteer_id', $user->id);
        }

        return $query;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('campaign.title')->label('Campaign'),
                        TextEntry::make('volunteer.name')->label('Volunteer'),
                        TextEntry::make('recommendation')->badge(),
                        TextEntry::make('created_at')->label('Submitted')->dateTime(),
                        TextEntry::make('notes')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('campaign.title')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('volunteer.name')
                    ->label('Volunteer'),
                Tables\Columns\TextColumn::make('recommendation')
                    ->badge()
                    ->color(fn (string $state) => $state === 'approve' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFieldVisitReports::route('/'),
            'view' => Pages\ViewFieldVisitReport::route('/{record}'),
        ];
    }
}
