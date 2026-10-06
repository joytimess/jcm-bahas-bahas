<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowRequestController extends Controller
{
    /** Permintaan follow yang menunggu persetujuan pengguna yang sedang login. */
    public function index(Request $request): JsonResponse
    {
        $requests = $request->user()->followRequests()
            ->orderByDesc('follows.created_at')
            ->get(['users.id', 'users.name', 'users.avatar']);

        return response()->json([
            'data' => $requests->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'avatar_url' => $u->avatar_url,
            ])->values(),
        ]);
    }

    public function accept(Request $request, User $user): JsonResponse
    {
        $updated = $this->pending($request, $user)->update(['status' => 'accepted', 'updated_at' => now()]);

        abort_if($updated === 0, 404);

        return response()->json(['followers_count' => $request->user()->followers()->count()]);
    }

    public function reject(Request $request, User $user): JsonResponse
    {
        abort_if($this->pending($request, $user)->delete() === 0, 404);

        return response()->json(['message' => 'Permintaan ditolak.']);
    }

    private function pending(Request $request, User $follower)
    {
        return DB::table('follows')
            ->where('follower_id', $follower->id)
            ->where('following_id', $request->user()->id)
            ->where('status', 'pending');
    }
}
