<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class is_approved
{
    public function handle(Request $request, Closure $next): Response
    {
        if(!auth()->check()){
            return redirect('home');
        }
        if(!auth()->user()->roles())
        return $next($request);
    }
}
