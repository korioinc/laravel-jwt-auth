<?php

namespace Korioinc\JwtAuth\Middleware;

use Closure;
use Illuminate\Http\Request;

class AutoRefreshedTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Check if a refreshed token exists in request attributes
        if ($request->attributes->has('jwt_refreshed_token')) {
            $refreshedToken = $request->attributes->get('jwt_refreshed_token');
            $response->headers->set('X-Refreshed-Token', $refreshedToken);
        }

        return $response;
    }
}
