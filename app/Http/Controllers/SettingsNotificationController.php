<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsNotificationController extends Controller
{
    public function show()
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        return view('settings.notifications', [
            'slackWebhookUrl' => Setting::get('slack_webhook_url', ''),
            'slackEnabled'    => (bool) Setting::get('slack_enabled', false),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        $data = $request->validate([
            'slack_webhook_url' => 'nullable|url|max:500',
            'slack_enabled'     => 'boolean',
        ]);

        Setting::set('slack_webhook_url', $data['slack_webhook_url'] ?? '');
        Setting::set('slack_enabled', $request->boolean('slack_enabled') ? '1' : '0');

        return back()->with('success', 'Notification settings saved.');
    }

    public function testSlack()
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        $webhookUrl = Setting::get('slack_webhook_url') ?? config('services.slack.webhook_url');

        if (empty($webhookUrl)) {
            return back()->with('error', 'No Slack webhook URL configured.');
        }

        try {
            $response = \Illuminate\Support\Facades\Http::post($webhookUrl, [
                'text' => '✅ PiShift Command Center — Slack integration is working!',
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
