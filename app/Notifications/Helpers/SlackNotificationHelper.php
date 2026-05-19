<?php

namespace App\Notifications\Helpers;

class SlackNotificationHelper
{
    public static function isEnabled(): bool
    {
        if (! config('services.slack.enabled', false)) {
            return false;
        }

        return ! empty(config('services.slack.webhook_url'));
    }

    public static function webhookUrl(): ?string
    {
        return config('services.slack.webhook_url') ?: null;
    }
}
