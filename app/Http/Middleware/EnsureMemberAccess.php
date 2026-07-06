<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $isAnggota = $user->hasRole('Anggota');
        $hasMemberData = $user->member()->exists();

        if (!$isAnggota && !$hasMemberData) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
