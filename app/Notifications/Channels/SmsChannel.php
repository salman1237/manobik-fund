<?php

namespace App\Notifications\Channels;

use App\Contracts\SmsGateway;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(protected SmsGateway $gateway) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $phone = $notifiable->routeNotificationFor('sms', $notification)
            ?? $notifiable->phone
            ?? null;

        if (! $phone) {
            return;
        }

        $this->gateway->send($phone, $notification->toSms($notifiable));
    }
}
