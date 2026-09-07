<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BloodRequestResource\Pages;
use App\Models\BloodRequest;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BloodRequestResource extends Resource
{
    protected static ?string $model = BloodRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Blood Requests';

    protected static ?string $navigationGroup = 'Blood Network';

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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make()
                ->columns(2)
                ->schema([
                    TextEntry::make('requester_name'),
                    TextEntry::make('requester_phone'),
                    TextEntry::make('blood_group')->badge(),
                    TextEntry::make('urgency')->badge(),
                    TextEntry::make('hospital_name')->placeholder('-'),
                    TextEntry::make('status')->badge(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('requester_name'),
                Tables\Columns\TextColumn::make('requester_phone'),
                Tables\Columns\TextColumn::make('blood_group')->badge(),
                Tables\Columns\TextColumn::make('urgency')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'critical' => 'danger',
                        'urgent' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    BloodRequest::STATUS_OPEN => 'Open',
                    BloodRequest::STATUS_FULFILLED => 'Fulfilled',
                    BloodRequest::STATUS_CANCELLED => 'Cancelled',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('markFulfilled')
                    ->label('Mark Fulfilled')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (BloodRequest $record) => $record->isOpen())
                    ->action(function (BloodRequest $record) {
                        $record->update(['status' => BloodRequest::STATUS_FULFILLED]);
                        Notification::make()->title('Marked as fulfilled')->success()->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (BloodRequest $record) => $record->isOpen())
                    ->action(function (BloodRequest $record) {
                        $record->update(['status' => BloodRequest::STATUS_CANCELLED]);
                        Notification::make()->title('Request cancelled')->danger()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBloodRequests::route('/'),
            'view' => Pages\ViewBloodRequest::route('/{record}'),
        ];
    }
}
