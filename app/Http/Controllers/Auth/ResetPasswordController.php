<?php

namespace App\Http\Controllers\Auth;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;

class ResetPasswordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $token)
    {
        // Lempar token dan phone_number (dari query URL) ke halaman Vue
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'phone_number' => $request->query('phone_number'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function submitRequest(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'token' => 'required',
            'phone_number' => 'required|string|exists:users,phone_number',
            'password' => 'required|min:8|confirmed',
        ]);

        // 2. Cek kesesuaian token dan phone_number di database
        $resetRecord = DB::table('password_reset_tokens')
            ->where('phone_number', $request->phone_number)
            ->where('token', $request->token)
            ->first();

        // Jika tidak ada record yang cocok
        if (!$resetRecord) {
            return back()->withErrors(['phone_number' => 'Token reset password tidak valid.']);
        }

        // 3. Cek apakah token sudah kedaluwarsa (misal batas waktunya 60 menit)
        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('phone_number', $request->phone_number)->delete();
            return back()->withErrors(['phone_number' => 'Link reset password sudah kedaluwarsa. Silakan minta link baru.']);
        }

        // 4. Jika valid, update password user
        $user = User::where('phone_number', $request->phone_number)->first();
        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        // 5. Hapus token setelah password berhasil diubah agar tidak bisa dipakai lagi
        DB::table('password_reset_tokens')->where('phone_number', $request->phone_number)->delete();

        // 6. Redirect ke halaman login dengan pesan sukses
        return redirect()->route('login')->with('success', 'Password berhasil direset! Silakan login dengan password baru.');
    }
}