<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Suspension is otherwise only checked at login, so an already-open session (or the
 * "remember me" cookie) would keep buying and refunding after an admin suspends the
 * account. This ends such a session on its next request.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_suspended) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Ce compte a été suspendu.');
        }

        return $next($request);
    }
}
