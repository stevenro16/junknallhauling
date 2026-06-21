<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Restrict a route to full admins. Employees (or anyone non-admin) are sent
     * to their own schedule (or 403 for JSON/API calls).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('admin_role') !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            return redirect()->route('admin.my-schedule');
        }

        return $next($request);
    }
}
