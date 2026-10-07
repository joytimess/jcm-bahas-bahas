<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Thread;
use App\Services\RepostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepostController extends Controller
{
    public function toggle(Request $request, Thread $thread, RepostService $reposts): JsonResponse
    {
        return response()->json($reposts->toggle($request->user(), $thread));
    }
}
