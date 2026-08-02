<?php

namespace App\Http\Controllers;

use App\Models\PersonalAccessToken;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DaemonTokenController extends Controller
{
    public function store(Request $request, TokenService $service)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $generated = $service->generatePAT();

        PersonalAccessToken::create([
            'user_id'    => $request->user()->id,
            'name'       => $data['name'],
            'token_hash' => $generated['hash'],
            'token_prefix' => $generated['prefix'],
        ]);

        return back()
            ->with('success', 'Personal access token generated.')
            ->with('personal_access_token_raw', $generated['raw']);
    }

    public function destroy(Request $request, PersonalAccessToken $token)
    {
        abort_unless($token->user_id === $request->user()->id, 403);

        Cache::forget('pat:' . $token->token_hash);
        $token->update(['revoked' => true]);

        return back()->with('success', 'Personal access token revoked.');
    }
}