<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ThreadQueryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait QueriesThreads
{
    /** Query thread siap tampil: hanya yang boleh dilihat pengguna, lengkap dengan hitungan dan status like. */
    protected function threadQuery(Request $request): Builder
    {
        return app(ThreadQueryService::class)->forViewer($request->user());
    }
}
