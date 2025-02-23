<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Barryvdh\Debugbar\Facades\Debugbar;

class DebugbarMiddleware
{
    public function handle(Request $request, Closure $next)
    {
//        // Включаем Debugbar только для администраторов Backpack
//        if (backpack_auth()->check()) {
//            config('app.debug', true);
//            Debugbar::enable();
//        }

        return $next($request);
    }
}
