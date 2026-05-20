<?php

namespace App\Http\Controllers;

use App\Notifications\Helpers\SlackNotificationHelper;
use App\Notifications\TestSlackNotification;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Slack\SlackRoute;
use Illuminate\Support\Facades\Notification;

class SettingsNotificationController extends Controller
{
    public function show()
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        return view('settings.notifications', [
            'slackConfigured' => SlackNotificationHelper::isEnabled(),
            'slackEnabled'    => config('services.slack.enabled', false),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        // Slack is configured via environment variables — nothing to save here yet.
        return back()->with('success', 'Settings saved.');
    }

    public function testSlack()
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        if (! SlackNotificationHelper::isEnabled()) {
            return back()->with('error', 'Slack is not configured. Set SLACK_BOT_TOKEN and SLACK_DEFAULT_CHANNEL in your environment.');
        }

        try {
            Notification::route('slack', SlackRoute::make(
                SlackNotificationHelper::defaultChannel(),
                SlackNotificationHelper::botToken(),
            ))->notify(new TestSlackNotification());

            return back()->with('success', 'Test message sent to Slack successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send test message: ' . $e->getMessage());
        }
    }
}
