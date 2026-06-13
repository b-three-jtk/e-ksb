<?php

namespace App\Services;

use App\Models\User;
use App\Enums\UserStatusEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AutentikasiService
{
    /**
     * Authenticate a user with code and password.
     *
     * @param array{user_code: string, password: string} $credentials
     * @param bool $remember
     * @return User
     * @throws ValidationException
     */
    public function login(array $credentials, bool $remember = false): User
    {
        $user = User::where('user_code', $credentials['user_code'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'user_code' => 'Kode pengguna atau password tidak sesuai.',
            ]);
        }

        // Allow users to login for non-inactive statuses (e.g., resigned requests/rejections)
        if ($user->status === UserStatusEnum::INACTIVE->value) {
            throw ValidationException::withMessages([
                'user_code' => 'Akun Anda tidak aktif. Hubungi pengurus koperasi untuk mengaktifkan kembali.',
            ]);
        }

        if (!Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'user_code' => 'Kode pengguna atau password tidak sesuai.',
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
