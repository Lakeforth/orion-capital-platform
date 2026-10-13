<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLeadMaxEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(module_enabled('leadmax_enabled'), 404);
        abort_if(auth()->user()?->hasRole(['customer', 'user']), 403);

        return $next($request);
    }
}
