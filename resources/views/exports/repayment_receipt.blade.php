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
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            background: white;
            padding: 20px;
        }

        .receipt {
            max-width: 400px;
            margin: 0 auto;
            border: 1px solid #333;
            padding: 20px;
            background: white;
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header .title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .header .subtitle {
            font-size: 11px;
            color: #666;
        }

        .receipt-number {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 1px dashed #999;
            padding-bottom: 10px;
            font-size: 11px;
        }

        .section {
            margin-bottom: 12px;
        }

        .section-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 11px;
        }

        .label {
            flex: 1;
            text-align: left;
        }

        .value {
            flex: 1;
            text-align: right;
            font-weight: 500;
        }

        .divider {
            border-bottom: 1px dashed #999;
            margin: 10px 0;
        }

        .total-section {
            background: #f5f5f5;
            padding: 8px;
            margin: 10px 0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 12px;
            padding: 5px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px dashed #333;
            font-size: 10px;
            color: #666;
        }

        .footer-text {
            margin: 5px 0;
        }

        .signature-area {
            margin-top: 15px;
            text-align: center;
            font-size: 10px;
        }

        .signature-line {
            margin-top: 25px;
            border-top: 1px solid #333;
            width: 150px;
            margin-left: auto;
            margin-right: auto;
            padding-top: 3px;
        }

        .note-box {
            background: #fffacd;
            border: 1px solid #ddd;
            padding: 6px;
            margin: 10px 0;
            font-size: 9px;
            line-height: 1.3;
        }

        @media print {
            body {
                padding: 0;
                margin: 0;
            }

            .receipt {
                border: none;
                max-width: 100%;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="title">KOPERASI SYARIAH BERKAH</div>
            <div class="subtitle"></div>
        </div>

        <!-- Receipt Number & Date -->
        <div class="receipt-number">
            No: {{ $receipt_code }}<br>
            Tgl: {{ $transaction_date }}<br>
            Jam: {{ $transaction_datetime ?? now()->format('H:i:s') }}
        </div>

        <!-- Jenis Transaksi -->
        <div class="section">
            <div class="row">
                <span class="label">JENIS TRANSAKSI</span>
            </div>
            <div class="row">
                <span class="value" style="font-weight: bold; text-align: center;">Pelunasan Sebelum Jatuh Tempo</span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Data Anggota -->
        <div class="section">
            <div class="section-title">DATA ANGGOTA</div>
            <div class="row">
                <span class="label">No. Anggota</span>
                <span class="value">{{ $member_code }}</span>
            </div>
            <div class="row">
                <span class="label">Nama</span>
                <span class="value">{{ $member_name }}</span>
            </div>
            <div class="row">
                <span class="label">Kontak</span>
                <span class="value">{{ $member_contact }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Data Pembiayaan -->
        <div class="section">
            <div class="section-title">DATA PEMBIAYAAN</div>
            <div class="row">
                <span class="label">No. Transaksi</span>
                <span class="value">{{ $financing_code }}</span>
            </div>
            <div class="row">
                <span class="label">Produk</span>
                <span class="value">{{ $product_name }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Rincian Pembayaran -->
        <div class="section">
            <div class="section-title">RINCIAN PEMBAYARAN</div>
            <div class="row">
                <span class="label">Pokok</span>
                <span class="value">Rp {{ number_format($principal_paid, 0, ',', '.') }}</span>
            </div>
            <div class="row">
                <span class="label">Margin</span>
                <span class="value">Rp {{ number_format($margin_paid, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Total Pelunasan -->
        <div class="total-section">
            <div class="total-row">
                <span>TOTAL PELUNASAN</span>
                <span>Rp {{ number_format($total_paid, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Detail Perhitungan Syariah (opsional) -->
        @if(isset($tsaman_naqdy) && isset($qimah_ismiyyah) && isset($qimah_haliyyah))
        <div class="section">
            <div class="section-title">PERHITUNGAN MURABAHAH</div>
            <div class="detail-row">
                <span class="label">Tsaman Naqdy</span>
                <span class="value">Rp {{ number_format($tsaman_naqdy, 0, ',', '.') }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Qimah Ismiyyah</span>
                <span class="value">Rp {{ number_format($qimah_ismiyyah, 0, ',', '.') }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Qimah Haliyyah</span>
                <span class="value">Rp {{ number_format($qimah_haliyyah, 0, ',', '.') }}</span>
            </div>
        </div>
        @endif

        <!-- Metode Pembayaran -->
        <div class="section">
            <div class="row">
                <span class="label">Metode Pembayaran</span>
                <span class="value">{{ $payment_method }}</span>
            </div>
        </div>

        <!-- Note -->
        <div class="note-box">
            <strong>Catatan:</strong> Pelunasan sebelum jatuh tempo telah diterima dengan perhitungan berdasarkan akad murabahah. Semua cicilan yang belum dibayar dinyatakan hangus.
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">Terima kasih atas kepercayaan Anda</div>
            <div class="footer-text">Semoga berkah dan bermanfaat</div>
        </div>

        <!-- Signature Area -->
        <div class="signature-area">
            <div style="margin-top: 20px; font-size: 10px;">Petugas: {{ $officer_name }}</div>
            <div class="signature-line"></div>
            <div style="margin-top: 3px; font-size: 9px;">{{ now()->format('d-m-Y') }}</div>
        </div>

        <!-- Print Footer -->
        <div style="text-align: center; margin-top: 15px; font-size: 8px; color: #999; border-top: 1px solid #ddd; padding-top: 10px;">
            *** Terima kasih telah mempercayai kami ***<br>
            Simpan struk ini sebagai bukti sah transaksi
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            // Auto print hanya di development, jangan di production
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                // Uncomment untuk auto print
                // window.print();
            }
        });
    </script>
</body>
</html>
