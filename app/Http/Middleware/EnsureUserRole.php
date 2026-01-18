<?php

namespace App\Http\Middleware;

use Closure;
use Inertia\Inertia;
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

        $userRole = $user->role->name;

        if ($role === 'admin' && $userRole !== 'Admin') {
            return Inertia::render('Admin/Errors/403')
                ->toResponse($request)
                ->setStatusCode(403);
        }

        if ($role === 'manajer' && !in_array($userRole, ['Admin', 'Manajer'])) {
            return Inertia::render('Admin/Errors/403')
                ->toResponse($request)
                ->setStatusCode(403);
        }

        if ($role === 'user' && $userRole !== 'Anggota') {
            abort(403);
        }

        return $next($request);
    }
}
