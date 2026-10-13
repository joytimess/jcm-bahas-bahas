<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Thread;
use App\Services\RepostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepostController extends Controller
{
    public function __construct(private RepostService $reposts) {}

    public function toggle(Request $request, Thread $thread): JsonResponse
    {
        return response()->json($this->reposts->toggle($request->user(), $thread));
    }
}
