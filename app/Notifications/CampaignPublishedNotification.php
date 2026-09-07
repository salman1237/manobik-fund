<?php

namespace App\Notifications;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignPublishedNotification extends Notification
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
            ->subject('Your campaign is live: '.$this->campaign->title)
            ->line("Great news! Your campaign \"{$this->campaign->title}\" is now published and accepting donations.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'campaign_title' => $this->campaign->title,
            'message' => "Your campaign \"{$this->campaign->title}\" is now live.",
        ];
    }
}
