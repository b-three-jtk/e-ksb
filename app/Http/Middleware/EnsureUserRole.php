<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user || !$user->role) {
            return redirect('/auth/login');
        }

        // Redirect based on role
        if ($role === 'admin' && $user->role->name !== 'Super Admin') {
            return redirect('/user/dashboard')->with('error', 'Anda tidak memiliki akses ke halaman admin.');
        }

        if ($role === 'user' && $user->role->name === 'Super Admin') {
            return redirect('/admin/dashboard')->with('error', 'Admin harus menggunakan dashboard admin.');
        }

        return $next($request);
    }
}
