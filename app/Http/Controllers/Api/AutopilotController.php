<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class AutopilotController
{
    public function index(): JsonResponse { return response()->json(['autopilots' => [], 'total' => 0]); }
    public function store(): JsonResponse { return response()->json(['status' => 'ok']); }
    public function show(string $id): JsonResponse { return response()->json(['error' => 'not found'], 404); }
    public function update(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function destroy(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function trigger(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function runs(string $id): JsonResponse { return response()->json(['runs' => [], 'total' => 0]); }
    public function run(string $autopilotId, string $runId): JsonResponse { return response()->json(['error' => 'not found'], 404); }
    public function createTrigger(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function updateTrigger(string $id, string $triggerId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function deleteTrigger(string $id, string $triggerId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function rotateWebhook(string $id, string $triggerId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function deliveries(string $id): JsonResponse { return response()->json(['deliveries' => [], 'total' => 0]); }
    public function delivery(string $id, string $deliveryId): JsonResponse { return response()->json(['error' => 'not found'], 404); }
    public function replayDelivery(string $id, string $deliveryId): JsonResponse { return response()->json(['status' => 'ok']); }
}
