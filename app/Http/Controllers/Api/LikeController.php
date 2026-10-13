<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Thread;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __construct(private LikeService $likes) {}

    public function thread(Request $request, Thread $thread): JsonResponse
    {
        return response()->json($this->likes->thread($request->user(), $thread));
    }

    public function comment(Request $request, Comment $comment): JsonResponse
    {
        return response()->json($this->likes->comment($request->user(), $comment));
    }
}
