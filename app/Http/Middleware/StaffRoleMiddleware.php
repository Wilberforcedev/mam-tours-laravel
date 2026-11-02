<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StaffRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if (!in_array($user->role, $roles, true) && $user->role !== 'admin') {
            return response()->json(['message' => 'Insufficient permissions'], 403);
        }
        return $next($request);
    }
}
