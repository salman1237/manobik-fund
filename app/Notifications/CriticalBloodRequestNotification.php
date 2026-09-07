<?php

namespace App\Notifications;

use App\Models\BloodRequest;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * spec §6 Phase 11: "Optional SMS gateway integration for critical alerts
 * (blood requests, urgent camp needs)." Sent to nearby available donors of
 * a matching blood group when a critical-urgency request is posted.
 * Queued like the other notifications (Phase 12 performance pass) - relies
 * on a running queue worker in production (see DEPLOYMENT.md) to still
 * deliver promptly despite being time-sensitive.
 */
class CriticalBloodRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BloodRequest $bloodRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        $hospital = $this->bloodRequest->hospital_name ? " at {$this->bloodRequest->hospital_name}" : '';

        return "URGENT: {$this->bloodRequest->blood_group} blood needed{$hospital}. Contact {$this->bloodRequest->requester_phone} if you can help. - Manobik Fund";
    }

    public function toArray(object $notifiable): array
    {
        return [
            'blood_request_id' => $this->bloodRequest->id,
            'blood_group' => $this->bloodRequest->blood_group,
            'message' => "Critical {$this->bloodRequest->blood_group} blood request posted.",
        ];
    }
}
