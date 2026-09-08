<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

/**
 * Staff account management (spec §12: "UserResource, RoleResource" -
 * RoleResource itself is provided by filament-shield at /control/shield/roles).
 * Gated to super_admin only (UserPolicy) since this can grant privileges -
 * this is how a Verification Admin, Executive Admin, or Volunteer account
 * actually gets created; public registration always lands on the plain
 * "user" role (see AssignDefaultRole listener).
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Staff Users';

    protected static ?string $navigationGroup = 'Administration';

    public const STAFF_ROLES = ['volunteer', 'verification_admin', 'executive_admin', 'super_admin'];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrateStateUsing(fn (?string $state) => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Leave blank to keep the current password when editing.'),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name', fn ($query) => $query->whereIn('name', self::STAFF_ROLES))
                    ->multiple()
                    ->preload()
                    ->required()
                    ->helperText('Grants access to this internal panel. Regular donors/seekers never need a role here.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->separator(',')
                    ->label('Roles'),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->boolean()
                    ->getStateUsing(fn (User $record) => filled($record->email_verified_at))
                    ->label('Verified'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name', fn ($query) => $query->whereIn('name', self::STAFF_ROLES)),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
