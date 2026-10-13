<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserBriefResource;
use App\Models\User;
use App\Services\FollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowRequestController extends Controller
{
    public function __construct(private FollowService $follows) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => UserBriefResource::collection($this->follows->requests($request->user()))]);
    }

    public function accept(Request $request, User $user): JsonResponse
    {
        return response()->json($this->follows->accept($request->user(), $user));
    }

    public function reject(Request $request, User $user): JsonResponse
    {
        $this->follows->reject($request->user(), $user);

        return response()->json(['message' => 'Permintaan ditolak.']);
    }
}
