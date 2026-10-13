<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function __construct(private FollowService $follows) {}

    public function toggle(Request $request, User $user): JsonResponse
    {
        return response()->json($this->follows->toggle($request->user(), $user));
    }
}
