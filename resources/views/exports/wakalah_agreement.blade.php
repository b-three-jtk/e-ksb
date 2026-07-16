<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perjanjian Wakalah</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { font-family: 'Times New Roman', Times, serif; font-size: 14px; line-height: 1.5; background: white; padding: 20px; }
        .table-header { border-collapse: collapse; width: 100%; border: 1px #000 solid; }
        .table-header td { padding: 3px 0; border: 1px #000 solid; }
        .content { margin-top: 20px; }
        .signatures { width: 100%; margin-top: 40px; border-collapse: collapse; }
        .signatures td { border: 1px solid black; text-align: center; padding: 10px; vertical-align: top; width: 33.33%; font-weight: bold; }
        ol { padding-left: 20px; }
        .title { text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <table class="table-header">
        <tr>
            <td rowspan="2" style="width: 15%; text-align: center; border: 1px #000 solid;">
                <img style="width: 70px;" src="{{ $src }}" alt="Logo KSB">
            </td>
            <td class="title" colspan="2" style="border-bottom: 1px #000 solid;font-size:14px; padding: 5px;">
                KOPERASI SYARIAH BERKAH<br>
                <span style="font-weight: normal; font-size: 11px;">MT. Mutiara Hikmah<br>DEWAN KELUARGA MASJID AL-HIKMAH<br>Jl. Komp. Puri Cipageran Indah 2 RW 21</span>
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold;text-align: center;font-size:12px;border: 1px #000 solid; width: 50%;">
                PERJANJIAN WAKALAH
            </td>
            <td style="font-weight: bold;text-align:center;font-size:12px; border: 1px #000 solid;">
                No. Dokumen: <span style="font-weight:normal;">{{ $pembiayaan->kode_pembiayaan }}/KSB-WAK/{{ \Carbon\Carbon::parse($pembiayaan->tgl_akad)->format('m') }}/{{ \Carbon\Carbon::parse($pembiayaan->tgl_akad)->format('Y') }}</span>
            </td>
        </tr>
    </table>

    <div class="content">
        <p>Perjanjian <strong>Wakalah</strong> ini dibuat dan ditandatangani pada hari ini, {{ \Carbon\Carbon::parse($pembiayaan->tgl_akad)->translatedFormat('l') }}, tanggal {{ \Carbon\Carbon::parse($pembiayaan->tgl_akad)->translatedFormat('j F Y') }} di Kota Bandung, Provinsi Jawa Barat antara :</p>
        
        <ol>
            <li style="margin-bottom: 10px;">
                {{ $ketuaKoperasi }} dengan Jabatan Ketua Koperasi Syariah Berkah (KSB), yang bertindak untuk dan atas nama KSB, berkedudukan di Bandung yang selanjutnya dalam perjanjian ini disebut sebagai <strong>KSB</strong>
            </li>
            <li>
                {{ $pembiayaan->anggota->user->nama }}, Anggota Koperasi Syariah Berkah (KSB). Selanjutnya dalam perjanjian ini disebut sebagai <strong>anggota</strong>.
            </li>
        </ol>

        <p style="margin-top: 20px;">Kedua belah pihak telah mencapai kesepakatan dan setuju untuk mengadakan wakalah selanjutnya ketentuan-ketentuan dan syarat-syarat sebagai berikut :</p>

        <ol>
            <li>KSB dan anggota telah sepakat untuk melaksanakan akad murabahah.</li>
            <li>Sebelum melaksanakan akad murabahah tersebut, untuk kemudahan teknis KSB dan kesesuaian dengan kebutuhan serta spesifikasi yang dibutuhkan oleh anggota, maka KSB menitipkan dana pembelian barang yang diajukan kepada anggota. Maka dengan ini telah terjadi akad wakalah antara KSB dengan anggota.</li>
            <li>Dana yang dititipkan kepada anggota hanya diperuntukkan untuk pembelian barang.</li>
            <li>Penggantian jenis barang dan spesifikasinya dibolehkan bila ada persetujuan tertulis dari KSB.</li>
            <li>Pembelian harus disertai dengan bukti pembelian sebagai bukti, yang diberikan kepada petugas KSB pada pertemuan minggu berikutnya.</li>
        </ol>

        <table class="signatures">
            <tr>
                <td>
                    <div style="margin-bottom: 80px;">Ketua Murabahah KSB</div>
                    <div>({{ $ketuaMurabahah }})</div>
                </td>
                <td>
                    <div style="margin-bottom: 80px;">Anggota KSB</div>
                    <div>({{ $pembiayaan->anggota->user->nama }})</div>
                </td>
                <td>
                    <div style="margin-bottom: 80px;">Ketua KSB</div>
                    <div>({{ $ketuaKoperasi }})</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
