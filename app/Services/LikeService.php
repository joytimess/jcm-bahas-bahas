<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;

class LikeService
{
    /** @return array{liked: bool, likes_count: int} */
    public function thread(User $viewer, Thread $thread): array
    {
        abort_unless($viewer->canView($thread), 404);

        return $this->toggle($viewer, $thread);
    }

    /** @return array{liked: bool, likes_count: int} */
    public function comment(User $viewer, Comment $comment): array
    {
        abort_unless($comment->thread && $viewer->canView($comment->thread), 404);

        return $this->toggle($viewer, $comment);
    }

    /**
     * Like jika belum, unlike jika sudah.
     *
     * @return array{liked: bool, likes_count: int}
     */
    private function toggle(User $viewer, Model $model): array
    {
        $userId = $viewer->id;
        $existing = $model->likes()->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $like = $model->likes()->make();
            $like->user_id = $userId;
            $like->save();
        }

        return [
            'liked' => ! $existing,
            'likes_count' => $model->likes()->count(),
        ];
    }

    public function history(User $viewer): LengthAwarePaginator
    {
        return Like::where('user_id', $viewer->id)
            ->whereHasMorph('likeable', [Thread::class, Comment::class], function ($q, $type) use ($viewer) {
                $type === Thread::class
                    ? $q->visibleTo($viewer)
                    : $q->whereHas('thread', fn ($t) => $t->visibleTo($viewer));
            })
            ->with(['likeable' => fn (MorphTo $m) => $m->morphWith([
                Thread::class => ['user:id,name,avatar'],
                Comment::class => ['user:id,name,avatar', 'thread.user:id,name,avatar'],
            ])])
            ->latest('id')
            ->paginate(15);

    }
}
