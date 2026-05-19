<?php

namespace App\Http\Controllers;

use App\Models\UserTwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;

class TwoFactorController extends Controller
{
    private function tfa(): TwoFactorAuth
    {
        return new TwoFactorAuth(new BaconQrCodeProvider(4, '#ffffff', '#000000', 'svg'), config('app.name'));
    }

    // ── Setup: show QR + manual entry ─────────────────────────────────────────

    public function setup(Request $request)
    {
        $tfa = $this->tfa();

        // If returning from a failed verify, reuse the same secret so the QR stays valid
        $secret = null;
        if ($request->old('encrypted_secret')) {
            try {
                $secret = decrypt($request->old('encrypted_secret'));
            } catch (\Exception) {}
        }

        if (!$secret) {
            $secret = $tfa->createSecret();
        }

        $user       = $request->user();
        $qrDataUri  = $tfa->getQRCodeImageAsDataUri($user->email, $secret, 200);

        return view('profile.2fa-setup', [
            'qrDataUri'       => $qrDataUri,
            'secret'          => $secret,
            'encryptedSecret' => encrypt($secret),
        ]);
    }

    // ── Enable: validate confirmation code and save ───────────────────────────

    public function enable(Request $request)
    {
        $code = preg_replace('/\D/', '', $request->input('code', ''));

        if (strlen($code) !== 6) {
            return back()->withErrors(['code' => 'Please enter the 6-digit code from your authenticator app.']);
        }

        try {
            $secret = decrypt($request->input('encrypted_secret', ''));
        } catch (\Exception) {
            return redirect()->route('2fa.setup')->withErrors(['code' => 'Session expired. Please restart setup.']);
        }

        $tfa = $this->tfa();
        if (!$tfa->verifyCode($secret, $code, 4)) {
            return back()
                ->withInput(['encrypted_secret' => $request->input('encrypted_secret')])
                ->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        $user = $request->user();
        $tf = UserTwoFactor::updateOrCreate(
            ['user_id' => $user->id],
            ['secret' => $secret, 'enabled' => true],
        );

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

    // ── Temporary diagnostic (remove after debugging) ─────────────────────────

    public function debug(Request $request)
    {
        $tfa    = $this->tfa();
        $user   = $request->user();
        $tf     = $user->twoFactor;
        $output = [];

        $output['server_time']     = now()->toDateTimeString();
        $output['server_timezone'] = config('app.timezone');
        $output['unix_timestamp']  = time();

        if ($tf && $tf->enabled) {
            $secret = $tf->secret; // model decrypts automatically
            $output['stored_secret_length'] = strlen($secret);
            $output['current_code']         = $tfa->getCode($secret);
            $output['verify_self']          = $tfa->verifyCode($secret, $tfa->getCode($secret), 4) ? 'PASS' : 'FAIL';
        } else {
            $output['totp'] = '2FA not enabled for this user';
        }

        return response()->json($output);
    }
}
