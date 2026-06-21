<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Guard /admin/* — mirrors proxy.ts. Uses a session key (admin_id) in place
     * of the original bare admin_session cookie. Also rejects accounts that have
     * been deactivated since they logged in.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('admin_id');
        $admin = $id ? Admin::find($id) : null;

        if (! $admin || ! $admin->active) {
            $request->session()->forget(['admin_id', 'admin_username', 'admin_role', 'admin_must_change']);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
