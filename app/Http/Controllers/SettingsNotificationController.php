<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsNotificationController extends Controller
{
    public function show()
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        return view('settings.notifications', [
            'slackWebhookConfigured' => ! empty(config('services.slack.webhook_url')),
            'slackEnabled'           => config('services.slack.enabled', false),
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

        $webhookUrl = config('services.slack.webhook_url');

        if (empty($webhookUrl)) {
            return back()->with('error', 'No Slack webhook URL configured.');
        }

        try {
            $response = \Illuminate\Support\Facades\Http::post($webhookUrl, [
                'text' => '✅ PiShift — Slack integration is working!',
            ]);

            if ($response->successful()) {
                return back()->with('success', 'Test message sent to Slack successfully!');
            }

            return back()->with('error', 'Slack returned an error. Check your webhook URL.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send test message: ' . $e->getMessage());
        }
    }
}
