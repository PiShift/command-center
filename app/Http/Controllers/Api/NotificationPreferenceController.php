<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class NotificationPreferenceController
{
    public function index(): JsonResponse
    {
        return response()->json(['preferences' => []]);
    }

    public function update(): JsonResponse
    {
        return response()->json(['preferences' => []]);
    }
}
