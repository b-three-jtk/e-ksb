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
            border: 2px solid black;
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
            font-size: 14px;
            text-decoration: underline;
            margin: 15px 0;
        }

        .detail-table td {
            padding: 4px 0;
            font-size: 12px;
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
                border: 1px solid #000;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <table>
            <tr>
                <td rowspan="4"><img style="width: 70px" src="{{ $logo }}" alt="Logo"></td>
                <td class="title">KOPERASI SYARIAH BERKAH</td>
                <td>Tanggal Transaksi:</td>
                <td>{{ $tanggal }}</td>
            </tr>

            <tr>
                <td>MT. Mutiara Hikmah</td>
                <td>No. Transaksi:</td>
                <td>{{ $no_transaksi }}</td>
            </tr>

            <tr>
                <td>DKM Masjid Al-Hikmah</td>
                <td>Petugas:</td>
                <td>{{ $pengurus }}</td>
            </tr>

            <tr>
                <td>Komp. Puri Cipageran Indah 2 RW 21</td>
            </tr>
        </table>

        <div class="kwitansi-title">KUITANSI</div>

        <table class="detail-table">
            <tr>
                <td>Telah Terima dari</td>
                <td>:</td>
                <td>{{ $no_anggota }} - {{ $nama_anggota }}</td>
            </tr>
            <tr>
                <td>Uang Sejumlah</td>
                <td>:</td>
                <td>{{ $repayment_total }}</td>
            </tr>
            <tr>
                <td>Untuk Pembayaran</td>
                <td>:</td>
                <td>Pelunasan Piutang Murabahah Sebelum Jatuh Tempo</td>
            </tr>
            <tr>
                <td>Metode Pembayaran</td>
                <td>:</td>
                <td>{{ $metode }}</td>
            </tr>
            <tr>
                <td>No. Pembiayaan</td>
                <td>:</td>
                <td>{{ $financing_transaction_code }} - {{ $product_name }}</td>
            </tr>
        </table>

        <div class="rincian-title">Rincian Perhitungan</div>
        <table class="rincian-table">
            <tr>
                <td></td>
                <td>Qimah Haliyyah (Harga Jual Saat ini)</td>
                <td>Rp{{ number_format($qimah_haliyyah, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td></td>
                <td>Total Angsuran Dibayar</td>
                <td>Rp{{ number_format($total_paid_amount, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td></td>
                <td><strong>Total Pelunasan</strong></td>
                <td><strong>Rp{{ number_format($repayment_total, 2, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <script>
        window.addEventListener('load', function() {
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                // window.print();
            }
        });
    </script>
</body>
</html>
