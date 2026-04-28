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

class LoginController extends Controller
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

        $user = User::with('role')->where('user_code', $credentials['user_code'])->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'user_code' => 'Nomor anggota atau password tidak sesuai.',
            ]);
        }

        if ($user->status !== UserStatusEnum::ACTIVE->value) {
            $messages = [
                UserStatusEnum::INACTIVE->value => 'Akun Anda tidak aktif. Hubungi admin untuk mengaktifkan kembali.',
            ];

            throw ValidationException::withMessages([
                'user_code' => $messages[$user->status] ?? 'Akun Anda belum aktif.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'user_code' => 'Nomor anggota atau password tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();

        // Redirect based on user role
        if ($user->role->role_name !== UserRoleEnum::ANGGOTA->value) {
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
