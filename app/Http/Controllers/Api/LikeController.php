<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Thread;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function thread(Request $request, Thread $thread): JsonResponse
    {
        abort_unless($request->user()->canView($thread), 404);

        return $this->toggle($request, $thread);
    }

    public function comment(Request $request, Comment $comment): JsonResponse
    {
        abort_unless($comment->thread && $request->user()->canView($comment->thread), 404);

        return $this->toggle($request, $comment);
    }

    /** Like jika belum, unlike jika sudah. */
    private function toggle(Request $request, Model $model): JsonResponse
    {
        $userId = $request->user()->id;
        $existing = $model->likes()->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $like = $model->likes()->make();
            $like->user_id = $userId;
            $like->save();
        }

        return response()->json([
            'liked' => ! $existing,
            'likes_count' => $model->likes()->count(),
        ]);
    }
}
