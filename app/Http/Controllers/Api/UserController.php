<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\QueriesThreads;
use App\Http\Controllers\Controller;
use App\Http\Resources\ThreadResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use QueriesThreads;

    /** Pengguna yang sedang login beserta hitungan followers dan following. */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadCount(['followers', 'following']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'is_private' => $user->is_private,
            'followers_count' => $user->followers_count,
            'following_count' => $user->following_count,
        ]);
    }

    /** Profil publik pengguna lain (tanpa email). Hitungan tetap tampil walau akun privat. */
    public function show(Request $request, User $user): JsonResponse
    {
        $me = $request->user();
        $user->loadCount(['followers', 'following']);

        return response()->json(['data' => [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
            'is_private' => $user->is_private,
            'is_me' => $user->id === $me->id,
            'followers_count' => $user->followers_count,
            'following_count' => $user->following_count,
            'follow_status' => $user->followStatusFor($me),
            'can_view_threads' => $user->threadsVisibleTo($me),
        ]]);
    }

    /** Thread milik satu pengguna; 403 bila akunnya privat dan belum disetujui. */
    public function threads(Request $request, User $user)
    {
        abort_unless($user->threadsVisibleTo($request->user()), 403, 'Akun ini privat.');

        return ThreadResource::collection(
            $this->threadQuery($request)->where('threads.user_id', $user->id)->latest()->paginate(15)
        );
    }

    /** 5 pengguna terbaru selain diri sendiri. */
    public function suggestions(Request $request): JsonResponse
    {
        $me = $request->user();

        $users = User::where('id', '!=', $me->id)
            ->latest('id')
            ->limit(5)
            ->get(['id', 'name', 'avatar']);

        return response()->json([
            'data' => $users->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'avatar_url' => $u->avatar_url,
                'follow_status' => $u->followStatusFor($me),
            ])->values(),
        ]);
    }
}
