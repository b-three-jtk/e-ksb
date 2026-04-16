<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'member_number' => ['required'],
            'password' => ['required'],
        ]);

        $user = User::with('role')->where('member_number', $credentials['member_number'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'member_number' => 'Nomor anggota atau password tidak sesuai.',
            ]);
        }

        if ($user->status !== UserStatus::ACTIVE->value) {
            $messages = [
                UserStatus::INREVIEW->value => 'Pengajuan keanggotaan Anda masih dalam peninjauan.',
                UserStatus::REJECTED->value => 'Pengajuan keanggotaan ditolak. Silakan hubungi admin.',
                UserStatus::INACTIVE->value => 'Akun Anda tidak aktif. Hubungi admin untuk mengaktifkan kembali.',
                UserStatus::RESIGNED->value => 'Anda sudah tidak terdaftar sebagai anggota.',
            ];

            throw ValidationException::withMessages([
                'member_number' => $messages[$user->status] ?? 'Akun Anda belum aktif.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'member_number' => 'Nomor anggota atau password tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();

        // Redirect based on user role
        if ($user->role->name !== 'Anggota') {
            return redirect()->intended('/admin/dashboard');
        }

        return redirect()->intended('/user/dashboard');
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
