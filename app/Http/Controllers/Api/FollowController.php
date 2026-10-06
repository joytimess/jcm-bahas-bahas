<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowController extends Controller
{
    /**
     * Follow jika belum; batal follow / batalkan permintaan jika sudah.
     * Akun privat membuat permintaan `pending`, akun publik langsung `accepted`.
     */
    public function toggle(Request $request, User $user): JsonResponse
    {
        $me = $request->user();

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

        return response()->json([
            'status' => $user->followStatusFor($me),
            'followers_count' => $user->followers()->count(),
        ]);
    }
}
