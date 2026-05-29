<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran Angsuran</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
            line-height: 1.2;
            background: white;
            padding: 12px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .container {
            border: 1px solid black;
            padding: 12px;
            max-width: 100%;
            margin: auto;
        }

        .kwitansi-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            text-decoration: underline;
            margin: 25px 0;
        }

        .detail-table td {
            padding: 2px 0;
            font-size: 11px;
        }

        .detail-table td:first-child {
            width: 180px;
        }

        .detail-table td:nth-child(2) {
            width: 10px;
        }

        .rincian-title {
            font-weight: bold;
            margin-top: 15px;
            font-size: 12px;
        }

        .rincian-table {
            margin-top: 5px;
        }

        .rincian-table td {
            padding: 2px 0;
            font-size: 10px;
        }

        .rincian-table td:nth-child(2) {
            width: 260px;
        }

        .rincian-table td:nth-child(3) {
            text-align: right;
            white-space: nowrap;
        }

        .catatan-box {
            margin-top: 15px;
            font-size: 10px;
        }

        @media print {
            body {
                padding: 0;
                margin: 0;
            }

            .container {
                border: 1px solid black;
                max-width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <!-- HEADER -->
        <table>
            <tr>
                <td rowspan="4" style="width: 80px">
                    <img
                        style="width: 55px"
                        src="{{ $receipt['logo'] }}"
                        alt="Logo"
                    >
                </td>

                <td>
                    <strong>KOPERASI SYARIAH BERKAH</strong>
                </td>

                <td style="width: 140px">
                    Tanggal Transaksi
                </td>

                <td>
                    {{ $receipt['tanggal_angsuran'] }}
                </td>
            </tr>

            <tr>
                <td>MT. Mutiara Hikmah</td>

                <td>No. Pembiayaan</td>

                <td>
                    {{ $receipt['nomor_pembiayaan'] }}
                </td>
            </tr>

            <tr>
                <td>DKM Masjid Al-Hikmah</td>

                <td>No. Anggota</td>

                <td>
                    {{ $receipt['no_anggota'] }}
                </td>
            </tr>

            <tr>
                <td>
                    Komp. Puri Cipageran Indah 2 RW 21
                </td>

                <td>Petugas</td>

                <td>
                    {{ $receipt['petugas'] }}
                </td>
            </tr>
        </table>

        <!-- TITLE -->
        <div class="kwitansi-title">
            KUITANSI PEMBAYARAN ANGSURAN
        </div>

        <!-- DETAIL -->
        <table class="detail-table">

            <tr>
                <td>Telah Terima dari</td>
                <td>:</td>
                <td>
                    {{ $receipt['no_anggota'] }} - {{ $receipt['diterima_dari'] }}
                </td>
            </tr>

            <tr>
                <td>Uang Sejumlah</td>
                <td>:</td>
                <td>
                    Rp {{ number_format($receipt['sejumlah_uang'], 0, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td>Untuk Pembayaran</td>
                <td>:</td>
                <td>
                    {{ $receipt['items'][0]['keterangan'] }}
                </td>
            </tr>

            <tr>
                <td>Metode Pembayaran</td>
                <td>:</td>
                <td>
                    {{ $receipt['payment_method'] ?? '-' }}
                </td>
            </tr>

            <tr>
                <td>Jatuh Tempo Berikutnya</td>
                <td>:</td>
                <td>
                    {{ $receipt['jatuh_tempo'] }}
                </td>
            </tr>

            <tr>
                <td>Status Pembiayaan</td>
                <td>:</td>
                <td>
                    {{ $receipt['status'] }}
                </td>
            </tr>

        </table>

        <!-- RINCIAN -->
        <div class="rincian-title">
            Rincian Pembiayaan
        </div>

        <table class="rincian-table">

            <tr>
                <td></td>
                <td>Harga Perolehan</td>
                <td>
                    Rp {{ number_format($receipt['harga_perolehan'], 0, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td></td>
                <td>Margin</td>
                <td>
                    Rp {{ number_format($receipt['margin'], 0, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td></td>
                <td>Harga Jual</td>
                <td>
                    Rp {{ number_format($receipt['harga_jual'], 0, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td></td>
                <td>Total Angsuran Dibayar</td>
                <td>
                    Rp {{ number_format($receipt['total_angsuran'], 0, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td></td>
                <td>Sisa Hutang</td>
                <td>
                    Rp {{ number_format($receipt['sisa_hutang'], 0, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td></td>
                <td>
                    <strong>Total Pembayaran</strong>
                </td>

                <td>
                    <strong>
                        Rp {{ number_format(collect($receipt['items'])->sum('jumlah'), 0, ',', '.') }}
                    </strong>
                </td>
            </tr>

        </table>

        <!-- CATATAN -->
        <div class="catatan-box">
            <strong>Catatan:</strong><br>
            {{ $receipt['catatan'] }}
        </div>

    </div>

</body>
</html>