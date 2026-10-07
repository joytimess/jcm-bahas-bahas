<?php

namespace App\Services;

use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepostService
{
    /** Repost dari repost selalu menunjuk ke thread aslinya; post dan quote adalah target itu sendiri. */
    public function resolveOriginal(Thread $thread): Thread
    {
        if ($thread->type !== 'repost') {
            return $thread;
        }

        return $thread->repostOf ?? abort(404);
    }

    /** Thread harus terlihat, belum dihapus, dan bukan milik akun privat. */
    public function assertRepostable(User $viewer, Thread $original): void
    {
        abort_unless($viewer->canView($original), 404);

        if ($original->is_deleted) {
            throw ValidationException::withMessages(['thread' => 'Thread tidak tersedia.']);
        }

        if ($original->user->is_private) {
            throw ValidationException::withMessages(['thread' => 'Thread dari akun privat tidak bisa dibagikan.']);
        }
    }

    /** Repost jika belum, batalkan jika sudah. */
    public function toggle(User $viewer, Thread $thread): array
    {
        $original = $this->resolveOriginal($thread);
        $this->assertRepostable($viewer, $original);

        $reposted = DB::transaction(function () use ($viewer, $original) {
            // Kunci baris user agar klik ganda tidak membuat dua repost.
            User::whereKey($viewer->id)->lockForUpdate()->first();

            $existing = $viewer->threads()->where('repost_of_id', $original->id)->whereNull('body')->first();

            if ($existing) {
                $existing->softDelete();

                return false;
            }

            $viewer->threads()->create(['repost_of_id' => $original->id, 'body' => null]);

            return true;
        });

        return [
            'reposted' => $reposted,
            'reposts_count' => $original->reposts()->count(),
        ];
    }

    /** Thread yang akan di-quote; melempar error bila tidak boleh. */
    public function quoteTarget(User $viewer, int $threadId): Thread
    {
        $original = $this->resolveOriginal(Thread::findOr($threadId, fn () => throw ValidationException::withMessages([
            'quote_of' => 'Thread tidak tersedia.',
        ])));
        $this->assertRepostable($viewer, $original);

        return $original;
    }
}
