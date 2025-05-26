<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictTelescope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (!app()->environment('production')) {
            return $next($request);
        }

        if (!backpack_auth()->check()) {
            abort(403, 'Telescope access denied');
        }

        return $next($request);
    }
}
