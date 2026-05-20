<?php

namespace App\Notifications\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public static function send(string $message): void
    {
        if (! self::isEnabled()) {
            return;
        }

        try {
            Http::withJson(['text' => $message])->post(self::webhookUrl());
        } catch (\Throwable $e) {
            Log::error('Slack notification failed', ['error' => $e->getMessage()]);
        }
    }
}
