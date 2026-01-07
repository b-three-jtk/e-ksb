<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Akun Disetujui</title>
</head>
<body style="margin:0; padding:0; background-color:#f9fafb; font-family:Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb; padding:24px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background-color:#ffffff; border-radius:12px; padding:32px;">

                <tr>
                    <td style="text-align:center; padding-bottom:24px;">
                        <h2 style="margin:0; color:#f97316;">
                            🎉 Akun Anda Telah Disetujui
                        </h2>
                    </td>
                </tr>

                <tr>
                    <td style="color:#111827; font-size:15px; line-height:1.6;">
                        <p>Halo <strong>{{ $user->name }}</strong>,</p>

                        <p>
                            Dengan senang hati kami informasikan bahwa
                            <strong>permohonan keanggotaan Anda di Koperasi Syariah Warga Polban telah disetujui</strong>.
                            Anda kini resmi terdaftar sebagai anggota.
                        </p>

                        <p>
                            <strong>Detail Akun Anda:</strong><br>
                            Email: {{ $user->email }}
                        </p>

                        <p>
                            Silakan login ke aplikasi untuk mulai menggunakan layanan Koperasi Syariah Warga Polban.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:24px 0;">
                        <a href="{{ config('app.url') }}/login"
                           style="
                                display:inline-block;
                                background-color:#f97316;
                                color:#ffffff;
                                padding:14px 28px;
                                border-radius:8px;
                                text-decoration:none;
                                font-weight:600;
                           ">
                            Login ke KopSy Campus
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="font-size:13px; color:#6b7280; line-height:1.5;">
                        <p>
                            Jika tombol di atas tidak dapat diklik, silakan salin dan buka tautan berikut:
                            <br>
                            <a href="{{ config('app.url') }}/login" style="color:#f97316;">
                                {{ config('app.url') }}/login
                            </a>
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding-top:24px; font-size:14px; color:#374151;">
                        <p>
                            Terima kasih atas kepercayaan Anda.<br>
                            Salam hangat,<br>
                            <strong>Pengurus Koperasi Syariah Warga Polban</strong>
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
