<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use App\Services\AutentikasiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
            'kode_pengguna' => ['required'],
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

    public function formForgotPassword()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function submitForgotPasswordRequest(Request $request)
    {
        $request->validate([
            'no_telp' => 'required|string|exists:penggunano_telp'
        ], [
            'no_telp.exists' => 'Nomor WhatsApp tidak ditemukan di sistem kami.'
        ]);

        $phone = $request->no_telp;

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['no_telp' => $phone],
            ['token' => $token, 'created_at' => now()]
        );

        $resetLink = route('password.reset', ['token' => $token, 'no_telp' => $phone]);

        $message = "*RESET PASSWORD*\n\n";
        $message .= "Halo, kami menerima permintaan untuk mereset password akun Anda.\n";
        $message .= "Silakan klik link di bawah ini untuk membuat password baru:\n\n";
        $message .= $resetLink . "\n\n";
        $message .= "Jika Anda tidak meminta reset password, abaikan pesan ini.";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'target' => $phone,
                'message' => $message,
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . env('FONNTE_TOKEN'),
            ),
        ));

        curl_exec($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            // Jika error, log atau tampilkan
            Log::error('WhatsApp API Error: ' . $error_msg);
            return back()->withErrors(['no_telp' => 'Gagal mengirim pesan WhatsApp. Coba beberapa saat lagi.']);
        }
        curl_close($curl);
        // arahkan ke login
        return redirect('/login')->with('success', 'Permintaan reset password berhasil. Silakan cek WhatsApp Anda untuk mendapatkan password baru.');
    }

    public function formResetPassword(Request $request, string $token)
    {
        // Lempar token dan no_telp (dari query URL) ke halaman Vue
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'no_telp' => $request->query('no_telp'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function submitResetPasswordRequest(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'token' => 'required',
            'no_telp' => 'required|string|exists:penggunano_telp',
            'password' => 'required|min:8|confirmed',
        ]);

        // 2. Cek kesesuaian token dan no_telp di database
        $resetRecord = DB::table('password_reset_tokens')
            ->where('no_telp', $request->no_telp)
            ->where('token', $request->token)
            ->first();

        // Jika tidak ada record yang cocok
        if (!$resetRecord) {
            return back()->withErrors(['no_telp' => 'Token reset password tidak valid.']);
        }

        // 3. Cek apakah token sudah kedaluwarsa (misal batas waktunya 60 menit)
        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('no_telp', $request->no_telp)->delete();
            return back()->withErrors(['no_telp' => 'Link reset password sudah kedaluwarsa. Silakan minta link baru.']);
        }

        // 4. Jika valid, update password user
        $user = Pengguna::where('no_telp', $request->no_telp)->first();
        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        // 5. Hapus token setelah password berhasil diubah agar tidak bisa dipakai lagi
        DB::table('password_reset_tokens')->where('no_telp', $request->no_telp)->delete();

        // 6. Redirect ke halaman login dengan pesan sukses
        return redirect()->route('login')->with('success', 'Password berhasil direset! Silakan login dengan password baru.');
    }
}
