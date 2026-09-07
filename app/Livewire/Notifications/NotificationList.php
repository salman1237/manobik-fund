<?php

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationList extends Component
{
    public function markAsRead(string $notificationId): void
    {
        Auth::user()->notifications()->where('id', $notificationId)->first()?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        return view('livewire.notifications.notification-list', [
            'notifications' => Auth::user()->notifications()->latest()->paginate(15),
        ]);
    }
}
