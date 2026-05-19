<x-layouts.app>
@section('title', 'Notification Settings')

<div class="max-w-2xl mx-auto py-8 px-4">
    <h1 style="font-size:22px;font-weight:700;color:#141413;margin-bottom:24px">Notification Settings</h1>

    @include('components.flash')

    <div class="rounded-xl p-6" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
        <h2 style="font-size:15px;font-weight:600;color:#141413;margin-bottom:4px">Slack Integration</h2>
        <p style="font-size:13px;color:#5c5c5a;margin-bottom:20px">Slack notifications are configured via environment variables on the server.</p>

        <div class="flex flex-col gap-3">
            <div class="flex items-center gap-3 p-3 rounded-lg" style="background:#F5F4EF">
                <div class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $slackWebhookConfigured ? '#22a559' : '#e5e4df' }}"></div>
                <div>
                    <p style="font-size:13px;font-weight:500;color:#141413;margin:0">Webhook URL</p>
                    <p style="font-size:12px;color:#5c5c5a;margin:1px 0 0">
                        {{ $slackWebhookConfigured ? 'Configured via SLACK_WEBHOOK_URL' : 'Not set — add SLACK_WEBHOOK_URL to your .env' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3 rounded-lg" style="background:#F5F4EF">
                <div class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $slackEnabled ? '#22a559' : '#e5e4df' }}"></div>
                <div>
                    <p style="font-size:13px;font-weight:500;color:#141413;margin:0">Status</p>
                    <p style="font-size:12px;color:#5c5c5a;margin:1px 0 0">
                        {{ $slackEnabled ? 'Enabled via SLACK_NOTIFICATIONS_ENABLED=true' : 'Disabled — set SLACK_NOTIFICATIONS_ENABLED=true to enable' }}
                    </p>
                </div>
            </div>
        </div>

        @if($slackWebhookConfigured)
        <hr style="border:none;border-top:1px solid #e5e4df;margin:20px 0">
        <div>
            <p style="font-size:13px;font-weight:600;color:#141413;margin-bottom:8px">Test Connection</p>
            <p style="font-size:13px;color:#5c5c5a;margin-bottom:12px">Send a test message to verify your Slack webhook is working.</p>
            <form method="POST" action="{{ route('settings.notifications.test-slack') }}">
                @csrf
                <button type="submit"
                        style="padding:8px 16px;font-size:13px;font-weight:500;background:#F5F4EF;border:1px solid #e5e4df;color:#141413;border-radius:8px;cursor:pointer;transition:background 150ms ease"
                        onmouseover="this.style.background='#eeeee9'" onmouseout="this.style.background='#F5F4EF'">Send Test Message</button>
            </form>
        </div>
        @endif
    </div>
</div>
</x-layouts.app>
