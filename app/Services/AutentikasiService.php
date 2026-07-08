<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Enums\UserStatusEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AutentikasiService
{
    /**
     * Authenticate a user with code and password.
     *
     * @param array{kode_pengguna: string, password: string} $credentials
     * @param bool $remember
     * @return Pengguna
     * @throws ValidationException
     */
    public function login(array $credentials, bool $remember = false): Pengguna
    {
        $user = Pengguna::where('kode_pengguna', $credentials['kode_pengguna'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'kode_pengguna' => 'Kode pengguna atau password tidak sesuai.',
            ]);
        }

        // Allow users to login for non-inactive statuses (e.g., resigned requests/rejections)
        if ($user->status === UserStatusEnum::INACTIVE->value) {
            throw ValidationException::withMessages([
                'kode_pengguna' => 'Akun Anda tidak aktif. Hubungi pengurus koperasi untuk mengaktifkan kembali.',
            ]);
        }

        if (!Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'kode_pengguna' => 'Kode pengguna atau password tidak sesuai.',
            ]);
        }

        return $user;
    }

    /**
     * Log the user out of the application.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();
    }
}
