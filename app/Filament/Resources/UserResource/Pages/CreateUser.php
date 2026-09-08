<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Staff accounts are created directly by a super_admin - skip the
     * public donor/seeker email-verification flow entirely rather than
     * leaving a brand-new Volunteer/Verification Admin unable to log in
     * and use the panel until they click a link that (on this deployment)
     * only ever lands in a log file (see PROGRESS.md: MAIL_MAILER=log).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['email_verified_at'] = now();

        return $data;
    }
}
