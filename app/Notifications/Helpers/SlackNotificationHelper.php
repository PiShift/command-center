<?php

namespace App\Notifications\Helpers;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackRoute;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class SlackNotificationHelper
{
    public static function isEnabled(): bool
    {
        if (! config('services.slack.enabled', false)) {
            return false;
        }

        return ! empty(config('services.slack.bot_token'));
    }

    public static function botToken(): ?string
    {
        return config('services.slack.bot_token') ?: null;
    }

    public static function defaultChannel(): string
    {
        return config('services.slack.default_channel') ?: '#general';
    }

    /**
     * Send a Slack notification exactly once, using the notification's own
     * routeNotificationForSlack() if it has one, falling back to the default channel.
     */
    public static function notifyOnce(Notification $notification): void
    {
        if (! self::isEnabled()) {
            return;
        }

        $route = method_exists($notification, 'routeNotificationForSlack')
            ? $notification->routeNotificationForSlack()
            : SlackRoute::make(self::defaultChannel(), self::botToken());

        NotificationFacade::route('slack', $route)->notify($notification);
    }
}

