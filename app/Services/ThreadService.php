<?php

namespace App\Services;

use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ThreadService
{
    public function __construct(private ThreadQueryService $queries, private RepostService $reposts, private ImageService $images) {}

    public function show(User $viewer, Thread $thread): Thread
    {
        return $this->queries->forViewer($viewer)->findOrFail($thread->id);
    }

    /**
     * @param  array{body: string, quote_of?: int|null, remove_image_ids?: array<int, int>}  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function store(User $viewer, array $data, array $files): Thread
    {
        $quoteOf = isset($data['quote_of']) ? $this->reposts->quoteTarget($viewer, (int) $data['quote_of'])->id : null;
        $thread = DB::transaction(function () use ($viewer, $data, $files, $quoteOf) {
            $thread = $viewer->threads()->create(['body' => $data['body'], 'repost_of_id' => $quoteOf]);
            $this->images->sync($thread, $files, $data['remove_image_ids'] ?? []);

            return $thread;
        });

        return $this->show($viewer, $thread);
    }

    public function assertEditable(User $viewer, Thread $thread): void
    {
        if ($thread->user_id !== $viewer->id || $thread->type === 'repost') {
            throw new AuthorizationException;
        }
    }

    /**
     * @param  array{body?: string, remove_image_ids?: array<int, int>}  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function update(User $viewer, Thread $thread, array $data, array $files): Thread
    {
        $this->assertEditable($viewer, $thread);
        DB::transaction(function () use ($thread, $data, $files) {
            if (isset($data['body'])) {
                $thread->update(['body' => $data['body']]);
            }
            $this->images->sync($thread, $files, $data['remove_image_ids'] ?? []);
        });

        return $this->show($viewer, $thread);
    }

    public function destroy(User $viewer, Thread $thread): void
    {
        abort_unless($thread->user_id === $viewer->id, 403);
        DB::transaction(fn () => $thread->softDelete());
    }
}
