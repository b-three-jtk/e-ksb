<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Services\AutentikasiService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AutentikasiController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected AutentikasiService $autentikasiService
    ) {}

    /**
     * Display the login page view.
     */
    public function loginPage()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'user_code' => ['required'],
            'password' => ['required'],
        ]);

        $user = $this->autentikasiService->login($credentials, $request->boolean('remember'));

        $request->session()->regenerate();

        $userRoles = $user->getRoleNames();

        if (!$userRoles->contains(UserRoleEnum::ANGGOTA->value)) {
            return redirect()->intended('/admin/dashboard');
        }

        return redirect()->intended('/user/dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request)
    {
        $this->autentikasiService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
