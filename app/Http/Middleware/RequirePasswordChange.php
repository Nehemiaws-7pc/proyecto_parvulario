<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->cambiar_password && ! $request->routeIs('password.edit', 'password.update', 'logout')) {
            return redirect()->route('password.edit');
        }

        return $next($request);
    }
}
