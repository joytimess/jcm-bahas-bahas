<?php

namespace App\Services;

use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ThreadQueryService
{
    /** @return Builder<Thread> */
    public function forViewer(User $viewer): Builder
    {
        return Thread::visibleTo($viewer)
            ->with(['user:id,name,avatar', 'images'])
            ->withCount(['comments', 'likes'])
            ->withExists(['likes as liked_by_me' => fn ($query) => $query->where('user_id', $viewer->id)]);
    }
}
