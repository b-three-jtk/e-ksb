<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Mutasi Simpanan</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 9mm 12mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Manrope';
            font-size: 10.5px;
            line-height: 1.45;
            color: #1d2939;
            background: #f9fafb;
        }

        .container {
            max-width: 190mm;
            margin: 0 auto;
            padding: 0;
            background: #ffffff;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            padding: 4px 0 12px;
            border-bottom: 1.5px solid #98a2b3;
        }

        .header-title {
            font-size: 18px;
            font-weight: 700;
            color: #1d2939;
            letter-spacing: -0.01em;
            margin-bottom: 3px;
        }

        .header-subtitle {
            font-size: 11px;
            color: #344054;
            margin-bottom: 4px;
        }

        .header-address {
            font-size: 8.5px;
            color: #667085;
        }

        .member-info {
            margin-bottom: 12px;
            width: 50%;
        }

        .member-card {
            display: flex;
            align-items: center;
            gap: 14px;
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, #eef4ff 0%, #e8eefc 100%);
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
            box-sizing: border-box;
        }

        .member-avatar {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            flex: 0 0 34px;
            background: #dbeafe;
            color: #1d2939;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
        }

        .member-copy {
            flex: 1;
        }

        .member-name {
            font-size: 12.5px;
            font-weight: 700;
            color: #1d2939;
            margin-bottom: 2px;
        }

        .member-meta {
            font-size: 9px;
            color: #008e43;
            font-weight: 600;
        }

        .member-meta .dot {
            color: #98a2b3;
            padding: 0 4px;
        }

        .member-note {
            font-size: 8.5px;
            color: #667085;
            margin-top: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }

        thead {
            background: #f8fafc;
        }

        th {
            padding: 11px 10px;
            text-align: left;
            font-weight: 700;
            color: #1d2939;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        td {
            padding: 10px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            color: #344054;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) {
            background-color: #fcfcfd;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .num-col {
            text-align: right;
            font-family: 'Manrope', 'Plus Jakarta Sans';
            font-weight: 500;
            white-space: nowrap;
        }

        .penyetoran {
            color: #007031;
            font-weight: 700;
        }

        .penarikan {
            color: #f04438;
            font-weight: 700;
        }

        .saldo {
            background-color: #ecfdf5;
            color: #007031;
            font-weight: 600;
        }

        .summary {
            margin-top: 4px;
            padding-top: 10px;
            border-top: 2px solid #007031;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 10px;
            color: #475467;
        }

        .summary-label {
            font-weight: 600;
            color: #667085;
        }

        .summary-value {
            color: #1d2939;
            font-weight: bold;
            font-family: 'Manrope', 'Plus Jakarta Sans';
        }

        .summary-divider {
            margin: 8px 0 8px;
            border-top: 1px solid #e2e8f0;
        }

        .summary-total {
            color: #007031;
            font-size: 11px;
        }

        .footer {
            margin-top: 18px;
            padding-top: 10px;
            text-align: center;
            font-size: 9px;
            color: #667085;
        }

        .footer-note {
            margin-top: 7px;
            font-style: italic;
            color: #98a2b3;
            font-size: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 20px 12px;
            color: #667085;
            font-style: italic;
            border: 1px dashed #d0d5dd;
            border-radius: 12px;
            background: #fcfcfd;
        }

        .page-break {
            page-break-after: always;
        }

        .table-shell {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.03);
        }

        .table-shell table {
            border: 0;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-title">Koperasi Syariah Berkah</div>
            <div class="header-subtitle">Laporan Mutasi Simpanan Anggota</div>
            <div class="header-address">Komplek Puri Cipageran Indah 2, RW 21, Desa Ngamprah, Kec. Tanimulya, Kabupaten Bandung Barat</div>
        </div>

        <div class="member-info">
            <div class="member-card">
                <div class="member-copy">
                    <div class="member-name">{{ $member['nama'] ?? '-' }}</div>
                    <div class="member-meta">
                        {{ $member['no_anggota'] ?? '-' }}
                        <span class="dot">•</span>
                        Anggota sejak {{ $member['sejak'] ?? ($member['tanggal_bergabung'] ?? '-') }}
                    </div>
                </div>
            </div>
        </div>

        @if(count($transactions) > 0)
            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%;">Tanggal</th>
                            <th style="width: 19%;">Produk</th>
                            <th style="width: 14%;">Metode</th>
                            <th style="width: 20%;">Petugas</th>
                            <th style="width: 16%;" class="num-col">Penyetoran</th>
                            <th style="width: 16%;" class="num-col">Penarikan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction['tanggal'] ?? '-' }}</td>
                                <td>
                                    <div style="font-weight: 600; color: #1d2939;">{{ $transaction['produk'] ?? '-' }}</div>
                                    <div style="font-size: 9px; color: #667085;">{{ $transaction['jenis'] ?? '-' }}</div>
                                </td>
                                <td>{{ $transaction['metode'] ?? '-' }}</td>
                                <td>{{ $transaction['petugas'] ?? '-' }}</td>
                                <td class="num-col penyetoran">
                                    @if(($transaction['debit'] ?? 0) > 0)
                                        + Rp {{ number_format($transaction['debit'], 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="num-col penarikan">
                                    @if(($transaction['kredit'] ?? 0) > 0)
                                        - Rp {{ number_format($transaction['kredit'], 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="summary">
                <div class="summary-row">
                    <span class="summary-label">Total Transaksi</span>
                    <span class="summary-value">{{ count($transactions) }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Penyetoran</span>
                    <span class="summary-value">Rp {{ number_format($totalDebit, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Penarikan</span>
                    <span class="summary-value">Rp {{ number_format($totalKredit, 0, ',', '.') }}</span>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-row">
                    <span class="summary-label summary-total">Saldo Akhir</span>
                    <span class="summary-value summary-total">Rp {{ number_format(max(0, $endingBalance), 0, ',', '.') }}</span>
                </div>
            </div>
        @else
            <div class="empty-state">
                Tidak ada transaksi dalam periode yang dipilih
            </div>
        @endif

        <div class="footer">
            <div>Laporan ini dicetak dari aplikasi Koperasi Syariah Berkah</div>
            <div>{{ now()->locale('id')->translatedFormat('d F Y, H:i:s') }}</div>
            <div class="footer-note">
                Bukti transaksi hanya dapat di akses pada aplikasi.
            </div>
        </div>
    </div>
</body>
</html>
