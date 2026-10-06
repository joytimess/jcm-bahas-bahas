<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Thread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    use HandlesImages;

    /** Seluruh komentar thread sebagai tree (balasan bersarang tak terbatas). */
    public function index(Request $request, Thread $thread)
    {
        abort_unless($request->user()->canView($thread), 404);

        $all = Comment::where('thread_id', $thread->id)
            ->with(['user:id,name,avatar', 'images'])
            ->withCount('likes')
            ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->oldest()
            ->get();

        $byParent = $all->groupBy('parent_id');

        $all->each(fn ($c) => $c->setRelation('replyTree', $byParent->get($c->id, collect())->values()));

        return CommentResource::collection($byParent->get(null, collect())->values());
    }

    public function store(Request $request, Thread $thread): JsonResponse
    {
        abort_unless($request->user()->canView($thread), 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:280'],
            'parent_id' => ['nullable', 'integer', Rule::exists('comments', 'id')
                ->where('thread_id', $thread->id)->where('is_deleted', 0)],
        ] + $this->imageRules());

        $comment = DB::transaction(function () use ($request, $thread, $data) {
            $comment = new Comment(['body' => $data['body'], 'parent_id' => $data['parent_id'] ?? null]);
            $comment->thread_id = $thread->id;
            $comment->user_id = $request->user()->id;
            $comment->save();

            $this->syncImages($request, $comment);

            return $comment;
        });

        return (new CommentResource($comment->load(['user:id,name,avatar', 'images'])))
            ->response()->setStatusCode(201);
    }

    public function update(Request $request, Comment $comment): CommentResource
    {
        abort_unless($comment->user_id === $request->user()->id, 403);

        $data = $request->validate(['body' => ['sometimes', 'required', 'string', 'max:280']] + $this->imageRules());

        DB::transaction(function () use ($request, $comment, $data) {
            if (isset($data['body'])) {
                $comment->update(['body' => $data['body']]);
            }
            $this->syncImages($request, $comment);
        });

        return new CommentResource($comment->load(['user:id,name,avatar', 'images']));
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);

        DB::transaction(fn () => $comment->softDelete());

        return response()->json(['message' => 'Comment deleted.']);
    }
}
