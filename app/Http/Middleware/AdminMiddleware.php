<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect('/login');
        }

        if (!auth()->user()->isAdmin()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Admin rights required.'], 403)
                : redirect('/dashboard')->with('error', 'Unauthorized access. Admin rights required.');
        }

        return $next($request);
    }
}
