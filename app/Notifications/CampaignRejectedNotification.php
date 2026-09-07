<?php

namespace App\Notifications;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignRejectedNotification extends Notification
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
            ->subject('Update on your campaign: '.$this->campaign->title)
            ->line("Your campaign \"{$this->campaign->title}\" was not approved.")
            ->line('Reason: '.($this->campaign->rejection_reason ?? 'No reason provided.'))
            ->line('You can review the feedback and contact support if you have questions.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'campaign_title' => $this->campaign->title,
            'reason' => $this->campaign->rejection_reason,
            'message' => "Your campaign \"{$this->campaign->title}\" was rejected.",
        ];
    }
}
