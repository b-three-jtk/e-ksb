<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pelunasan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            line-height: 1.5;
            background: white;
            padding: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        table td {
            padding: 3px 0;
        }

        .container {
            padding: 20px;
            max-width: 600px;
            margin: auto;
        }

        .header-logo {
            flex-shrink: 0;
        }

        .kwitansi-title {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            text-decoration: underline;
            margin: 15px 0;
        }

        .detail-table td {
            padding: 4px 0;
            font-size:9px;
        }

        .detail-table td:first-child {
            width: 150px;
        }

        .detail-table td:nth-child(2) {
            width: 10px;
        }

        .rincian-title {
            font-weight: bold;
            margin-top: 10px;
            font-size: 12px;
        }

        .rincian-table {
            margin-top: 5px;
        }

        .rincian-table td {
            padding: 3px 0;
            font-size: 11px;
        }

        .rincian-table td:nth-child(2) {
            width: 200px;
        }

        .rincian-table td:nth-child(3) {
            text-align: right;
        }

        @media print {
            body {
                padding: 0;
                margin: 0;
            }

            .container {
                max-width: 100%;
            }
        }

        .table-header td {
            border: 1px #000 solid;
        }
        .title {
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <table class="table-header">
            <tr>
                <td rowspan="2"><img style="width: 70px; margin-left: 30px;" src="{{ $logo }}" alt="Logo"></td>
                <td class="title" colspan="2" style="border-bottom: 1px #000 solid;font-size:11px;">KOPERASI SYARIAH BERKAH<br>MT. Mutiara Hikmah<br>DEWAN KELUARGA MASJID AL-HIKMAH<br>Jl. Komp. Puri Cipageran Indah 2 RW 21</td>
            </tr>

            <tr>
                <td style="font-weight: bold;text-align: center;font-size:9px;border: 1px #000 solid;">BERITA ACARA PELUNASAN PEMBIAYAAN</td>
                <td style="font-weight: bold;text-align:center;font-size:10px;">No. Dokumen: <span style="font-weight:normal;">{{ $no_transaksi }}</td>
            </tr>
        </table>

        <div class="kwitansi-title">BERITA ACARA PELUNASAN PEMBIAYAAN MURABAHAH</div>

        <table class="detail-table">
            <tr>
                <td colspan="6">Pada hari ini, {{ $hari }} tanggal {{ $tanggal }} bulan {{ $bulan }} tahun {{ $tahun }}, kami yang bertanda tangan di bawah ini:</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">PIHAK PERTAMA</td>
            </tr>
            <tr>
                <td>Nama Lengkap</td>
                <td>:</td>
                <td>{{ $nama_pengurus }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>:</td>
                <td>{{ $jabatan_pengurus }}</td>
            </tr>
            <tr>
                <td colspan="6">Bertindak untuk dan atas nama Koperasi Syariah Berkah (KSB)</td>
            </tr>

            <tr>
                <td style="font-weight: bold;">PIHAK KEDUA</td>
            </tr>
            <tr>
                <td>Nama Lengkap</td>
                <td>:</td>
                <td>{{ $nama_anggota }}</td>
            </tr>
            <tr>
                <td>Nomor Anggota</td>
                <td>:</td>
                <td>{{ $no_anggota }}</td>
            </tr>
            <tr>
                <td>Nomor Pembiayaan</td>
                <td>:</td>
                <td>{{ $financing_transaction_code }}</td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td>:</td>
                <td colspan="2">{{ $alamat }}</td>
            </tr>
            <tr>
                <td>Nomor Telepon/HP</td>
                <td>:</td>
                <td>{{ $no_telp }}</td>
            </tr>
            <tr>
                <td colspan="6">Bahwa <span style="font-weight: bold;">PIHAK KEDUA</span> merupakan anggota KSB yang telah memperoleh pembiayaan murabahah berdasarkan akad murabahah dengan rincian sebagai berikut:</td>
            </tr>
            <tr>
                <td>Tanggal Akad Murabahah</td>
                <td>:</td>
                <td>{{ $no_telp }}</td>
            </tr>
            <tr>
                <td>Nilai Harga Perolehan</td>
                <td>:</td>
                <td>Rp{{ number_format($harga_perolehan, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Margin Keuntungan Koperasi</td>
                <td>:</td>
                <td>Rp{{ number_format($margin_keuntungan, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Harga Jual Murabahah</td>
                <td>:</td>
                <td>Rp{{ number_format($qimah_ismiyyah, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Jangka Waktu Pembiayaan</td>
                <td>:</td>
                <td>{{ $tenor }} {{ $satuan_tenor }}</td>
            </tr>
            <tr>
                <td colspan="6">Berdasarkan hasil verifikasi dan perhitungan pembiayaan, dengan ini dinyatakan bahwa:</td>
            </tr>
            <tr>
                <td colspan="6">
                    <ol style="margin-top: 5px; margin-bottom: 0; padding-left: 20px;">
                        <li><span style="font-weight: bold;">PIHAK KEDUA</span> telah melakukan pelunasan seluruh kewajiban pembiayaan murabahah kepada <span style="font-weight: bold;">PIHAK PERTAMA</span> sesuai dengan ketentuan dan perhitungan yang telah ditetapkan.</li>
                        <li>Pelunasan pembiayaan dilakukan pada tanggal {{ $tanggal }} {{ $bulan }} {{ $tahun }} dengan nilai pelunasan sebesar Rp{{ number_format($total_paid_amount, 0, ',', '.') }}.</li>
                        <li>Dengan dilakukannya pelunasan tersebut, maka <span style="font-weight: bold;">seluruh kewajiban PIHAK KEDUA kepada PIHAK PERTAMA dinyatakan telah lunas dan akad murabahah dinyatakan berakhir.</span></li>
                        <li>Sejak tanggal pelunasan ini, <span style="font-weight: bold;">PIHAK PERTAMA tidak lagi memiliki tuntutan keuangan terhadap PIHAK KEDUA</span> sehubungan dengan pembiayaan murabahah dimaksud.</li>
                        <li>Segala dokumen, jaminan, atau hak dan kewajiban lain yang berkaitan dengan pembiayaan murabahah tersebut dinyatakan telah diselesaikan sesuai ketentuan yang berlaku.</li>
                    </ol>
                </td>
            </tr>
        </table>

        <table style="width: 100%; margin-top: 30px; border: none; font-size: 11px;">
            <tr>
                <td style="width: 50%;"></td>
                <td style="width: 50%; padding-left:50px;">Bandung, {{ $tanggal }} {{ $bulan }} {{ $tahun }}</td>
            </tr>
            <tr>
                <td style="padding-top: 20px;font-size:10px;"><strong>PIHAK KEDUA,</strong></td>
                <td style="padding-top: 20px;padding-left:50px;font-size:10px;"><strong>PIHAK PERTAMA,</strong></td>
            </tr>
            <tr>
                <td style="padding-top: 70px;">( ............................................. )</td>
                <td style="padding-top: 70px;padding-left:50px;">( ............................................. )</td>
            </tr>
            <tr>
                <td style="font-weight: bold;font-size:9px;">{{ $nama_anggota }}</td>
                <td style="font-weight: bold;padding-left:50px;font-size:9px;">{{ $nama_pengurus }}</td>
            </tr>
            
            <tr>
                <td style="padding-top: 30px;"><strong>SAKSI 1,</strong></td>
                <td style="padding-top: 30px;padding-left:50px;"><strong>SAKSI 2,</strong></td>
            </tr>
            <tr>
                <td style="padding-top: 70px;">( ............................................. )</td>
                <td style="padding-top: 70px;padding-left:50px;">( ............................................. )</td>
            </tr>
            <tr>
                <td>................................................</td>
                <td style="padding-left:50px;">................................................</td>
            </tr>
        </table>
    </div>

    <script>
        window.addEventListener('load', function () {
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                window.print();
            }
        });
    </script>
</body>

</html>