<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class UploadController
{
    public function upload(): JsonResponse
    {
        return response()->json([
            'id' => '',
            'url' => '',
            'filename' => '',
            'content_type' => '',
            'size_bytes' => 0,
            'created_at' => '',
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['error' => 'not found'], 404);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function content(string $id): JsonResponse
    {
        return response()->json(['text' => '', 'originalContentType' => '']);
    }
}
