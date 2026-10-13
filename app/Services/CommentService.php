<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommentService
{
    public function __construct(private ImageService $images) {}

    public function assertVisible(User $viewer, Thread $thread): void
    {
        abort_unless($viewer->canView($thread), 404);
    }

    public function assertOwner(User $viewer, Comment $comment): void
    {
        abort_unless($comment->user_id === $viewer->id, 403);
    }

    /** @return Collection<int, Comment> */
    public function index(User $viewer, Thread $thread): Collection
    {
        $this->assertVisible($viewer, $thread);
        $all = Comment::where('thread_id', $thread->id)
            ->with(['user:id,name,avatar', 'images'])
            ->withCount('likes')
            ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', $viewer->id)])
            ->oldest()->get();
        $byParent = $all->groupBy('parent_id');
        $all->each(fn ($c) => $c->setRelation('replyTree', $byParent->get($c->id, collect())->values()));

        return $byParent->get(null, collect())->values();
    }

    /**
     * @param  array{body: string, parent_id?: int|null, remove_image_ids?: array<int, int>}  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function store(User $viewer, Thread $thread, array $data, array $files): Comment
    {
        $this->assertVisible($viewer, $thread);
        $comment = DB::transaction(function () use ($viewer, $thread, $data, $files) {
            $comment = new Comment(['body' => $data['body'], 'parent_id' => $data['parent_id'] ?? null]);
            $comment->thread_id = $thread->id;
            $comment->user_id = $viewer->id;
            $comment->save();
            $this->images->sync($comment, $files, $data['remove_image_ids'] ?? []);

            return $comment;
        });

        return $comment->load(['user:id,name,avatar', 'images']);
    }

    /**
     * @param  array{body?: string, remove_image_ids?: array<int, int>}  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function update(User $viewer, Comment $comment, array $data, array $files): Comment
    {
        $this->assertOwner($viewer, $comment);
        DB::transaction(function () use ($comment, $data, $files) {
            if (isset($data['body'])) {
                $comment->update(['body' => $data['body']]);
            }
            $this->images->sync($comment, $files, $data['remove_image_ids'] ?? []);
        });

        return $comment->load(['user:id,name,avatar', 'images']);
    }

    public function destroy(User $viewer, Comment $comment): void
    {
        $this->assertOwner($viewer, $comment);
        DB::transaction(fn () => $comment->softDelete());
    }
}
