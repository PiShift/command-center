<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginOtpMail;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserLoginHistory;
use App\Models\UserPendingVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    // ── Helpers ──────────────────────────────────────────────────────────────

    private function parseUserAgent(string $ua): array
    {
        $browser = 'Unknown';
        $platform = 'Unknown';

        // Platform
        if (str_contains($ua, 'Windows')) $platform = 'Windows';
        elseif (str_contains($ua, 'Mac')) $platform = 'macOS';
        elseif (str_contains($ua, 'Linux')) $platform = 'Linux';
        elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $platform = 'iOS';
        elseif (str_contains($ua, 'Android')) $platform = 'Android';

        // Browser
        if (str_contains($ua, 'Edg/')) $browser = 'Edge';
        elseif (str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')) $browser = 'Opera';
        elseif (str_contains($ua, 'Chrome')) $browser = 'Chrome';
        elseif (str_contains($ua, 'Firefox')) $browser = 'Firefox';
        elseif (str_contains($ua, 'Safari')) $browser = 'Safari';

        return compact('browser', 'platform');
    }

    private function recordLogin(int $userId, string $status, array $device, ?string $ip): void
    {
        UserLoginHistory::create([
            'user_id'    => $userId,
            'ip_address' => $ip,
            'device'     => ($device['browser'] ?? 'Unknown') . ' on ' . ($device['platform'] ?? 'Unknown'),
            'browser'    => $device['browser'] ?? null,
            'platform'   => $device['platform'] ?? null,
            'status'     => $status,
        ]);
    }

    // ── Step 1: Password verification ─────────────────────────────────────────

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();
        $ua = $request->header('User-Agent', '');
        $device = $this->parseUserAgent($ua);
        $ip = $request->ip();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            if ($user) {
                $this->recordLogin($user->id, 'failed', $device, $ip);
            }
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        // Check for trusted device cookie
        $cookieToken = $request->cookie('device_token');
        if ($cookieToken) {
            $knownDevice = UserDevice::findByToken($user->id, $cookieToken);
            if ($knownDevice && $knownDevice->trusted) {
                $knownDevice->update(['last_used_at' => now()]);
                $this->recordLogin($user->id, 'success', $device, $ip);
                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'));
            }
        }

        // Store pending state and redirect to verify
        $request->session()->put('auth.pending_user_id', $user->id);
        $request->session()->put('auth.pending_device', array_merge($device, ['ip' => $ip]));

        return redirect()->route('login.verify');
    }

    // ── Step 2: Verification screen ───────────────────────────────────────────

    public function showVerify(Request $request)
    {
        $userId = $request->session()->get('auth.pending_user_id');
        abort_if(!$userId, 403);

        $user = User::with('twoFactor')->findOrFail($userId);

        // In local env, skip verification entirely
        if (app()->isLocal()) {
            $pendingDevice = $request->session()->get('auth.pending_device', []);
            $this->recordLogin($user->id, 'success', $pendingDevice, $pendingDevice['ip'] ?? null);
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->forget(['auth.pending_user_id', 'auth.pending_device']);
            return redirect()->intended(route('dashboard'));
        }

        // Admin/manager with no 2FA set up — log them in and let the middleware
        // force them to /profile/2fa/setup. Don't block here or they can never set it up.
        if ($user->requiresTwoFactor() && !$user->isTwoFactorEnabled()) {
            $pendingDevice = $request->session()->get('auth.pending_device', []);
            $this->recordLogin($user->id, 'success', $pendingDevice, $pendingDevice['ip'] ?? null);
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->forget(['auth.pending_user_id', 'auth.pending_device']);
            return redirect()->route('2fa.setup')
                ->with('warning', 'Your role requires two-factor authentication. Please set it up now.');
        }

        if ($user->isTwoFactorEnabled()) {
            return view('auth.verify', ['mode' => 'totp']);
        }

        // OTP flow — generate and send
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        UserPendingVerification::where('user_id', $userId)->where('type', 'otp')->update(['used' => true]);
        UserPendingVerification::create([
            'user_id'    => $userId,
            'type'       => 'otp',
            'code'       => hash('sha256', $code),
            'expires_at' => now()->addMinutes(10),
        ]);
        Mail::to($user->email)->send(new LoginOtpMail($code, $user->name));

        return view('auth.verify', ['mode' => 'otp', 'email' => $user->email]);
    }

    // ── Step 3: Verify submission ─────────────────────────────────────────────

    public function verify(Request $request)
    {
        $userId = $request->session()->get('auth.pending_user_id');
        abort_if(!$userId, 403);

        $key = 'verify:' . $userId;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $request->session()->forget(['auth.pending_user_id', 'auth.pending_device']);
            return redirect()->route('login')->withErrors(['email' => 'Too many attempts. Please sign in again.']);
        }

        $user = User::with('twoFactor')->findOrFail($userId);
        $mode = $request->input('mode', 'otp');
        $code = $request->input('code', '');
        $recoveryCode = $request->input('recovery_code', '');
        $verified = false;

        if ($mode === 'totp' && $user->isTwoFactorEnabled()) {
            $tfa = new \RobThree\Auth\TwoFactorAuth(new \RobThree\Auth\Providers\Qr\QRServerProvider(), config('app.name'));
            $verified = $tfa->verifyCode($user->twoFactor->secret, $code, 4);
        } elseif ($mode === 'recovery' && $user->isTwoFactorEnabled()) {
            $hashed = hash('sha256', strtoupper($recoveryCode));
            $codes = $user->twoFactor->recovery_codes ?? [];
            if (in_array($hashed, $codes)) {
                $user->twoFactor->update([
                    'recovery_codes' => array_values(array_filter($codes, fn($c) => $c !== $hashed)),
                ]);
                $verified = true;
            }
        } else {
            // OTP
            $pending = UserPendingVerification::where('user_id', $userId)
                ->where('type', 'otp')
                ->valid()
                ->latest()
                ->first();
            if ($pending && hash('sha256', $code) === $pending->code) {
                $pending->update(['used' => true]);
                $verified = true;
            }
        }

        if (!$verified) {
            RateLimiter::hit($key, 600);
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        RateLimiter::clear($key);

        $pendingDevice = $request->session()->get('auth.pending_device', []);
        $this->recordLogin($user->id, 'success', $pendingDevice, $pendingDevice['ip'] ?? null);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(['auth.pending_user_id', 'auth.pending_device']);

        // Create device record
        $plainToken = Str::random(64);
        $trusted = $request->boolean('trust_device');
        UserDevice::create([
            'user_id'      => $user->id,
            'name'         => ($pendingDevice['browser'] ?? 'Unknown') . ' on ' . ($pendingDevice['platform'] ?? 'Unknown'),
            'browser'      => $pendingDevice['browser'] ?? null,
            'platform'     => $pendingDevice['platform'] ?? null,
            'ip_address'   => $pendingDevice['ip'] ?? null,
            'token'        => hash('sha256', $plainToken),
            'trusted'      => $trusted,
            'last_used_at' => now(),
        ]);

        $response = redirect()->intended(route('dashboard'));
        if ($trusted) {
            $response->withCookie(cookie('device_token', $plainToken, 60 * 24 * 30, '/', null, true, true, false, 'strict'));
        }

        return $response;
    }

    // ── Resend OTP ────────────────────────────────────────────────────────────

    public function resendOtp(Request $request)
    {
        $userId = $request->session()->get('auth.pending_user_id');
        abort_if(!$userId, 403);

        $key = 'resend-otp:' . $userId;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->withErrors(['code' => 'Please wait a minute before requesting a new code.']);
        }
        RateLimiter::hit($key, 60);

        $user = User::findOrFail($userId);
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        UserPendingVerification::where('user_id', $userId)->where('type', 'otp')->update(['used' => true]);
        UserPendingVerification::create([
            'user_id'    => $userId,
            'type'       => 'otp',
            'code'       => hash('sha256', $code),
            'expires_at' => now()->addMinutes(10),
        ]);
        Mail::to($user->email)->send(new LoginOtpMail($code, $user->name));

        return back()->with('resent', true);
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
