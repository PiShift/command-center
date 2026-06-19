<x-layouts.auth>
<div style="width:100%;max-width:380px;margin:0 auto">

    {{-- Logo --}}
    <div style="display:flex;justify-content:center;margin-bottom:32px">
        <img src="/images/logo.svg" alt="PiShift" style="height:36px;width:auto">
    </div>

    {{-- Card --}}
    <div style="background:#fff;border:1px solid #e5e4df;border-radius:14px;box-shadow:0 1px 3px rgba(20,20,19,0.04);padding:32px">

        <h1 style="font-size:18px;font-weight:600;color:#141413;margin:0 0 4px 0;line-height:1.2">Sign in</h1>
        <p style="font-size:13px;color:#8c8c8a;margin:0 0 24px 0">Welcome back to your workspace.</p>

        {{-- Error banner --}}
        @if($errors->any())
        <div style="padding:10px 12px;margin-bottom:20px;background:#fff8f8;border:1px solid #ffd0d0;border-radius:8px;font-size:12px;color:#b94040">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" style="display:flex;flex-direction:column;gap:16px">
            @csrf
            <input type="hidden" name="cli_callback" value="{{ old('cli_callback', session('auth.cli_callback')) }}">
            <input type="hidden" name="cli_state" value="{{ old('cli_state', session('auth.cli_state')) }}">

            {{-- Email --}}
            <div>
                <label for="email" style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;margin-bottom:5px">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       placeholder="you@example.com"
                       style="width:100%;box-sizing:border-box;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;padding:9px 12px;font-size:14px;color:#141413;outline:none;transition:border-color 150ms ease,background 150ms ease"
                       onfocus="this.style.borderColor='#D97757';this.style.background='#fff'"
                       onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">
            </div>

            {{-- Password --}}
            <div>
                <label for="password" style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#8c8c8a;margin-bottom:5px">Password</label>
                <input id="password" name="password" type="password" required
                       placeholder="••••••••"
                       style="width:100%;box-sizing:border-box;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;padding:9px 12px;font-size:14px;color:#141413;outline:none;transition:border-color 150ms ease,background 150ms ease"
                       onfocus="this.style.borderColor='#D97757';this.style.background='#fff'"
                       onblur="this.style.borderColor='#e5e4df';this.style.background='#F5F4EF'">
            </div>

            {{-- Remember me --}}
            <div style="display:flex;align-items:center;gap:8px">
                <input id="remember" name="remember" type="checkbox"
                       style="width:15px;height:15px;border-radius:4px;border:1px solid #e5e4df;accent-color:#D97757;cursor:pointer;flex-shrink:0">
                <label for="remember" style="font-size:13px;color:#5c5c5a;cursor:pointer">Remember me</label>
            </div>

            {{-- Submit --}}
            <button type="submit"
                    style="width:100%;padding:10px 16px;background:#D97757;border:none;border-radius:8px;font-size:13px;font-weight:500;color:#fff;cursor:pointer;transition:background 150ms ease;margin-top:4px"
                    onmouseover="this.style.background='#c4684a'" onmouseout="this.style.background='#D97757'">
                Sign in
            </button>
        </form>
    </div>

    <p style="text-align:center;font-size:12px;color:#8c8c8a;margin-top:20px">PiShift &copy; {{ date('Y') }}</p>
</div>
</x-layouts.auth>
