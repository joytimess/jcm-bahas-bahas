<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCommentRequest;
use App\Http\Requests\Api\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Thread;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    public function __construct(private CommentService $comments) {}

    public function index(Request $request, Thread $thread): AnonymousResourceCollection
    {
        return CommentResource::collection($this->comments->index($request->user(), $thread));
    }

    public function store(StoreCommentRequest $request, Thread $thread): JsonResponse
    {
        return (new CommentResource($this->comments->store($request->user(), $thread, $request->validated(), $request->file('images', []))))
            ->response()->setStatusCode(201);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): CommentResource
    {
        return new CommentResource($this->comments->update($request->user(), $comment, $request->validated(), $request->file('images', [])));
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $this->comments->destroy($request->user(), $comment);

        return response()->json(['message' => 'Comment deleted.']);
    }
}
