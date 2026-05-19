<?php

namespace App\Livewire;

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->loadCount();
    }

    public function loadCount(): void
    {
        $this->unreadCount = auth()->user()->unreadNotifications()->count();
    }

    public function markRead(string $id): void
    {
        $notification = auth()->user()->notifications()->find($id);
        $notification?->markAsRead();
        $this->loadCount();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->unreadCount = 0;
    }

    public function render()
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->take(10)
            ->get();

        return view('livewire.notification-bell', compact('notifications'));
    }
}
