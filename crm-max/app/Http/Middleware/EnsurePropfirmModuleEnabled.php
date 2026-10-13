<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePropfirmModuleEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! module_enabled('propfirm_enabled')) {
            return response()->json([
                'status' => false,
                'message' => 'Prop firm module is not enabled',
            ], 403);
        }

        return $next($request);
    }
}
