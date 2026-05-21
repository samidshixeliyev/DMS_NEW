<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange
{
    /** Routes that are always allowed even when force_password_change = true. */
    private const ALLOWED = [
        'force-password-change',
        'force-password-change.submit',
        'logout',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && $user->force_password_change) {
            // Allow the change-password page and logout to pass through
            if ($request->routeIs(...self::ALLOWED)) {
                return $next($request);
            }
            return redirect()->route('force-password-change');
        }

        return $next($request);
    }
}
