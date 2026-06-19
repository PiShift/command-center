<?php

namespace App\Http\Controllers;

use App\Models\DaemonToken;
use App\Services\DaemonTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DaemonTokenController extends Controller
{
    public function store(Request $request, DaemonTokenService $service)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $generated = $service->generate();

        DaemonToken::create([
            'user_id'    => $request->user()->id,
            'token_hash' => $generated['hash'],
            'name'       => $data['name'],
        ]);

        return back()
            ->with('success', 'Daemon token generated.')
            ->with('daemon_token_raw', $generated['raw']);
    }

    public function destroy(Request $request, DaemonToken $token)
    {
        abort_unless($token->user_id === $request->user()->id, 403);

        Cache::forget('daemon_token:' . $token->token_hash);
        $token->delete();

        return back()->with('success', 'Daemon token revoked.');
    }
}