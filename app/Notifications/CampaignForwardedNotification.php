<?php

namespace App\Notifications;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignForwardedNotification extends Notification
{
    use Queueable;

    public function __construct(public Campaign $campaign) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your campaign has moved to final review')
            ->line("\"{$this->campaign->title}\" passed field verification and is now with the Executive Admin for final review.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'message' => "\"{$this->campaign->title}\" is now in executive review.",
        ];
    }
}
