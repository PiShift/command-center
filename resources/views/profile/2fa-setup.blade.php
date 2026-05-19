<x-layouts.auth>
    <div style="background:#fff;border:1px solid #e5e4df;border-radius:16px;padding:40px 36px;width:100%;max-width:440px">
        <h1 style="margin:0 0 6px;font-size:22px;font-weight:700;color:#141413">Set up two-factor authentication</h1>
        <p style="margin:0 0 28px;font-size:13px;color:#5c5c5a;line-height:1.5">
            Scan the QR code below with your authenticator app (e.g. Google Authenticator, Authy), then enter the 6-digit code to confirm.
        </p>

        {{-- QR Code (generated server-side) --}}
        <div style="text-align:center;margin-bottom:24px">
            <img src="{{ $qrDataUri }}" alt="QR Code" style="border:1px solid #e5e4df;border-radius:12px;padding:8px;background:#fff">
        </div>

        {{-- Manual entry --}}
        <div style="background:#F5F4EF;border:1px solid #e5e4df;border-radius:10px;padding:14px 16px;margin-bottom:24px">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:#8c8c8a;text-transform:uppercase;letter-spacing:.05em">Manual entry key</p>
            <p style="margin:0;font-size:15px;font-weight:600;color:#141413;letter-spacing:.15em;word-break:break-all">{{ $secret }}</p>
        </div>

        @if($errors->has('code'))
            <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#dc2626">
                {{ $errors->first('code') }}
            </div>
        @endif

        <form method="POST" action="{{ route('2fa.enable') }}">
            @csrf
            <div style="margin-bottom:20px">
                <label style="display:block;font-size:12px;font-weight:600;color:#5c5c5a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Confirmation Code</label>
                <input
                    type="text"
                    name="code"
                    maxlength="6"
                    placeholder="000000"
                    autofocus
                    autocomplete="one-time-code"
                    style="width:100%;padding:12px 14px;font-size:24px;letter-spacing:.25em;text-align:center;border:1px solid #e5e4df;border-radius:10px;background:#F5F4EF;color:#141413;outline:none;box-sizing:border-box"
                >
            </div>
            <button type="submit" style="width:100%;padding:11px 20px;background:#D97757;color:#fff;font-size:14px;font-weight:600;border:none;border-radius:10px;cursor:pointer">
                Enable two-factor authentication
            </button>
        </form>

        <div style="margin-top:16px;text-align:center">
            <a href="{{ route('profile.show') }}" style="font-size:13px;color:#8c8c8a;text-decoration:none">Cancel</a>
        </div>
    </div>
</x-layouts.auth>
