<x-layouts.auth>
    <div style="background:#fff;border:1px solid #e5e4df;border-radius:16px;padding:40px 36px;width:100%;max-width:480px">
        <div style="margin-bottom:24px">
            <div style="width:48px;height:48px;border-radius:50%;background:#fdf3ee;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px">
                @include('components.icon', ['name' => 'lock', 'class' => 'w-5 h-5', 'style' => 'color:#D97757'])
            </div>
            <h1 style="margin:0 0 6px;font-size:22px;font-weight:700;color:#141413">Save your recovery codes</h1>
            <p style="margin:0;font-size:13px;color:#5c5c5a;line-height:1.5">
                Store these codes somewhere safe. Each code can only be used once.
                <strong>They will not be shown again.</strong>
            </p>
        </div>

        <div style="background:#F5F4EF;border:1px solid #e5e4df;border-radius:12px;padding:20px;margin-bottom:24px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                @foreach($codes as $code)
                    <div style="font-family:monospace;font-size:14px;font-weight:600;letter-spacing:.1em;color:#141413;background:#fff;border:1px solid #e5e4df;border-radius:8px;padding:8px 12px;text-align:center">
                        {{ $code }}
                    </div>
                @endforeach
            </div>
        </div>

        <a href="{{ route('profile.show') }}" style="display:block;width:100%;padding:11px 20px;background:#D97757;color:#fff;font-size:14px;font-weight:600;border-radius:10px;text-align:center;text-decoration:none;box-sizing:border-box">
            I've saved my codes — go to profile
        </a>
    </div>
</x-layouts.auth>
