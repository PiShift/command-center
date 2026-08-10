<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SqlReadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:20000'],
        ]);

        $query = trim($data['query']);

        if ($query === '') {
            return response()->json([
                'message' => 'Query must not be empty.',
            ], 422);
        }

        // Support a single optional trailing semicolon while blocking stacked statements.
        if (Str::substrCount($query, ';') > 1 || (Str::contains($query, ';') && ! preg_match('/;\s*$/', $query))) {
            return response()->json([
                'message' => 'Only a single SQL statement is allowed.',
            ], 422);
        }

        $normalized = rtrim($query, "; \t\n\r\0\x0B");

        if (! preg_match('/^\s*(select|with)\b/i', $normalized)) {
            return response()->json([
                'message' => 'Only read-only SELECT queries are allowed.',
            ], 422);
        }

        if (preg_match('/(--|\/\*|\*\/)/', $normalized)) {
            return response()->json([
                'message' => 'SQL comments are not allowed.',
            ], 422);
        }

        if (preg_match('/\b(insert|update|delete|alter|drop|create|truncate|replace|merge|grant|revoke|commit|rollback|savepoint|lock|unlock|call|execute|exec|set|use)\b/i', $normalized)) {
            return response()->json([
                'message' => 'Only read-only SELECT queries are allowed.',
            ], 422);
        }

        try {
            $rows = DB::select($normalized);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'SQL execution failed: '.$exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'row_count' => count($rows),
            'rows' => $rows,
        ]);
    }
}
