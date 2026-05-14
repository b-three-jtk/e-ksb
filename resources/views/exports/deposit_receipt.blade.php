<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kwitansi Penyetoran</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }

        body {
            margin: 0;
            width: 72mm;
            color: #111;
            font-size: 11px;
            font-family: DejaVu Sans Mono, DejaVu Sans, monospace;
        }

        .receipt {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 10px;
        }

        .center { text-align: center; }

        .title {
            font-weight: 700;
            font-size: 12px;
        }

        .subtitle {
            font-size: 9px;
            color: #555;
            margin-top: 2px;
        }

        .badge {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 2px;
            background: #111;
            color: #fff;
            font-size: 10px;
            letter-spacing: .04em;
        }

        hr {
            border: none;
            border-top: 1px dashed #bbb;
            margin: 8px 0;
        }

        .row,
        .rowb {
            width: 100%;
            border-collapse: collapse;
            margin: 2.5px 0;
        }

        .row td:first-child,
        .rowb td:first-child {
            width: 44%;
            color: #444;
        }

        .row td:last-child,
        .rowb td:last-child {
            width: 56%;
            text-align: right;
            font-weight: 600;
        }

        .rowb td {
            font-weight: 700 !important;
            font-size: 12px;
        }

        .box {
            border: 1px solid #d1d5db;
            border-radius: 3px;
            padding: 9px 10px;
            margin: 6px 0;
        }

        .muted td {
            color: #555;
            font-weight: 400 !important;
        }

        .plus {
            color: #16a34a;
        }

        .saldo-now {
            border-top: 1px dashed #bbb;
            padding-top: 5px;
        }

        .saldo-now td {
            font-size: 12px;
            font-weight: 700 !important;
        }

        .footer {
            margin-top: 8px;
            font-size: 10px;
            color: #555;
            text-align: center;
            line-height: 1.45;
        }

        .printed-at {
            font-size: 9px;
            color: #8f8f8f;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    @php
        $trxDate = \Carbon\Carbon::parse($struk['tanggal'] ?? now())->locale('id');
        $tanggalFormatted = $trxDate->translatedFormat('d M Y') . '  ' . $trxDate->format('H:i');
        $waktuCetak = now()->locale('id')->translatedFormat('d/m/Y H:i') . ' WIB';
    @endphp

    <div class="receipt">
        <div class="center">
            <div class="title">Koperasi Syariah Berkah</div>
            <div class="subtitle">Komplek Puri Cipageran Indah 2, RW 21, Desa Ngamprah, Kec. Tanimulya, Kabupaten Bandung Barat</div>
        </div>

        <hr>

        <div class="center">
            <span class="badge">KWITANSI PENYETORAN SIMPANAN</span>
        </div>

        <hr>

        <table class="row"><tr><td>No. Transaksi</td><td><strong>{{ $struk['no_transaksi'] ?? '-' }}</strong></td></tr></table>
        <table class="row"><tr><td>Tanggal</td><td>{{ $tanggalFormatted }}</td></tr></table>
        <table class="row"><tr><td>Kasir / Pengurus</td><td>{{ $struk['pengurus'] ?? '-' }}</td></tr></table>

        <hr>

        <table class="row"><tr><td>Nama Anggota</td><td>{{ $struk['nama_anggota'] ?? '-' }}</td></tr></table>
        <table class="row"><tr><td>No. Anggota</td><td>{{ $struk['no_anggota'] ?? '-' }}</td></tr></table>

        <hr>

        <table class="rowb"><tr><td>Jenis Simpanan</td><td>{{ $struk['jenis'] ?? '-' }}</td></tr></table>
        <table class="row"><tr><td>Metode</td><td>{{ $struk['metode'] ?? '-' }}</td></tr></table>

        @if(($struk['metode'] ?? '') === 'Non-Tunai')
            <table class="row"><tr><td>Bank</td><td>{{ $struk['bank_name'] ?? '-' }}</td></tr></table>
            <table class="row"><tr><td>Atas Nama</td><td>{{ $struk['account_name'] ?? '-' }}</td></tr></table>
            <table class="row"><tr><td>No. Rekening</td><td>{{ $struk['account_number'] ?? '-' }}</td></tr></table>
        @endif

        <hr>

        <table class="rowb">
            <tr>
                <td>NOMINAL SETOR</td>
                <td>Rp {{ number_format((float) ($struk['nominal'] ?? 0), 0, ',', '.') }}</td>
            </tr>
        </table>

        <hr>

        <div style="font-weight:700; font-size:11px; margin-bottom:4px;">SALDO {{ strtoupper($struk['jenis'] ?? '-') }}</div>
        <div class="box">
            <table class="row muted"><tr><td>Saldo sebelum</td><td>Rp {{ number_format((float) ($struk['saldo_sebelum'] ?? 0), 0, ',', '.') }}</td></tr></table>
            <table class="row"><tr><td>+ Setoran ini</td><td class="plus">+ Rp {{ number_format((float) ($struk['nominal'] ?? 0), 0, ',', '.') }}</td></tr></table>
            <table class="row saldo-now"><tr><td>Saldo sekarang</td><td>Rp {{ number_format((float) ($struk['saldo_sesudah'] ?? 0), 0, ',', '.') }}</td></tr></table>
        </div>

        <hr>

        <div class="footer">
            <div>Terima kasih atas setoran Anda</div>
            <div>Simpan kwitansi ini sebagai bukti transaksi</div>
            <div class="printed-at">Dicetak: {{ $waktuCetak }}</div>
        </div>
    </div>
</body>
</html>