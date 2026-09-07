<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmbulanceResource\Pages;
use App\Models\Ambulance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AmbulanceResource extends Resource
{
    protected static ?string $model = Ambulance::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Ambulances';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('driver_contact')->required()->maxLength(255),
                Forms\Components\Select::make('vehicle_type')
                    ->options(array_combine(Ambulance::VEHICLE_TYPES, array_map('ucfirst', Ambulance::VEHICLE_TYPES)))
                    ->required(),
                Forms\Components\TextInput::make('district')->required()->maxLength(255),
                Forms\Components\TextInput::make('latitude')->numeric(),
                Forms\Components\TextInput::make('longitude')->numeric(),
                Forms\Components\Toggle::make('is_available')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('district')->searchable(),
                Tables\Columns\TextColumn::make('vehicle_type')->badge(),
                Tables\Columns\TextColumn::make('driver_contact'),
                Tables\Columns\IconColumn::make('is_available')->boolean()->label('Available'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('district'),
                Tables\Filters\TernaryFilter::make('is_available')->label('Available'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAmbulances::route('/'),
            'create' => Pages\CreateAmbulance::route('/create'),
            'edit' => Pages\EditAmbulance::route('/{record}/edit'),
        ];
    }
}
