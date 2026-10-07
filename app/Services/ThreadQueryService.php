<?php

namespace App\Services;

use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ThreadQueryService
{
    /** @return Builder<Thread> */
    public function forViewer(User $viewer): Builder
    {
        return $this->decorate(Thread::visibleTo($viewer), $viewer)
            ->with(['repostOf' => fn ($query) => $this->decorate($query->visibleTo($viewer), $viewer)])
            // Repost murni yang thread aslinya hilang / tak terlihat tidak ditampilkan.
            ->where(fn (Builder $q) => $q->whereNotNull('threads.body')
                ->orWhereIn('threads.repost_of_id', Thread::visibleTo($viewer)->select('threads.id')));
    }

    /** Relasi, hitungan, dan status like/repost yang dibutuhkan ThreadResource. */
    private function decorate(Builder|Relation $query, User $viewer): Builder|Relation
    {
        return $query
            ->with(['user:id,name,avatar,is_private', 'images'])
            ->withCount(['comments', 'likes', 'reposts', 'quotes'])
            ->withExists([
                'likes as liked_by_me' => fn ($q) => $q->where('user_id', $viewer->id),
                'reposts as reposted_by_me' => fn ($q) => $q->where('user_id', $viewer->id),
            ]);
    }
}
