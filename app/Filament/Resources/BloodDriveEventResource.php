<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BloodDriveEventResource\Pages;
use App\Models\BloodDriveEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BloodDriveEventResource extends Resource
{
    protected static ?string $model = BloodDriveEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Donation Drives';

    protected static ?string $navigationGroup = 'Blood Network';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->columnSpanFull(),
                Forms\Components\TextInput::make('location')->required()->maxLength(255),
                Forms\Components\TextInput::make('latitude')->numeric(),
                Forms\Components\TextInput::make('longitude')->numeric(),
                Forms\Components\DateTimePicker::make('scheduled_at')->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        BloodDriveEvent::STATUS_UPCOMING => 'Upcoming',
                        BloodDriveEvent::STATUS_COMPLETED => 'Completed',
                        BloodDriveEvent::STATUS_CANCELLED => 'Cancelled',
                    ])
                    ->default(BloodDriveEvent::STATUS_UPCOMING)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('organizer.name')->label('Organized By'),
                Tables\Columns\TextColumn::make('location'),
                Tables\Columns\TextColumn::make('scheduled_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->defaultSort('scheduled_at')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBloodDriveEvents::route('/'),
            'create' => Pages\CreateBloodDriveEvent::route('/create'),
            'edit' => Pages\EditBloodDriveEvent::route('/{record}/edit'),
        ];
    }
}
