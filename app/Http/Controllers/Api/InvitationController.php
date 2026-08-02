<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class InvitationController
{
    public function show(string $invitationId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function accept(string $invitationId): JsonResponse { return response()->json(['status' => 'ok']); }
    public function decline(string $invitationId): JsonResponse { return response()->json(['status' => 'ok']); }
}
