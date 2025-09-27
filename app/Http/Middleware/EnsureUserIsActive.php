<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !auth()->user()->is_active) {
            auth()->logout();

            return redirect()->route('login')
                ->with('error', 'Your account has been deactivated. Please contact your administrator.');
        }

        return $next($request);
    }
}
