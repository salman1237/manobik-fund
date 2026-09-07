<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BloodDonorResource\Pages;
use App\Models\BloodDonor;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BloodDonorResource extends Resource
{
    protected static ?string $model = BloodDonor::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Blood Donors';

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
                    TextEntry::make('user.name')->label('Donor'),
                    TextEntry::make('user.phone')->label('Phone')->placeholder('-'),
                    TextEntry::make('blood_group')->badge(),
                    TextEntry::make('is_available')->badge()->formatStateUsing(fn (bool $state) => $state ? 'Available' : 'Not available'),
                    TextEntry::make('last_donation_date')->date()->placeholder('Never'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Donor')->searchable(),
                Tables\Columns\TextColumn::make('blood_group')->badge(),
                Tables\Columns\IconColumn::make('is_available')->boolean()->label('Available'),
                Tables\Columns\TextColumn::make('last_donation_date')->date()->placeholder('Never'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('blood_group')->options(array_combine(BloodDonor::BLOOD_GROUPS, BloodDonor::BLOOD_GROUPS)),
                Tables\Filters\TernaryFilter::make('is_available')->label('Available'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBloodDonors::route('/'),
            'view' => Pages\ViewBloodDonor::route('/{record}'),
        ];
    }
}
