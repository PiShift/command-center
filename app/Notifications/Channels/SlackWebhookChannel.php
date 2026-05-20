<?php

namespace App\Notifications\Channels;

use App\Notifications\Helpers\SlackNotificationHelper;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class SlackWebhookChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSlackText')) {
            return;
        }

        $url = SlackNotificationHelper::webhookUrl();

        if (empty($url)) {
            return;
        }

        $text = $notification->toSlackText($notifiable);

        if (empty($text)) {
            return;
        }

        Http::post($url, ['text' => $text]);
    }
}
