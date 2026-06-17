<script setup>
const props = defineProps({
    transaksi: {
        type: Object,
        required: true,
    },
    showPrintButton: {
        type: Boolean,
        default: true,
    },
    namaKoperasi: {
        type: String,
        default: 'Koperasi Syariah Berkah',
    },
})

const formatRp = (val) =>
    'Rp ' + Number(val || 0).toLocaleString('id-ID')

const formatDate = (dateStr) => {
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const formatTime = (dateStr) => {
    const d = new Date(dateStr)
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

function escapeHtml(val) {
    return String(val ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
}

function cetak() {
    // Repayment receipt format
    const murabahahSection = (props.transaksi.tsaman_naqdy && props.transaksi.qimah_ismiyyah && props.transaksi.qimah_haliyyah)
        ? `
        <div style="font-weight:700; font-size:11px; margin-bottom:5px; border-bottom:1px solid #ccc; padding-bottom:3px;">PERHITUNGAN MURABAHAH</div>
        <div class="row"><span>Tsaman Naqdy</span><span>${escapeHtml(formatRp(props.transaksi.tsaman_naqdy))}</span></div>
        <div class="row"><span>Qimah Ismiyyah</span><span>${escapeHtml(formatRp(props.transaksi.qimah_ismiyyah))}</span></div>
        <div class="row"><span>Qimah Haliyyah</span><span>${escapeHtml(formatRp(props.transaksi.qimah_haliyyah))}</span></div>
      `
        : ''

    isi = `
      <div class="header">
        <div style="font-weight:700; font-size:13px;">KOPERASI SYARIAH BERKAH</div>
      </div>

      <div class="receipt-number">
        No: ${escapeHtml(props.transaksi.no_transaksi)}<br>
        Tgl: ${escapeHtml(formatDate(props.transaksi.tanggal))}<br>
        Jam: ${escapeHtml(formatTime(props.transaksi.tanggal))}
      </div>

      <div style="margin-bottom:12px;">
        <div class="row"><span style="font-weight:bold;">JENIS TRANSAKSI</span></div>
        <div style="text-align:center; font-weight:bold; margin-top:3px;">${escapeHtml(transactionTypeText.value)}</div>
      </div>

      <div style="border-bottom:1px dashed #999; margin:10px 0;"></div>

      <div style="margin-bottom:12px;">
        <div style="font-weight:700; font-size:11px; margin-bottom:5px; border-bottom:1px solid #ccc; padding-bottom:3px;">DATA ANGGOTA</div>
        <div class="row"><span>No. Anggota</span><span>${escapeHtml(props.transaksi.no_anggota)}</span></div>
        <div class="row"><span>Nama</span><span>${escapeHtml(props.transaksi.nama_anggota)}</span></div>
        <div class="row"><span>Kontak</span><span>${escapeHtml(props.transaksi.no_telp || '-')}</span></div>
      </div>

      <div style="border-bottom:1px dashed #999; margin:10px 0;"></div>

      <div style="margin-bottom:12px;">
        <div style="font-weight:700; font-size:11px; margin-bottom:5px; border-bottom:1px solid #ccc; padding-bottom:3px;">DATA PEMBIAYAAN</div>
        <div class="row"><span>No. Transaksi</span><span>${escapeHtml(props.transaksi.financing_code || '-')}</span></div>
        <div class="row"><span>Produk</span><span>${escapeHtml(props.transaksi.product_name || '-')}</span></div>
      </div>

      <div style="border-bottom:1px dashed #999; margin:10px 0;"></div>

      <div style="margin-bottom:12px;">
        <div style="font-weight:700; font-size:11px; margin-bottom:5px; border-bottom:1px solid #ccc; padding-bottom:3px;">RINCIAN PEMBAYARAN</div>
        <div class="row"><span>Pokok</span><span>Rp ${escapeHtml(Number(props.transaksi.principal_paid || 0).toLocaleString('id-ID'))}</span></div>
        <div class="row"><span>Margin</span><span>Rp ${escapeHtml(Number(props.transaksi.margin_paid || 0).toLocaleString('id-ID'))}</span></div>
      </div>

      <div style="background:#f5f5f5; padding:8px; margin:10px 0;">
        <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:12px; padding:5px 0;">
          <span>TOTAL PELUNASAN</span>
          <span>Rp ${escapeHtml(Number((props.transaksi.principal_paid || 0) + (props.transaksi.margin_paid || 0)).toLocaleString('id-ID'))}</span>
        </div>
      </div>

      ${murabahahSection}

      <div style="margin-bottom:12px;">
        <div class="row"><span>Metode Pembayaran</span><span>${escapeHtml(props.transaksi.metode || '-')}</span></div>
      </div>

      <div style="background:#fffacd; border:1px solid #ddd; padding:6px; margin:10px 0; font-size:10px; line-height:1.3;">
        <strong>Catatan:</strong> Pelunasan sebelum jatuh tempo telah diterima dengan perhitungan berdasarkan akad murabahah. Semua cicilan yang belum dibayar dinyatakan hangus.
      </div>

      <div style="text-align:center; margin-top:15px; padding-top:10px; border-top:1px dashed #333; font-size:10px; color:#666;">
        <div style="margin:5px 0;">Terima kasih atas kepercayaan Anda</div>
        <div style="margin:5px 0;">Semoga berkah dan bermanfaat</div>
      </div>

      <div style="text-align:center; margin-top:15px; font-size:10px;">
        <div style="margin-top:20px;">Petugas: ${escapeHtml(props.transaksi.pengurus)}</div>
        <div style="margin-top:25px; border-top:1px solid #333; width:150px; margin-left:auto; margin-right:auto; padding-top:3px;"></div>
        <div style="margin-top:3px; font-size:9px;">${escapeHtml(formatDate(new Date()))}</div>
      </div>

      <div style="text-align:center; margin-top:15px; font-size:8px; color:#999; border-top:1px solid #ddd; padding-top:10px;">
        *** Terima kasih telah mempercayai kami ***<br>
        Simpan struk ini sebagai bukti sah transaksi
      </div>
    `

const win = window.open('', '_blank', 'width=420,height=700')
win.document.write(`<!DOCTYPE html><html>
<head>
  <meta charset="utf-8">
  <title>${isRepayment.value ? 'Struk Pelunasan' : 'Kuitansi'} ${escapeHtml(props.transaksi.no_transaksi)}</title>
  <style>
    @page { size: 80mm auto; margin: 4mm; }
    body  { margin:0; font-family:'Courier New',Courier,monospace;
            font-size:11px; color:#111; width:72mm; }
    .header { text-align:center; border-bottom:2px dashed #333; padding-bottom:10px; margin-bottom:15px; }
    .receipt-number { text-align:center; font-weight:bold; margin-bottom:15px; border-bottom:1px dashed #999; padding-bottom:10px; font-size:11px; }
    .tc   { text-align:center; }
    hr    { border:none; border-top:1px dashed #bbb; margin:8px 0; }
    .row  { display:flex; justify-content:space-between; font-size:11px; margin:2.5px 0; padding:3px 0; }
    .rowb { display:flex; justify-content:space-between; font-size:12px;
            font-weight:700; margin:3px 0; padding:5px 0; }
    .badge{ display:inline-block; background:#111; color:#fff; font-size:10px;
            padding:1px 7px; border-radius:2px; letter-spacing:.04em; }
    .saldo-box   { border:1px solid #d1d5db; border-radius:3px;
                   padding:9px 10px; margin:6px 0; }
    .saldo-before{ display:flex; justify-content:space-between;
                   font-size:11px; color:#555; margin-bottom:3px; }
    .saldo-setor { display:flex; justify-content:space-between;
                   font-size:11px; margin-bottom:3px; }
    .plus  { color:#15803d; }
    .minus { color:#dc2626; }
    .saldo-after { display:flex; justify-content:space-between; font-size:12px;
                   font-weight:700; padding-top:5px; border-top:1px dashed #bbb; }
  </style>
</head>
<body>${isi}</body></html>`)
win.document.close()
win.focus()
setTimeout(() => win.print(), 400)
}

defineExpose({ cetak })
</script>

<template>
    <div class="w-full">
        <div id="struk-cetak" class="w-full bg-white text-gray-900 border border-gray-200 rounded p-4"
            style="font-family:'Courier New',Courier,monospace; font-size:11px; max-width:400px; margin:0 auto;">
            <!-- Header -->
            <div class="text-center mb-2.5 pb-2.5 border-b-2 border-dashed border-gray-900">
                <p class="font-bold text-sm">{{ namaKoperasi }}</p>
            </div>

            <!-- Receipt Number & Date -->
            <div class="text-center font-bold mb-4 pb-2.5 border-b border-dashed border-gray-400 text-xs">
                <div>No: {{ transaksi.no_transaksi }}</div>
                <div>Tgl: {{ formatDate(transaksi.tanggal) }}</div>
                <div>Jam: {{ formatTime(transaksi.tanggal) }}</div>
            </div>

            <!-- Jenis Transaksi -->
            <div class="mb-3">
                <div class="font-bold text-xs">JENIS TRANSAKSI</div>
                <div class="text-center font-bold text-xs mt-1">Pelunasan Sebelum Jatuh Tempo</div>
            </div>

            <hr class="border-dashed border-gray-400 my-2.5" />

            <!-- Data Anggota -->
            <div class="mb-3">
                <div class="font-bold text-xs mb-1.5 pb-1 border-b border-gray-300">DATA ANGGOTA</div>
                <div class="flex justify-between text-xs mb-0.5">
                    <span>No. Anggota</span>
                    <span>{{ transaksi.no_anggota }}</span>
                </div>
                <div class="flex justify-between text-xs mb-0.5">
                    <span>Nama</span>
                    <span>{{ transaksi.nama_anggota }}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span>Kontak</span>
                    <span>{{ transaksi.no_telp || '-' }}</span>
                </div>
            </div>

            <hr class="border-dashed border-gray-400 my-2.5" />

            <!-- Data Pembiayaan -->
            <div class="mb-3">
                <div class="font-bold text-xs mb-1.5 pb-1 border-b border-gray-300">DATA PEMBIAYAAN</div>
                <div class="flex justify-between text-xs mb-0.5">
                    <span>No. Transaksi</span>
                    <span>{{ transaksi.financing_code || '-' }}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span>Produk</span>
                    <span>{{ transaksi.product_name || '-' }}</span>
                </div>
            </div>

            <hr class="border-dashed border-gray-400 my-2.5" />

            <!-- Rincian Pembayaran -->
            <div class="mb-3">
                <div class="font-bold text-xs mb-1.5 pb-1 border-b border-gray-300">RINCIAN PEMBAYARAN</div>
                <div class="flex justify-between text-xs mb-0.5">
                    <span>Pokok</span>
                    <span>{{ formatRp(transaksi.principal_paid || 0) }}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span>Margin</span>
                    <span>{{ formatRp(transaksi.margin_paid || 0) }}</span>
                </div>
            </div>

            <!-- Total Section -->
            <div class="bg-gray-100 px-2 py-2 mb-3">
                <div class="flex justify-between font-bold text-xs">
                    <span>TOTAL PELUNASAN</span>
                    <span>{{ formatRp((transaksi.principal_paid || 0) + (transaksi.margin_paid || 0)) }}</span>
                </div>
            </div>

            <!-- Murabahah Calculations (Optional) -->
            <div v-if="transaksi.tsaman_naqdy && transaksi.qimah_ismiyyah && transaksi.qimah_haliyyah" class="mb-3">
                <div class="font-bold text-xs mb-1.5 pb-1 border-b border-gray-300">PERHITUNGAN MURABAHAH</div>
                <div class="flex justify-between text-xs mb-0.5">
                    <span>Tsaman Naqdy</span>
                    <span>{{ formatRp(transaksi.tsaman_naqdy) }}</span>
                </div>
                <div class="flex justify-between text-xs mb-0.5">
                    <span>Qimah Ismiyyah</span>
                    <span>{{ formatRp(transaksi.qimah_ismiyyah) }}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span>Qimah Haliyyah</span>
                    <span>{{ formatRp(transaksi.qimah_haliyyah) }}</span>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="mb-3">
                <div class="flex justify-between text-xs">
                    <span>Metode Pembayaran</span>
                    <span>{{ transaksi.metode || '-' }}</span>
                </div>
            </div>

            <!-- Note Box -->
            <div class="bg-yellow-50 border border-gray-300 px-1.5 py-1.5 mb-3 text-[9px] leading-tight">
                <strong>Catatan:</strong> Pelunasan sebelum jatuh tempo telah diterima dengan perhitungan berdasarkan
                akad murabahah. Semua cicilan yang belum dibayar dinyatakan hangus.
            </div>

            <!-- Footer -->
            <div
                class="text-center text-[10px] text-gray-600 leading-tight mb-3 pt-2.5 border-t-2 border-dashed border-gray-900">
                <div class="mb-1">Terima kasih atas kepercayaan Anda</div>
                <div>Semoga berkah dan bermanfaat</div>
            </div>

            <!-- Signature Area -->
            <div class="text-center text-[10px]">
                <div class="mt-5">Petugas: {{ transaksi.pengurus }}</div>
                <div class="mt-6 mx-auto w-36 border-t border-gray-900"></div>
                <div class="mt-1 text-[9px]">{{ formatDate(new Date()) }}</div>
            </div>

            <!-- Print Footer -->
            <div class="text-center text-[8px] text-gray-400 border-t border-gray-300 pt-2.5 mt-3">
                <div>*** Terima kasih telah mempercayai kami ***</div>
                <div>Simpan struk ini sebagai bukti sah transaksi</div>
            </div>
        </div>

        <button v-if="showPrintButton" @click="cetak"
            class="mt-4 w-full flex items-center justify-center gap-2 px-6 py-2.5 bg-primary hover:bg-secondary text-white text-sm font-medium rounded-lg transition-colors">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
                <rect x="6" y="14" width="12" height="8" rx="1" />
            </svg>
            Cetak Struk
        </button>
    </div>
</template>
