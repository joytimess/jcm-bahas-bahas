<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FeedService
{
    public function __construct(private ThreadQueryService $threads) {}

    public function paginate(User $viewer, string $feed, int $page): LengthAwarePaginator
    {
        $query = $this->threads->forViewer($viewer);

        if ($feed === 'following') {
            $query->where('threads.user_id', '!=', $viewer->id)
                ->whereIn('threads.user_id', DB::table('follows')
                    ->select('following_id')
                    ->where('follower_id', $viewer->id)
                    ->where('status', 'accepted'));
        }

        return $query->latest('threads.created_at')->latest('threads.id')
            ->paginate(15, ['*'], 'page', $page);
    }
}
