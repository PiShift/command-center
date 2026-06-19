<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class SkillController
{
    public function index(): JsonResponse { return response()->json([]); }
    public function show(string $id): JsonResponse { return response()->json(['error' => 'not found'], 404); }
    public function store(): JsonResponse { return response()->json(['status' => 'ok']); }
    public function update(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function destroy(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function import(): JsonResponse { return response()->json(['status' => 'ok']); }
}
