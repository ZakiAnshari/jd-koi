<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Check if user is authenticated and has admin role
        if (!$user || $user->role_id !== 1) {
            abort(403);
        }

        return $next($request);
    }
}
