<x-layouts.auth>
    <div style="background:#fff;border:1px solid #e5e4df;border-radius:16px;padding:40px 36px;width:100%;max-width:420px">

        {{-- Blocked --}}
        @if($mode === 'blocked')
            <div style="text-align:center;margin-bottom:24px">
                <div style="width:48px;height:48px;border-radius:50%;background:#fdf3ee;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px">
                    @include('components.icon', ['name' => 'lock', 'class' => 'w-5 h-5', 'style' => 'color:#D97757'])
                </div>
                <h1 style="margin:0 0 8px;font-size:20px;font-weight:700;color:#141413">Access Restricted</h1>
                <p style="margin:0;font-size:14px;color:#5c5c5a;line-height:1.5">{{ $message }}</p>
            </div>
            <a href="{{ route('login') }}" style="display:block;text-align:center;font-size:13px;color:#D97757;text-decoration:none;margin-top:16px">
                ← Back to sign in
            </a>
        @else

        {{-- Header --}}
        <div style="margin-bottom:28px">
            <h1 style="margin:0 0 6px;font-size:22px;font-weight:700;color:#141413">Verify your identity</h1>
            @if($mode === 'otp')
                <p style="margin:0;font-size:13px;color:#5c5c5a">
                    We sent a 6-digit code to <strong>{{ $email ?? '' }}</strong>
                </p>
            @elseif($mode === 'totp')
                <p style="margin:0;font-size:13px;color:#5c5c5a">
                    Enter the code from your authenticator app.
                </p>
            @endif
        </div>

        {{-- Alerts --}}
        @if(session('resent'))
            <div style="background:#fdf3ee;border:1px solid #D97757;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#D97757">
                A new code has been sent to your email.
            </div>
        @endif

        @if($errors->has('code'))
            <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#dc2626">
                {{ $errors->first('code') }}
            </div>
        @endif

        {{-- TOTP / OTP Form --}}
        @php
            $showRecovery = ($panel ?? null) === 'recovery';
        @endphp

        @if(! $showRecovery)
                <form method="POST" action="{{ route('login.verify.submit') }}">
                    @csrf
                    <input type="hidden" name="mode" value="{{ $mode === 'totp' ? 'totp' : 'otp' }}">

                    <div style="margin-bottom:20px">
                        <input
                            type="text"
                            name="code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            minlength="6"
                            pattern="\d{6}"
                            placeholder="000000"
                            required
                            autofocus
                            style="width:100%;padding:14px 16px;font-size:28px;letter-spacing:0.25em;text-align:center;border:1px solid #e5e4df;border-radius:10px;background:#F5F4EF;color:#141413;outline:none;box-sizing:border-box"
                        >
                    </div>

                    <div style="margin-bottom:20px">
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#5c5c5a;cursor:pointer">
                            <input type="checkbox" name="trust_device" value="1" style="accent-color:#D97757">
                            Trust this device for 30 days
                        </label>
                    </div>

                    <button type="submit" style="width:100%;padding:11px 20px;background:#D97757;color:#fff;font-size:14px;font-weight:600;border:none;border-radius:10px;cursor:pointer">
                        Verify & Sign in
                    </button>
                </form>

                {{-- Recovery code link (TOTP only) --}}
                @if($mode === 'totp')
                    <a href="{{ route('login.verify', ['panel' => 'recovery']) }}" style="display:block;width:100%;text-align:center;margin-top:16px;font-size:13px;color:#D97757;text-decoration:none;cursor:pointer">
                        Use a recovery code instead →
                    </a>
                @endif

                {{-- Resend link (OTP only) --}}
                @if($mode === 'otp')
                    <form method="POST" action="{{ route('login.resend-otp') }}" style="margin-top:16px;text-align:center">
                        @csrf
                        <button type="submit" style="background:none;border:none;font-size:13px;color:#D97757;cursor:pointer">
                            Resend code
                        </button>
                    </form>
                @endif
        @else
            {{-- Recovery code form --}}
                <form method="POST" action="{{ route('login.verify.submit') }}">
                    @csrf
                    <input type="hidden" name="mode" value="recovery">

                    <div style="margin-bottom:8px">
                        <label style="display:block;font-size:12px;font-weight:600;color:#5c5c5a;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Recovery Code</label>
                        <input
                            type="text"
                            name="recovery_code"
                            placeholder="XXXXXXXXXX"
                            style="width:100%;padding:10px 14px;font-size:15px;letter-spacing:.1em;border:1px solid #e5e4df;border-radius:10px;background:#F5F4EF;color:#141413;outline:none;box-sizing:border-box"
                        >
                    </div>

                    <div style="margin-bottom:20px">
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#5c5c5a;cursor:pointer">
                            <input type="checkbox" name="trust_device" value="1" style="accent-color:#D97757">
                            Trust this device for 30 days
                        </label>
                    </div>

                    <button type="submit" style="width:100%;padding:11px 20px;background:#D97757;color:#fff;font-size:14px;font-weight:600;border:none;border-radius:10px;cursor:pointer">
                        Verify with recovery code
                    </button>
                </form>

                <a href="{{ route('login.verify') }}" style="display:block;width:100%;text-align:center;margin-top:16px;font-size:13px;color:#D97757;text-decoration:none;cursor:pointer">
                    ← Use authenticator app
                </a>
        @endif

        <div style="margin-top:20px;text-align:center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="background:none;border:none;font-size:13px;color:#8c8c8a;cursor:pointer">
                    Cancel — back to sign in
                </button>
            </form>
        </div>

        @endif
    </div>
</x-layouts.auth>
