<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PinController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_type' => ['required', 'string', 'max:100'],
            'item_id' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer'],
        ]);

        return response()->json([
            'id' => (string) Str::uuid(),
            'workspace_id' => '1',
            'user_id' => (string) $request->user()->id,
            'item_type' => $data['item_type'],
            'item_id' => $data['item_id'],
            'position' => (int) ($data['position'] ?? 0),
            'created_at' => now()->toIso8601String(),
        ]);
    }

    public function destroy(string $itemType, string $itemId): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function reorder(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
