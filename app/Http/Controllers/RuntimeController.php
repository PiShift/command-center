<?php

namespace App\Http\Controllers;

use App\Models\AgentRuntime;
use Illuminate\View\View;

class RuntimeController extends Controller
{
    /**
     * Display a listing of all agent runtimes.
     */
    public function index(): View
    {
        $runtimes = AgentRuntime::with(['user', 'agents'])
            ->orderByRaw("CASE WHEN status = 'online' THEN 0 WHEN status = 'offline' THEN 1 ELSE 2 END")
            ->orderByDesc('last_seen_at')
            ->get();

        return view('runtimes.index', [
            'runtimes' => $runtimes,
        ]);
    }
}
