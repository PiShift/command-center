<?php

namespace App\Notifications\Helpers;

use App\Models\Setting;

class SlackNotificationHelper
{
    public static function isEnabled(): bool
    {
        $dbEnabled = Setting::get('slack_enabled');

        if ($dbEnabled !== null) {
            $enabled = filter_var($dbEnabled, FILTER_VALIDATE_BOOLEAN);
        } else {
            $enabled = config('services.slack.enabled', false);
        }

        if (! $enabled) {
            return false;
        }

        $webhookUrl = Setting::get('slack_webhook_url') ?? config('services.slack.webhook_url');
        return ! empty($webhookUrl);
    }

    public static function webhookUrl(): ?string
    {
        return Setting::get('slack_webhook_url') ?? config('services.slack.webhook_url');
    }
}
