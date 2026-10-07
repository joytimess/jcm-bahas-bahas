<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Http\Controllers\Concerns\QueriesThreads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreThreadRequest;
use App\Http\Requests\Api\ThreadIndexRequest;
use App\Http\Requests\Api\UpdateThreadRequest;
use App\Http\Resources\ThreadResource;
use App\Models\Thread;
use App\Services\FeedService;
use App\Services\RepostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ThreadController extends Controller
{
    use HandlesImages, QueriesThreads;

    public function index(ThreadIndexRequest $request, FeedService $feed): AnonymousResourceCollection
    {
        $data = $request->validated();

        return ThreadResource::collection(
            $feed->paginate($request->user(), $data['feed'] ?? 'all', (int) ($data['page'] ?? 1))
                ->appends($request->safe()->except('page'))
        );
    }

    public function show(Request $request, Thread $thread): ThreadResource
    {
        return new ThreadResource($this->threadQuery($request)->findOrFail($thread->id));
    }

    public function store(StoreThreadRequest $request, RepostService $reposts): JsonResponse
    {
        $data = $request->validated();
        $quoteOf = isset($data['quote_of']) ? $reposts->quoteTarget($request->user(), (int) $data['quote_of'])->id : null;

        $thread = DB::transaction(function () use ($request, $data, $quoteOf) {
            $thread = $request->user()->threads()->create(['body' => $data['body'], 'repost_of_id' => $quoteOf]);
            $this->syncImages($request, $thread);

            return $thread;
        });

        return (new ThreadResource($this->threadQuery($request)->findOrFail($thread->id)))
            ->response()->setStatusCode(201);
    }

    public function update(UpdateThreadRequest $request, Thread $thread): ThreadResource
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $thread, $data) {
            if (isset($data['body'])) {
                $thread->update(['body' => $data['body']]);
            }
            $this->syncImages($request, $thread);
        });

        return new ThreadResource($this->threadQuery($request)->findOrFail($thread->id));
    }

    public function destroy(Request $request, Thread $thread): JsonResponse
    {
        abort_unless($thread->user_id === $request->user()->id, 403);

        DB::transaction(fn () => $thread->softDelete());

        return response()->json(['message' => 'Thread deleted.']);
    }
}
