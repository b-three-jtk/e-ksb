<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Mutasi Simpanan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 15mm;
            background: white;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1a3a3a;
            padding-bottom: 15px;
        }
        .header-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a3a3a;
            margin-bottom: 5px;
        }
        .header-subtitle {
            font-size: 11px;
            color: #666;
            margin-bottom: 10px;
        }
        .member-info {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            width: 100%;
            margin-bottom: 20px;
            font-size: 10px;
        }
        .info-block {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .info-label {
            font-weight: bold;
            color: #1a3a3a;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .info-value {
            color: #333;
            padding-left: 10px;
            border-left: 2px solid #059669;
        }
        .info-row {
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        thead {
            background-color: #f3f4f6;
            border-bottom: 2px solid #1a3a3a;
        }
        th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            color: #1a3a3a;
            border: 1px solid #d1d5db;
            background: linear-gradient(180deg, #e5e7eb 0%, #f3f4f6 100%);
        }
        td {
            padding: 7px 8px;
            border: 1px solid #e5e7eb;
            text-align: left;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        tr:hover {
            background-color: #f0fdf4;
        }
        .num-col {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }
        .penyetoran {
            color: #059669;
            font-weight: bold;
        }
        .penarikan {
            color: #dc2626;
            font-weight: bold;
        }
        .saldo {
            background-color: #ecfdf5;
            color: #065f46;
            font-weight: 600;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            border: 1px solid #86efac;
            border-radius: 4px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 10px;
        }
        .summary-label {
            font-weight: bold;
            color: #1a3a3a;
        }
        .summary-value {
            color: #065f46;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .footer-note {
            margin-top: 10px;
            font-style: italic;
            color: #999;
        }
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #999;
            font-style: italic;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-title">Koperasi Syariah Berkah</div>
            <div class="header-subtitle">LAPORAN MUTASI SIMPANAN ANGGOTA</div>
        </div>

        <div class="member-info">
            <div class="info-block">
                <div class="info-row">
                    <div class="info-label">Nama Anggota</div>
                    <div class="info-value">{{ $member['nama'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">No Anggota</div>
                    <div class="info-value">{{ $member['no_anggota'] }}</div>
                </div>
            </div>
            </div>
        </div>

        @if(count($transactions) > 0)
            <table>
                <thead>
                    <tr>
                        <th style="width: 11%;">Tanggal</th>
                        <th style="width: 18%;">Produk / Jenis</th>
                        <th style="width: 13%;">Metode</th>
                        <th style="width: 13%;">Petugas</th>
                        <th style="width: 15%;" class="num-col">Penyetoran</th>
                        <th style="width: 15%;" class="num-col">Penarikan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction['tanggal'] ?? '-' }}</td>
                            <td>
                                <div style="font-weight: 500;">{{ $transaction['produk'] ?? '-' }}</div>
                                <div style="font-size: 9px; color: #666;">{{ $transaction['jenis'] ?? '-' }}</div>
                            </td>
                            <td>{{ $transaction['metode'] ?? '-' }}</td>
                            <td>{{ $transaction['petugas'] ?? '-' }}</td>
                            <td class="num-col penyetoran">
                                @if($transaction['debit'] > 0)
                                    {{ number_format($transaction['debit'], 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="num-col penarikan">
                                @if($transaction['kredit'] > 0)
                                    {{ number_format($transaction['kredit'], 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="summary">
                <div class="summary-row">
                    <span class="summary-label">Total Transaksi :</span>
                    <span class="summary-value">{{ count($transactions) }} transaksi</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Penyetoran :</span>
                    <span class="summary-value">Rp {{ number_format($totalDebit, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Penarikan :</span>
                    <span class="summary-value">Rp {{ number_format($totalKredit, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #86efac;">
                    <span class="summary-label" style="font-size: 11px;">Saldo Akhir :</span>
                    <span class="summary-value" style="font-size: 11px;">Rp {{ number_format(max(0, $endingBalance), 0, ',', '.') }}</span>
                </div>
            </div>
        @else
            <div class="empty-state">
                Tidak ada transaksi dalam periode yang dipilih
            </div>
        @endif

        <div class="footer">
            <div>Laporan ini dicetak dari aplikasi Koperasi Syariah Berkah</div>
            <div>{{ now()->format('d F Y, H:i:s') }}</div>
            <div class="footer-note">
                Dokumen ini adalah catatan resmi dari simpanan Anda. Harap simpan laporan ini untuk referensi.
            </div>
        </div>
    </div>
</body>
</html>
