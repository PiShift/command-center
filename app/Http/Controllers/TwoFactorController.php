<?php

namespace App\Http\Controllers;

use App\Models\UserTwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

class TwoFactorController extends Controller
{
    private function tfa(): TwoFactorAuth
    {
        return new TwoFactorAuth(new QRServerProvider(), config('app.name'));
    }

    // ── Setup: show QR + manual entry ─────────────────────────────────────────

    public function setup(Request $request)
    {
        $tfa = $this->tfa();

        // Reuse the session secret so refreshing the page doesn't invalidate the QR
        $secret = $request->session()->get('2fa.pending_secret') ?? $tfa->createSecret();
        $request->session()->put('2fa.pending_secret', $secret);

        $user = $request->user();
        $qrUrl = $tfa->getQRText($user->email, $secret);

        return view('profile.2fa-setup', [
            'qrUrl'  => $qrUrl,
            'secret' => $secret,
        ]);
    }

    // ── Enable: validate confirmation code and save ───────────────────────────

    public function enable(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $secret = $request->session()->get('2fa.pending_secret');
        if (!$secret) {
            return redirect()->route('2fa.setup')->withErrors(['code' => 'Session expired. Please restart the setup process.']);
        }

        $tfa = $this->tfa();
        if (!$tfa->verifyCode($secret, $request->input('code'), 4)) {
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        $user = $request->user();
        $tf = UserTwoFactor::updateOrCreate(
            ['user_id' => $user->id],
            ['secret' => $secret, 'enabled' => true],
        );
        $request->session()->forget('2fa.pending_secret');

        $plainCodes = $tf->generateRecoveryCodes();

        return view('profile.2fa-recovery', ['codes' => $plainCodes]);
    }

    // ── Disable ───────────────────────────────────────────────────────────────

    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required'],
        ]);

        if (!Hash::check($request->input('password'), $request->user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user = $request->user();
        if ($user->requiresTwoFactor()) {
            return back()->withErrors(['password' => 'Two-factor authentication cannot be disabled for your role.']);
        }

        $user->twoFactor?->update(['enabled' => false]);

        // Revoke all trusted devices
        $user->devices()->update(['trusted' => false]);

        return back()->with('success', 'Two-factor authentication has been disabled.');
    }

    // ── Regenerate recovery codes ─────────────────────────────────────────────

    public function regenerateCodes(Request $request)
    {
        $request->validate([
            'password' => ['required'],
        ]);

        if (!Hash::check($request->input('password'), $request->user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user = $request->user();
        $tf = $user->twoFactor;
        if (!$tf || !$tf->enabled) {
            return back()->withErrors(['password' => 'Two-factor authentication is not enabled.']);
        }

        $plainCodes = $tf->generateRecoveryCodes();

        return view('profile.2fa-recovery', ['codes' => $plainCodes]);
    }
}
