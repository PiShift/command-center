<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class FeedbackController
{
    public function store(): JsonResponse
    {
        return response()->json(['id' => '', 'created_at' => '']);
    }
}
