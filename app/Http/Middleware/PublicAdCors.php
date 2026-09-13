<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PublicAdCors
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->is('api/serve', 'api/track/*')) {
            return $next($request);
        }
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        return $response->header('Access-Control-Allow-Origin', '*')->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')->header('Access-Control-Allow-Headers', 'Content-Type, Accept')->header('Cache-Control', 'no-store');
    }
}
