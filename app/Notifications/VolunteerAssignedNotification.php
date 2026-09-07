<?php

namespace App\Notifications;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VolunteerAssignedNotification extends Notification implements ShouldQueue
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
            ->subject('New field visit assigned: '.$this->campaign->title)
            ->line("You've been assigned a field visit for the campaign \"{$this->campaign->title}\".")
            ->line('Hospital: '.($this->campaign->hospital_name ?? 'N/A'))
            ->action('Review Campaign', url('/control'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'campaign_title' => $this->campaign->title,
            'message' => "You've been assigned a field visit for \"{$this->campaign->title}\".",
        ];
    }
}
