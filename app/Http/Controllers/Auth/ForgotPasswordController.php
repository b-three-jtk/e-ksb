<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ForgotPasswordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function submitRequest(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|exists:users,phone_number'
        ], [
            'phone_number.exists' => 'Nomor WhatsApp tidak ditemukan di sistem kami.'
        ]);

        $phone = $request->phone_number;

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone_number' => $phone],
            ['token' => $token, 'created_at' => now()]
        );

        $resetLink = route('password.reset', ['token' => $token, 'phone_number' => $phone]);

        $message = "*RESET PASSWORD*\n\n";
        $message .= "Halo, kami menerima permintaan untuk mereset password akun Anda.\n";
        $message .= "Silakan klik link di bawah ini untuk membuat password baru:\n\n";
        $message .= $resetLink . "\n\n";
        $message .= "Jika Anda tidak meminta reset password, abaikan pesan ini.";

        $curl = curl_init();

        Log::info('Mengirim pesan WhatsApp ke ' . $phone . ' dengan token: ' . $token);

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
            return back()->withErrors(['phone_number' => 'Gagal mengirim pesan WhatsApp. Coba beberapa saat lagi.']);
        }
        curl_close($curl);
        // arahkan ke login
        return redirect('/login')->with('success', 'Permintaan reset password berhasil. Silakan cek WhatsApp Anda untuk mendapatkan password baru.');
    }
}
