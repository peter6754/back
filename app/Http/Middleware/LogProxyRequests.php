<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Closure;

class LogProxyRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::channel('proxy')->info('Proxy request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}
