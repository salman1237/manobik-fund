<?php

namespace App\Notifications;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DonationReceiptNotification extends Notification
{
    use Queueable;

    public function __construct(public Donation $donation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->donation->amount / 100, 2).' '.$this->donation->currency;
        $campaignTitle = $this->donation->campaign?->title ?? 'the general fund';

        return (new MailMessage)
            ->subject('Thank you for your donation - Manobik Fund')
            ->greeting('Thank you, '.$this->donation->donor_name.'!')
            ->line("Your donation of {$amount} to \"{$campaignTitle}\" has been received.")
            ->line('Transaction ID: '.$this->donation->transaction_id)
            ->line('This receipt confirms your contribution. Keep it for your records.');
    }
}
