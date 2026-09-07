<?php

namespace App\Notifications;

use App\Models\Campaign;
use App\Models\Disbursement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FundsDisbursedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Campaign $campaign, public Disbursement $disbursement) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->disbursement->amount / 100, 2);

        return (new MailMessage)
            ->subject('Funds disbursed for: '.$this->campaign->title)
            ->line("A disbursement of {$amount} has been made for \"{$this->campaign->title}\".")
            ->line('The deposit slip has been published on your campaign page as proof of transfer.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'campaign_id' => $this->campaign->id,
            'disbursement_id' => $this->disbursement->id,
            'amount' => $this->disbursement->amount,
            'message' => "Funds disbursed for \"{$this->campaign->title}\".",
        ];
    }
}
