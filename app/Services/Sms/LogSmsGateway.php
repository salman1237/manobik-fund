<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

/**
 * spec §6 Phase 11 marks SMS as an *optional* future integration, and no
 * SMS provider (Twilio, a local BD aggregator, etc.) credentials have been
 * provided. This logs the message instead of sending it, so the calling
 * code (which alert fires, to whom, with what content) is fully built and
 * testable now - swap this binding for a real provider client in
 * AppServiceProvider once credentials exist, with no other code changes.
 */
class LogSmsGateway implements SmsGateway
{
    public function send(string $toPhoneNumber, string $message): void
    {
        Log::channel(config('logging.default'))->info('[SMS not sent - no provider configured]', [
            'to' => $toPhoneNumber,
            'message' => $message,
        ]);
    }
}
