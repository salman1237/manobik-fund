<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RefundRequestResource\Pages;
use App\Models\Campaign;
use App\Models\RefundRequest;
use App\Services\RefundRequestService;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RefundRequestResource extends Resource
{
    protected static ?string $model = RefundRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Refund Requests';

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
                    TextEntry::make('donation.amount')->formatStateUsing(fn (int $state) => number_format($state / 100, 2)),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('resolution_type')->placeholder('-'),
                    TextEntry::make('reason')->columnSpanFull(),
                    TextEntry::make('rejection_reason')->placeholder('-')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Donor'),
                Tables\Columns\TextColumn::make('donation.amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (int $state) => number_format($state / 100, 2)),
                Tables\Columns\TextColumn::make('donation.campaign.title')->label('Campaign')->limit(30),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    RefundRequest::STATUS_PENDING => 'warning',
                    RefundRequest::STATUS_APPROVED => 'success',
                    RefundRequest::STATUS_REJECTED => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('approveGatewayRefund')
                    ->label('Approve: Gateway Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (RefundRequest $record) => $record->isPending() && Auth::user()->can('resolve', $record))
                    ->action(function (RefundRequest $record) {
                        try {
                            app(RefundRequestService::class)->approveWithGatewayRefund($record, Auth::user());
                            Notification::make()->title('Refund processed via gateway')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Refund failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\Action::make('approveCreditRedirect')
                    ->label('Approve: Redirect Credit')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (RefundRequest $record) => $record->isPending() && Auth::user()->can('resolve', $record))
                    ->form([
                        Forms\Components\Select::make('redirect_campaign_id')
                            ->label('Redirect To Campaign')
                            ->options(fn () => Campaign::query()->whereIn('status', [Campaign::STATUS_PUBLISHED, Campaign::STATUS_FUNDED])->pluck('title', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (RefundRequest $record, array $data) {
                        app(RefundRequestService::class)->approveWithCreditRedirect(
                            $record,
                            Auth::user(),
                            Campaign::query()->findOrFail($data['redirect_campaign_id']),
                        );

                        Notification::make()->title('Donation redirected as credit')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (RefundRequest $record) => $record->isPending() && Auth::user()->can('resolve', $record))
                    ->form([
                        Forms\Components\Textarea::make('reason')->required()->rows(3),
                    ])
                    ->action(function (RefundRequest $record, array $data) {
                        app(RefundRequestService::class)->reject($record, Auth::user(), $data['reason']);

                        Notification::make()->title('Refund request rejected')->danger()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRefundRequests::route('/'),
            'view' => Pages\ViewRefundRequest::route('/{record}'),
        ];
    }
}
