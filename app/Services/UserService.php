<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserService
{
    public function __construct(private ThreadQueryService $threads) {}

    public function me(User $user): User
    {
        return $user->loadCount(['followers', 'following']);
    }

    public function show(User $viewer, User $user): User
    {
        $user->loadCount(['followers', 'following']);
        $user->setAttribute('is_me', $user->id === $viewer->id);
        $user->setAttribute('follow_status', $user->followStatusFor($viewer));
        $user->setAttribute('can_view_threads', $user->threadsVisibleTo($viewer));

        return $user;
    }

    public function threads(User $viewer, User $user): LengthAwarePaginator
    {
        $this->assertVisible($viewer, $user);

        return $this->threads->forViewer($viewer)->where('threads.user_id', $user->id)->latest()->paginate(15);
    }

    public function followers(User $viewer, User $user, string $search): LengthAwarePaginator
    {
        return $this->connections($viewer, $user, 'followers', $search);
    }

    public function following(User $viewer, User $user, string $search): LengthAwarePaginator
    {
        return $this->connections($viewer, $user, 'following', $search);
    }

    private function assertVisible(User $viewer, User $user): void
    {
        abort_unless($user->threadsVisibleTo($viewer), 403, 'Akun ini privat.');
    }

    private function connections(User $viewer, User $user, string $relation, string $search): LengthAwarePaginator
    {
        $this->assertVisible($viewer, $user);
        $search = trim($search);

        return $user->{$relation}()
            ->when($search !== '', fn ($q) => $q->where('users.name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderByDesc('follows.created_at')
            ->select(['users.id', 'users.name', 'users.avatar'])
            ->paginate(20);
    }

    public function suggestions(User $viewer): Collection
    {
        return User::where('id', '!=', $viewer->id)->latest('id')->limit(5)
            ->get(['id', 'name', 'avatar'])
            ->each(fn (User $user) => $user->setAttribute('follow_status', $user->followStatusFor($viewer)));
    }
}
