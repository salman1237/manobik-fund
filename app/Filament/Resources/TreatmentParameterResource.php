<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TreatmentParameterResource\Pages;
use App\Models\TreatmentParameter;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TreatmentParameterResource extends Resource
{
    protected static ?string $model = TreatmentParameter::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Treatment Parameters';

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('campaign.title')
                    ->label('Campaign')
                    ->limit(35)
                    ->searchable(),
                Tables\Columns\TextColumn::make('parameter_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('label'),
                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(fn (TreatmentParameter $record) => $record->value.' '.$record->unit),
                Tables\Columns\TextColumn::make('recorded_at')->date(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verified'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')->label('Verified'),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TreatmentParameter $record) => ! $record->is_verified && Auth::user()->can('verify', $record))
                    ->action(function (TreatmentParameter $record) {
                        $record->update([
                            'is_verified' => true,
                            'verified_by' => Auth::id(),
                        ]);

                        Notification::make()->title('Parameter verified - now visible publicly')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTreatmentParameters::route('/'),
        ];
    }
}
