<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class LabelController
{
    public function index(): JsonResponse
    {
        return response()->json(['labels' => [], 'total' => 0]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['error' => 'not found'], 404);
    }

    public function store(): JsonResponse
    {
        return response()->json([
            'id' => '',
            'name' => '',
            'color' => '',
            'workspace_id' => '',
            'created_at' => '',
            'updated_at' => '',
        ]);
    }

    public function update(string $id): JsonResponse
    {
        return response()->json([
            'id' => '',
            'name' => '',
            'color' => '',
            'workspace_id' => '',
            'created_at' => '',
            'updated_at' => '',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function addToIssue(string $id): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function removeFromIssue(string $id, string $labelId): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
