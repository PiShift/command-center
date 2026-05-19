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

    // ── Setup: generate + store secret immediately, show QR ──────────────────

    public function setup(Request $request)
    {
        $tfa  = $this->tfa();
        $user = $request->user();

        // Reuse existing pending secret (enabled=false) so the QR stays stable
        // across page refreshes or failed verifications
        $tf = UserTwoFactor::firstOrNew(['user_id' => $user->id]);

        if (!$tf->exists || $tf->enabled) {
            // Fresh setup or re-setup after having 2FA enabled previously
            $tf->user_id = $user->id;
            $tf->secret  = $tfa->createSecret();
            $tf->enabled = false;
            $tf->save();
        }

        $qrDataUri = $tfa->getQRCodeImageAsDataUri($user->email, $tf->secret, 200);

        return view('profile.2fa-setup', [
            'qrDataUri' => $qrDataUri,
            'secret'    => $tf->secret,
        ]);
    }

    // ── Enable: verify code against DB-stored secret ──────────────────────────

    public function enable(Request $request)
    {
        $code = preg_replace('/\D/', '', $request->input('code', ''));

        if (strlen($code) !== 6) {
            return back()->withErrors(['code' => 'Please enter the 6-digit code from your authenticator app.']);
        }

        $user = $request->user();
        $tf   = UserTwoFactor::where('user_id', $user->id)->first();

        if (!$tf || !$tf->secret) {
            return redirect()->route('2fa.setup')->withErrors(['code' => 'Setup session expired. Please scan the QR code again.']);
        }

        $tfa = $this->tfa();

        if (!$tfa->verifyCode($tf->secret, $code, 8)) {
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        $tf->enabled = true;
        $tf->save();

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
        $tf     = UserTwoFactor::where('user_id', $user->id)->first();
        $output = [];

        $output['server_time']         = now()->toDateTimeString();
        $output['server_timezone']      = config('app.timezone');
        $output['unix_timestamp']       = time();
        $output['seconds_into_period']  = time() % 30;
        $output['seconds_remaining']    = 30 - (time() % 30);

        if ($tf && $tf->secret) {
            $secret = $tf->secret;
            $output['secret_source']  = $tf->enabled ? 'DB (enabled)' : 'DB (pending setup)';
            $output['secret_length']  = strlen($secret);
            $output['current_code']   = $tfa->getCode($secret);
            $output['verify_self']    = $tfa->verifyCode($secret, $tfa->getCode($secret), 8) ? 'PASS' : 'FAIL';

            $now    = time();
            $window = [];
            for ($i = -2; $i <= 2; $i++) {
                $period   = (int)(($now + $i * 30) / 30);
                $window[] = ['offset' => ($i * 30) . 's', 'code' => $tfa->getCode($secret, $period)];
            }
            $output['code_window'] = $window;
        } else {
            $output['hint'] = 'No secret found. Go to /profile/2fa/setup first.';
        }

        return response()->json($output);
    }
}
