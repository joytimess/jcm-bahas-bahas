<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserBriefResource;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Thread;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MyLikeController extends Controller
{
    /**
     * Riwayat like pengguna (thread dan komentar), terbaru dulu.
     * Like komentar menyertakan thread induknya. Thread yang dihapus atau tidak lagi boleh dilihat dilewati.
     */
    public function index(Request $request)
    {
        $me = $request->user();

        $likes = Like::where('user_id', $me->id)
            ->whereHasMorph('likeable', [Thread::class, Comment::class], function ($q, $type) use ($me) {
                $type === Thread::class
                    ? $q->visibleTo($me)
                    : $q->whereHas('thread', fn ($t) => $t->visibleTo($me));
            })
            ->with(['likeable' => fn (MorphTo $m) => $m->morphWith([
                Thread::class => ['user:id,name,avatar'],
                Comment::class => ['user:id,name,avatar', 'thread.user:id,name,avatar'],
            ])])
            ->latest('id')
            ->paginate(15);

        $likes->getCollection()->transform(function (Like $like) {
            $item = $like->likeable;
            $isComment = $item instanceof Comment;
            $thread = $isComment ? $item->thread : $item;

            return [
                'id' => $like->id,
                'type' => $isComment ? 'comment' : 'thread',
                'liked_at' => $like->created_at,
                'thread' => [
                    'id' => $thread->id,
                    'body' => Str::limit($thread->body, $isComment ? 120 : 280),
                    'user' => new UserBriefResource($thread->user),
                ],
                'comment' => $isComment ? [
                    'id' => $item->id,
                    'body' => $item->body,
                    'user' => new UserBriefResource($item->user),
                ] : null,
            ];
        });

        return $likes;
    }
}
