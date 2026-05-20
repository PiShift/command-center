<?php

namespace App\Notifications;

use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Notifications\Slack\SlackRoute;

class TestSlackNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text('✅ ' . config('app.name') . ' — Slack integration is working!');
    }

    public function routeNotificationForSlack(): SlackRoute
    {
        return SlackRoute::make(SlackNotificationHelper::defaultChannel(), SlackNotificationHelper::botToken());
    }
}
