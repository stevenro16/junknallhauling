<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Guard /admin/* — mirrors proxy.ts. Uses a session key (admin_id) in place
     * of the original bare admin_session cookie.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('admin_id')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
