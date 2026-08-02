<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class InboxExtrasController
{
    public function read(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function archive(string $id): JsonResponse { return response()->json(['status' => 'ok']); }
    public function unreadCount(): JsonResponse { return response()->json(['count' => 0]); }
    public function markAllRead(): JsonResponse { return response()->json(['count' => 0]); }
    public function archiveAll(): JsonResponse { return response()->json(['count' => 0]); }
    public function archiveAllRead(): JsonResponse { return response()->json(['count' => 0]); }
    public function archiveCompleted(): JsonResponse { return response()->json(['count' => 0]); }
}
