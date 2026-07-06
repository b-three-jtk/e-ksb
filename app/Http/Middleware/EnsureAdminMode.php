<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $authMode = $request->session()->get('auth_mode');

        if ($authMode === null) {
            return $next($request);
        }

        if ($authMode === 'anggota') {
            return redirect('/user/dashboard');
        }

        return $next($request);
    }
}
