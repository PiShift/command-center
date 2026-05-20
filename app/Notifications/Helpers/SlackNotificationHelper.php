<?php

namespace App\Notifications\Helpers;

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
}

