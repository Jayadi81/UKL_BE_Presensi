<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $role)
    {
        // Mengecek apakah role pengguna sesuai
        if (Auth::user() && Auth::user()->role !== $role) {
            return response()->json(['message' => 'Akses ditolak. Anda tidak memiliki hak akses.'], 403);
        }

        return $next($request);
    }
}