<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    /**
     * Force a logged-in admin flagged must_change_password onto the change
     * screen before they can use the rest of the portal. JSON/API requests are
     * left alone so XHR calls aren't redirected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('admin_must_change') && ! $request->expectsJson()) {
            $allowed = ['admin.change-password', 'admin.change-password.update', 'admin.logout'];

            if (! $request->routeIs(...$allowed)) {
                return redirect()->route('admin.change-password');
            }
        }

        return $next($request);
    }
}
