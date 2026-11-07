<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userRole = Auth::user()->role;
        if (!empty($roles) && !in_array($userRole, $roles)) {
            return redirect()->route('dashboard')
                ->with('error', 'Access denied: Your role (' . strtoupper($userRole) . ') is not authorized for this action.');
        }

        return $next($request);
    }
}
