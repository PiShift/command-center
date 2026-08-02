<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function pendingTasks(): JsonResponse
    {
        return response()->json(['tasks' => []]);
    }

    public function sessions(): JsonResponse
    {
        return response()->json([]);
    }
}
