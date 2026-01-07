<!DOCTYPE html>

<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Permohonan Keanggotaan Ditolak</title>
</head>
<body style="margin:0; padding:0; background-color:#f9fafb; font-family:Arial, Helvetica, sans-serif; color:#333333;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:24px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0"
                       style="max-width:600px; background-color:#ffffff; border-radius:8px; overflow:hidden;">
                <tr>
                    <td style="background-color:#dc2626; padding:20px;">
                        <h2 style="margin:0; color:#ffffff; font-size:20px;">
                            Permohonan Keanggotaan Ditolak
                        </h2>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px; background-color:#f9fafb; font-size:15px; line-height:1.6;">
                        <p>Halo <strong>{{ $user->name }}</strong>,</p>

                        <p>
                            Terima kasih atas minat Anda untuk bergabung dengan
                            <strong>Koperasi Syariah Warga Polban</strong>.
                            Setelah melakukan peninjauan, dengan berat hati kami memberitahukan bahwa
                            permohonan keanggotaan Anda belum dapat kami setujui pada saat ini.
                        </p>

                        @if($note)
                        <div style="
                            background-color:#fef2f2;
                            border-left:4px solid #dc2626;
                            padding:16px;
                            margin:16px 0;
                        ">
                            <strong>Catatan:</strong>
                            <p style="margin:8px 0 0 0;">
                                {{ $note }}
                            </p>
                        </div>
                        @endif

                        <p>
                            Jika Anda memiliki pertanyaan atau membutuhkan klarifikasi lebih lanjut,
                            silakan menghubungi pengurus koperasi.
                        </p>

                        <p style="margin-top:24px;">
                            Terima kasih,<br>
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
