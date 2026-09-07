<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Models\Campaign;
use App\Models\Disbursement;
use App\Models\FieldVisitReport;
use App\Models\FundUtilization;
use App\Models\User;
use App\Services\CampaignDisbursementService;
use App\Services\CampaignVerificationService;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Campaign Verification';

    /**
     * Campaigns are created by Seekers via the public wizard (Phase 2), not
     * from this internal panel - staff only review and transition status
     * here. This also avoids exposing the raw encrypted bank_account_details
     * column through a generic form field.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Volunteers only ever see campaigns assigned to them.
        if ($user?->hasRole('volunteer') && ! $user->hasAnyRole(['verification_admin', 'executive_admin', 'super_admin'])) {
            $query->where('assigned_volunteer_id', $user->id);
        }

        return $query;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Campaign')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),
                        TextEntry::make('seeker.name')->label('Seeker'),
                        TextEntry::make('seeker.email')->label('Seeker Email'),
                        TextEntry::make('category'),
                        TextEntry::make('hospital_name')->placeholder('-'),
                        TextEntry::make('target_amount')->formatStateUsing(fn (int $state) => number_format($state / 100, 2).' BDT'),
                        TextEntry::make('raised_amount')->formatStateUsing(fn (int $state) => number_format($state / 100, 2).' BDT'),
                        TextEntry::make('assignedVolunteer.name')->label('Assigned Volunteer')->placeholder('Not yet assigned'),
                        TextEntry::make('rejection_reason')->placeholder('-')->visible(fn (Campaign $record) => $record->status === Campaign::STATUS_REJECTED),
                        TextEntry::make('description')->columnSpanFull(),
                    ]),

                Section::make('Documents')
                    ->schema([
                        RepeatableEntry::make('documents')
                            ->schema([
                                TextEntry::make('type')->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),
                                TextEntry::make('file_path')->label('File'),
                                TextEntry::make('verified_at')->dateTime()->placeholder('Not verified'),
                            ])
                            ->columns(3)
                            ->contained(false),
                    ])
                    ->visible(fn (Campaign $record) => $record->documents->isNotEmpty()),

                Section::make('Field Visit Reports')
                    ->schema([
                        RepeatableEntry::make('fieldVisitReports')
                            ->schema([
                                TextEntry::make('volunteer.name')->label('Volunteer'),
                                TextEntry::make('recommendation')->badge(),
                                TextEntry::make('notes')->columnSpanFull(),
                                TextEntry::make('created_at')->dateTime(),
                            ])
                            ->columns(3)
                            ->contained(false),
                    ])
                    ->visible(fn (Campaign $record) => $record->fieldVisitReports->isNotEmpty()),

                Section::make('Disbursements')
                    ->schema([
                        RepeatableEntry::make('disbursements')
                            ->schema([
                                TextEntry::make('amount')->formatStateUsing(fn (int $state) => number_format($state / 100, 2).' BDT'),
                                TextEntry::make('disburser.name')->label('Disbursed By'),
                                TextEntry::make('disbursed_at')->dateTime(),
                                TextEntry::make('deposit_slip_file')->label('Deposit Slip'),
                            ])
                            ->columns(4)
                            ->contained(false),
                    ])
                    ->visible(fn (Campaign $record) => $record->disbursements->isNotEmpty()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('seeker.name')
                    ->label('Seeker')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Campaign::STATUS_DRAFT => 'gray',
                        Campaign::STATUS_PENDING_VERIFICATION, Campaign::STATUS_FIELD_VISIT, Campaign::STATUS_EXECUTIVE_REVIEW => 'warning',
                        Campaign::STATUS_PUBLISHED, Campaign::STATUS_FUNDED, Campaign::STATUS_COMPLETED => 'success',
                        Campaign::STATUS_REJECTED, Campaign::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('assignedVolunteer.name')
                    ->label('Assigned Volunteer')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('target_amount')
                    ->label('Target')
                    ->formatStateUsing(fn (int $state) => number_format($state / 100, 2).' BDT'),
                Tables\Columns\TextColumn::make('raised_amount')
                    ->label('Raised')
                    ->formatStateUsing(fn (int $state) => number_format($state / 100, 2).' BDT'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Campaign::STATUS_PENDING_VERIFICATION => 'Pending Verification',
                        Campaign::STATUS_FIELD_VISIT => 'Field Visit',
                        Campaign::STATUS_EXECUTIVE_REVIEW => 'Executive Review',
                        Campaign::STATUS_PUBLISHED => 'Published',
                        Campaign::STATUS_REJECTED => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('assignVolunteer')
                    ->label('Assign Volunteer')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->visible(fn (Campaign $record) => Auth::user()->can('assignVolunteer', $record)
                        && $record->status === Campaign::STATUS_PENDING_VERIFICATION)
                    ->form([
                        Forms\Components\Select::make('volunteer_id')
                            ->label('Volunteer')
                            ->options(fn () => User::role('volunteer')->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Campaign $record, array $data) {
                        app(CampaignVerificationService::class)->assignVolunteer(
                            $record,
                            User::query()->findOrFail($data['volunteer_id']),
                            Auth::user(),
                        );

                        Notification::make()->title('Volunteer assigned')->success()->send();
                    }),

                Tables\Actions\Action::make('submitFieldReport')
                    ->label('Submit Field Report')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->visible(fn (Campaign $record) => Auth::user()->can('submitFieldReport', $record))
                    ->form([
                        Forms\Components\Textarea::make('notes')->required()->rows(4),
                        Forms\Components\Select::make('recommendation')
                            ->options([
                                FieldVisitReport::RECOMMENDATION_APPROVE => 'Recommend Approval',
                                FieldVisitReport::RECOMMENDATION_REJECT => 'Recommend Rejection',
                            ])
                            ->required(),
                    ])
                    ->action(function (Campaign $record, array $data) {
                        app(CampaignVerificationService::class)->submitFieldReport($record, Auth::user(), $data);

                        Notification::make()->title('Field report submitted')->success()->send();
                    }),

                Tables\Actions\Action::make('forwardToExecutive')
                    ->label('Forward to Executive')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Campaign $record) => Auth::user()->can('forwardToExecutive', $record)
                        && $record->status === Campaign::STATUS_FIELD_VISIT
                        && $record->fieldVisitReports()->exists())
                    ->action(function (Campaign $record) {
                        app(CampaignVerificationService::class)->forwardToExecutive($record, Auth::user());

                        Notification::make()->title('Forwarded to Executive Admin')->success()->send();
                    }),

                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Campaign $record) => Auth::user()->can('publish', $record)
                        && $record->status === Campaign::STATUS_EXECUTIVE_REVIEW)
                    ->action(function (Campaign $record) {
                        app(CampaignVerificationService::class)->publish($record, Auth::user());

                        Notification::make()->title('Campaign published')->success()->send();
                    }),

                Tables\Actions\Action::make('disburse')
                    ->label('Disburse Funds')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Campaign $record) => Auth::user()->can('disburse', $record)
                        && in_array($record->status, [Campaign::STATUS_PUBLISHED, Campaign::STATUS_FUNDED], true))
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (BDT)')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('new_status')
                            ->label('Mark Campaign As')
                            ->options([
                                Campaign::STATUS_FUNDED => 'Funded (more disbursements may follow)',
                                Campaign::STATUS_COMPLETED => 'Completed (final disbursement)',
                            ])
                            ->required(),
                        Forms\Components\FileUpload::make('deposit_slip')
                            ->label('Deposit Slip (public proof)')
                            ->disk('public')
                            ->directory('deposit-slips')
                            ->required(),
                        Forms\Components\Repeater::make('fund_utilization')
                            ->label('Fund Utilization Breakdown (optional)')
                            ->schema([
                                Forms\Components\Select::make('category')
                                    ->options([
                                        FundUtilization::CATEGORY_SURGERY => 'Surgery',
                                        FundUtilization::CATEGORY_MEDICATION => 'Medication',
                                        FundUtilization::CATEGORY_ICU => 'ICU',
                                        FundUtilization::CATEGORY_POST_OP => 'Post-Op',
                                        FundUtilization::CATEGORY_OTHER => 'Other',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('amount')->numeric()->required(),
                                Forms\Components\TextInput::make('description'),
                            ])
                            ->columns(3)
                            ->defaultItems(0),
                    ])
                    ->action(function (Campaign $record, array $data) {
                        app(CampaignDisbursementService::class)->disburse(
                            $record,
                            Auth::user(),
                            (int) round($data['amount'] * 100),
                            $data['deposit_slip'],
                            $data['new_status'],
                            $data['fund_utilization'] ?? [],
                        );

                        Notification::make()->title('Disbursement recorded')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Campaign $record) => Auth::user()->can('reject', $record)
                        && in_array($record->status, [
                            Campaign::STATUS_PENDING_VERIFICATION,
                            Campaign::STATUS_FIELD_VISIT,
                            Campaign::STATUS_EXECUTIVE_REVIEW,
                        ], true))
                    ->form([
                        Forms\Components\Textarea::make('reason')->required()->rows(3),
                    ])
                    ->action(function (Campaign $record, array $data) {
                        app(CampaignVerificationService::class)->reject($record, Auth::user(), $data['reason']);

                        Notification::make()->title('Campaign rejected')->danger()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'view' => Pages\ViewCampaign::route('/{record}'),
        ];
    }
}
