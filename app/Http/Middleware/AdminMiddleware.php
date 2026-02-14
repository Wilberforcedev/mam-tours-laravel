<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if (!auth()->user()->isAdmin()) {
            return redirect('/dashboard')->with('error', 'Unauthorized access. Admin rights required.');
        }

        return $next($request);
    }
}
