<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreThreadRequest;
use App\Http\Requests\Api\ThreadIndexRequest;
use App\Http\Requests\Api\UpdateThreadRequest;
use App\Http\Resources\ThreadResource;
use App\Models\Thread;
use App\Services\FeedService;
use App\Services\ThreadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ThreadController extends Controller
{
    public function __construct(private ThreadService $threads, private FeedService $feed) {}

    public function index(ThreadIndexRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();

        return ThreadResource::collection(
            $this->feed->paginate($request->user(), $data['feed'] ?? 'all', (int) ($data['page'] ?? 1))
                ->appends($request->safe()->except('page'))
        );
    }

    public function show(Request $request, Thread $thread): ThreadResource
    {
        return new ThreadResource($this->threads->show($request->user(), $thread));
    }

    public function store(StoreThreadRequest $request): JsonResponse
    {
        return (new ThreadResource($this->threads->store($request->user(), $request->validated(), $request->file('images', []))))
            ->response()->setStatusCode(201);
    }

    public function update(UpdateThreadRequest $request, Thread $thread): ThreadResource
    {
        return new ThreadResource($this->threads->update($request->user(), $thread, $request->validated(), $request->file('images', [])));
    }

    public function destroy(Request $request, Thread $thread): JsonResponse
    {
        $this->threads->destroy($request->user(), $thread);

        return response()->json(['message' => 'Thread deleted.']);
    }
}
