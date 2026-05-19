<x-layouts.app>
@section('title', 'Notification Settings')

<div class="max-w-2xl mx-auto py-8 px-4">
    <h1 style="font-size:22px;font-weight:700;color:#141413;margin-bottom:24px">Notification Settings</h1>

    @include('components.flash')

    <div class="rounded-xl p-6" style="background:#fff;border:1px solid #e5e4df;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
        <h2 style="font-size:15px;font-weight:600;color:#141413;margin-bottom:4px">Slack Integration</h2>
        <p style="font-size:13px;color:#5c5c5a;margin-bottom:20px">Configure a Slack incoming webhook to receive team notifications in Slack.</p>

        <form method="POST" action="{{ route('settings.notifications.update') }}" class="flex flex-col gap-4">
            @csrf @method('PATCH')

            <div>
                <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;display:block;margin-bottom:4px">Slack Webhook URL</label>
                <input type="url" name="slack_webhook_url"
                       value="{{ old('slack_webhook_url', $slackWebhookUrl) }}"
                       placeholder="https://hooks.slack.com/services/..."
                       class="w-full rounded-lg text-[13px] px-3 py-2"
                       style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;outline:none"
                       onfocus="this.style.borderColor='#D97757';this.style.background='#fff'"
                       onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">
                @error('slack_webhook_url')<p style="font-size:11px;color:#b94040;margin-top:3px">{{ $message }}</p>@enderror
            </div>

            <div x-data="{ enabled: {{ $slackEnabled ? 'true' : 'false' }} }">
                <label class="flex items-center gap-3" style="cursor:pointer">
                    <div @click="enabled = !enabled" class="relative"
                         style="width:36px;height:20px;border-radius:20px;transition:background 200ms ease;cursor:pointer"
                         :style="enabled ? 'background:#D97757' : 'background:#e5e4df'">
                        <div class="absolute top-0.5 rounded-full transition-transform"
                             style="width:16px;height:16px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.2)"
                             :style="enabled ? 'transform:translateX(18px)' : 'transform:translateX(2px)'"></div>
                        <input type="hidden" name="slack_enabled" :value="enabled ? '1' : '0'">
                    </div>
                    <span style="font-size:13px;color:#141413">Enable Slack notifications</span>
                </label>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        style="padding:8px 20px;font-size:13px;font-weight:500;background:#D97757;color:#fff;border:none;border-radius:8px;cursor:pointer;transition:background 150ms ease"
                        onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">Save Settings</button>
            </div>
        </form>

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
    </div>
</div>
</x-layouts.app>
