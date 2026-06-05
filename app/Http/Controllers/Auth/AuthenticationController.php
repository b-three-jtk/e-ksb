<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthenticationController extends Controller
{
    public function loginPage()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'user_code' => ['required'],
            'password' => ['required'],
        ]);

        $user = User::where('user_code', $credentials['user_code'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'user_code' => 'Nomor anggota atau password tidak sesuai.',
            ]);
        }

        // Allow users to login for non-inactive statuses (e.g., resigned requests/rejections)
        if ($user->status === UserStatusEnum::INACTIVE->value) {
            throw ValidationException::withMessages([
                'user_code' => 'Akun Anda tidak aktif. Hubungi pengurus koperasi untuk mengaktifkan kembali.',
            ]);
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'user_code' => 'Nomor anggota atau password tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();

        $userRoles = $user->getRoleNames();

        if (!$userRoles->contains(UserRoleEnum::ANGGOTA->value)) {
            return redirect()->intended('/admin/dashboard');
        }

        return redirect()->intended('/user/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
