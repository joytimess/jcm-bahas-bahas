<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function __construct(private ThreadQueryService $threads) {}

    public function threads(User $viewer, string $query, int $page): LengthAwarePaginator
    {
        return $this->threads->forViewer($viewer)
            ->whereRaw("LOWER(threads.body) LIKE ? ESCAPE '!'", [$this->pattern($query)])
            ->latest('threads.created_at')->latest('threads.id')
            ->paginate(15, ['*'], 'page', $page);
    }

    public function users(User $viewer, string $query, int $page): LengthAwarePaginator
    {
        return User::query()
            ->select(['users.id', 'users.name', 'users.avatar', 'users.is_private'])
            ->selectSub(DB::table('follows')->select('status')
                ->whereColumn('following_id', 'users.id')
                ->where('follower_id', $viewer->id), 'viewer_follow_status')
            ->whereRaw("LOWER(users.name) LIKE ? ESCAPE '!'", [$this->pattern($query)])
            ->orderBy('users.name')->orderBy('users.id')
            ->paginate(15, ['*'], 'page', $page);
    }

    private function pattern(string $query): string
    {
        return '%'.strtr(strtolower($query), ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
    }
}
