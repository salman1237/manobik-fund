<?php

namespace App\Notifications;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundRequestResolvedNotification extends Notification
{
    use Queueable;

    public function __construct(public RefundRequest $refundRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject('Update on your refund request');

        return match ($this->refundRequest->status) {
            RefundRequest::STATUS_APPROVED => $mail
                ->line($this->refundRequest->resolution_type === RefundRequest::RESOLUTION_CREDIT_REDIRECT
                    ? 'Your refund request was approved and your donation has been redirected to another campaign as credit.'
                    : 'Your refund request was approved. The refund has been processed back to your original payment method.'),
            default => $mail
                ->line('Your refund request was not approved.')
                ->line('Reason: '.($this->refundRequest->rejection_reason ?? 'No reason provided.')),
        };
    }

    public function toArray(object $notifiable): array
    {
        return [
            'refund_request_id' => $this->refundRequest->id,
            'status' => $this->refundRequest->status,
        ];
    }
}
