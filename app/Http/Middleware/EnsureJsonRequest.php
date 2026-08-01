<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJsonRequest
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->expectsJson() && ! $request->is('api/v1/webhooks/*')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
