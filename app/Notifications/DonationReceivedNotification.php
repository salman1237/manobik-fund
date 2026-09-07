<?php

namespace App\Notifications;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies the campaign's Seeker (not the donor - see
 * DonationReceiptNotification for that) that a new donation landed on
 * their campaign, per spec §6 Phase 11.
 */
class DonationReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(public Donation $donation) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->donation->amount / 100, 2).' '.$this->donation->currency;
        $donorName = $this->donation->is_anonymous ? 'An anonymous donor' : $this->donation->donor_name;

        return (new MailMessage)
            ->subject('New donation received: '.$this->donation->campaign?->title)
            ->line("{$donorName} just donated {$amount} to \"{$this->donation->campaign?->title}\".");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'donation_id' => $this->donation->id,
            'campaign_id' => $this->donation->campaign_id,
            'amount' => $this->donation->amount,
            'message' => 'New donation received on your campaign.',
        ];
    }
}
