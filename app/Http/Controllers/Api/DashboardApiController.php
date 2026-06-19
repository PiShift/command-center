<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class DashboardApiController
{
    public function usageDaily(): JsonResponse { return response()->json([]); }
    public function usageByAgent(): JsonResponse { return response()->json([]); }
    public function agentRuntime(): JsonResponse { return response()->json([]); }
    public function runtimeDaily(): JsonResponse { return response()->json([]); }
}
