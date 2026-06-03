<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __construct(private readonly GlobalSearchService $search) {}

    public function search(Request $request): JsonResponse
    {
        $query = trim($request->string('q'));

        if (mb_strlen($query) < 2) {
            return response()->json([
                'projects'  => [],
                'tasks'     => [],
                'customers' => [],
                'invoices'  => [],
                'sprints'   => [],
            ]);
        }

        $results = $this->search->search(auth()->user(), $query);

        // Persist to recent searches (deduplicated, max 5, newest first)
        $recent = collect(session('recent_searches', []))
            ->prepend($query)
            ->unique()
            ->values()
            ->take(5)
            ->all();
        session(['recent_searches' => $recent]);

        return response()->json($results);
    }
}
