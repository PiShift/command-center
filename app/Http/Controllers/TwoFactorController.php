<?php

namespace App\Http\Controllers;

use App\Models\UserTwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    // ── Setup: show QR + manual entry ─────────────────────────────────────────

    public function setup(Request $request)
    {
        $google2fa = new Google2FA();

        // Generate a new secret and hold it in session until confirmed
        $secret = $google2fa->generateSecretKey();
        $request->session()->put('2fa.pending_secret', $secret);

        $user = $request->user();
        $qrUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

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

        $google2fa = new Google2FA();
        if (!$google2fa->verifyKey($secret, $request->input('code'))) {
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
