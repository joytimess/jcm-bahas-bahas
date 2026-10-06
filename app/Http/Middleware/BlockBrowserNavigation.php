<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyembunyikan endpoint /api dari akses langsung lewat address bar browser.
 * Hanya request fetch/XHR (Accept: JSON) yang boleh lewat.
 */
class BlockBrowserNavigation
{
    public function handle(Request $request, Closure $next): Response
    {
        $isNavigation = $request->header('Sec-Fetch-Mode') === 'navigate'
            || $request->header('Sec-Fetch-Dest') === 'document'
            || (! $request->expectsJson() && ! $request->wantsJson());

        abort_if($isNavigation, 404);

        return $next($request);
    }
}
