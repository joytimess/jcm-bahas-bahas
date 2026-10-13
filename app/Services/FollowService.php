<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FollowService
{
    /** @return array{status: string, followers_count: int} */
    public function toggle(User $me, User $user): array
    {
        abort_if($me->id === $user->id, 422, 'Kamu tidak bisa mengikuti dirimu sendiri.');

        $edge = DB::table('follows')->where('follower_id', $me->id)->where('following_id', $user->id);

        if ($edge->exists()) {
            $edge->delete();
        } else {
            DB::table('follows')->insert([
                'follower_id' => $me->id,
                'following_id' => $user->id,
                'status' => $user->is_private ? 'pending' : 'accepted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'status' => $user->followStatusFor($me),
            'followers_count' => $user->followers()->count(),
        ];
    }

    public function requests(User $me): Collection
    {
        $requests = $me->followRequests()
            ->orderByDesc('follows.created_at')
            ->get(['users.id', 'users.name', 'users.avatar']);

        return $requests;
    }

    /** @return array{followers_count: int} */
    public function accept(User $me, User $user): array
    {
        $updated = $this->pending($me, $user)->update(['status' => 'accepted', 'updated_at' => now()]);

        abort_if($updated === 0, 404);

        return ['followers_count' => $me->followers()->count()];
    }

    public function reject(User $me, User $user): void
    {
        abort_if($this->pending($me, $user)->delete() === 0, 404);

    }

    private function pending(User $me, User $follower): Builder
    {
        return DB::table('follows')
            ->where('follower_id', $follower->id)
            ->where('following_id', $me->id)
            ->where('status', 'pending');
    }
}
