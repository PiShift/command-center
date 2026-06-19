<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class ProjectSearchController
{
    public function search(): JsonResponse
    {
        return response()->json(['projects' => [], 'total' => 0]);
    }
}
