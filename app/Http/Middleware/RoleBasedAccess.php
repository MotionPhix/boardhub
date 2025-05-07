<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedAccess
{
  public function handle(Request $request, Closure $next): Response
  {
    if (! auth()->check() || ! auth()->user()->roles->count()) {
      return redirect()->route('filament.admin.auth.login');
    }

    return $next($request);
  }
}
