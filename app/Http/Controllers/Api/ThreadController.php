<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Http\Controllers\Concerns\QueriesThreads;
use App\Http\Controllers\Controller;
use App\Http\Resources\ThreadResource;
use App\Models\Thread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThreadController extends Controller
{
    use HandlesImages, QueriesThreads;

    public function index(Request $request)
    {
        return ThreadResource::collection(
            $this->threadQuery($request)->latest()->paginate(15)
        );
    }

    public function show(Request $request, Thread $thread): ThreadResource
    {
        return new ThreadResource($this->threadQuery($request)->findOrFail($thread->id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:280']] + $this->imageRules());

        $thread = DB::transaction(function () use ($request, $data) {
            $thread = $request->user()->threads()->create(['body' => $data['body']]);
            $this->syncImages($request, $thread);

            return $thread;
        });

        return (new ThreadResource($this->threadQuery($request)->findOrFail($thread->id)))
            ->response()->setStatusCode(201);
    }

    public function update(Request $request, Thread $thread): ThreadResource
    {
        abort_unless($thread->user_id === $request->user()->id, 403);

        $data = $request->validate(['body' => ['sometimes', 'required', 'string', 'max:280']] + $this->imageRules());

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
