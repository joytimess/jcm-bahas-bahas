<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Thread;
use Illuminate\Http\Request;

trait QueriesThreads
{
    /** Query thread siap tampil: hanya yang boleh dilihat pengguna, lengkap dengan hitungan dan status like. */
    protected function threadQuery(Request $request)
    {
        $me = $request->user();

        return Thread::visibleTo($me)
            ->with(['user:id,name,avatar', 'images'])
            ->withCount(['comments', 'likes'])
            ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', $me->id)]);
    }
}
