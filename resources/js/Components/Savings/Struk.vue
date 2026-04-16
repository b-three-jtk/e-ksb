<script setup>
import { computed } from 'vue'

const props = defineProps({
  transaksi: {
    type: Object,
    required: true,
  },
  mode: {
    type: String,
    default: 'withdrawal',
  },
  showPrintHint: {
    type: Boolean,
    default: false,
  },
  showPrintButton: {
    type: Boolean,
    default: true,
  },
  showTime: {
    type: Boolean,
    default: true,
  },
  namaKoperasi: {
    type: String,
    default: 'Koperasi Syariah Berkah',
  },
  alamatKoperasi: {
    type: String,
    default: 'Komplek Puri Cipageran Indah 2, RW 21, Desa Ngamprah, Kec. Tanimulya, Kabupaten Bandung Barat',
  },
})

const isDeposit = computed(() => props.mode === 'deposit')

const formatRp = (val) =>
  'Rp ' + Number(val || 0).toLocaleString('id-ID')

const tanggalFormatted = computed(() => {
  const d = new Date(props.transaksi.tanggal)
  const datePart = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })

  if (!props.showTime) {
    return datePart
  }

  return datePart + '  ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
})

const waktuCetak = computed(() => {
  const d = new Date()
  return (
    d.toLocaleDateString('id-ID') +
    ' ' +
    d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) +
    ' WIB'
  )
})

const saldoTitle = computed(() =>
  'SALDO ' + (props.transaksi.jenis || '').toUpperCase()
)

const receiptTitle = computed(() =>
  isDeposit.value ? 'KWITANSI PENYETORAN SIMPANAN' : 'KWITANSI PENARIKAN SIMPANAN'
)

const nominalLabel = computed(() =>
  isDeposit.value ? 'NOMINAL SETOR' : 'NOMINAL TARIK'
)

const saldoDeltaLabel = computed(() =>
  isDeposit.value ? '+ Setoran ini' : '- Tarikan ini'
)

const saldoDeltaValueClass = computed(() =>
  isDeposit.value ? 'text-green-700' : 'text-red-600'
)

const saldoDeltaSign = computed(() =>
  isDeposit.value ? '+' : '-'
)

function escapeHtml(val) {
  return String(val ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

function cetak() {
  const tenorRow = isDeposit.value && props.transaksi.tenor
    ? `<div class="row"><span>Tenor</span><span>${escapeHtml(props.transaksi.tenor)} bulan</span></div>`
    : ''

  const targetRow = isDeposit.value && props.transaksi.target
    ? `<div class="row"><span>Target</span><span>${escapeHtml(formatRp(props.transaksi.target))}</span></div>`
    : ''

  const withdrawalBankRows = !isDeposit.value && props.transaksi.metode === 'Non-Tunai'
    ? `
      <div class="row"><span>Bank</span><span>${escapeHtml(props.transaksi.bank_name)}</span></div>
      <div class="row"><span>Atas Nama</span><span>${escapeHtml(props.transaksi.account_name)}</span></div>
      <div class="row"><span>No. Rekening</span><span>${escapeHtml(props.transaksi.account_number)}</span></div>
    `
    : ''

  const nominalClass = isDeposit.value ? 'plus' : 'minus'

  const isi = `
    <div class="tc" style="margin-bottom:6px;">
      <div style="font-weight:700; font-size:12px;">${escapeHtml(props.namaKoperasi)}</div>
      <div style="font-size:10px; color:#555;">${escapeHtml(props.alamatKoperasi)}</div>
    </div>

    <hr>

    <div class="tc" style="margin:6px 0;">
      <span class="badge">${escapeHtml(receiptTitle.value)}</span>
    </div>

    <hr>

    <div class="row"><span>No. Transaksi</span><span style="font-weight:700;">${escapeHtml(props.transaksi.no_transaksi)}</span></div>
    <div class="row"><span>Tanggal</span><span>${escapeHtml(tanggalFormatted.value)}</span></div>
    <div class="row"><span>Pengurus</span><span>${escapeHtml(props.transaksi.pengurus)}</span></div>

    <hr>

    <div class="row"><span>Nama Anggota</span><span>${escapeHtml(props.transaksi.nama_anggota)}</span></div>
    <div class="row"><span>No. Anggota</span><span>${escapeHtml(props.transaksi.no_anggota)}</span></div>

    <hr>

    <div class="rowb"><span>Jenis Simpanan</span><span>${escapeHtml(props.transaksi.jenis)}</span></div>
    ${tenorRow}
    ${targetRow}
    <div class="row"><span>Metode</span><span>${escapeHtml(props.transaksi.metode)}</span></div>
    ${withdrawalBankRows}

    <hr>

    <div class="rowb"><span>${escapeHtml(nominalLabel.value)}</span><span>${escapeHtml(formatRp(props.transaksi.nominal))}</span></div>

    <hr>

    <div style="font-weight:700; font-size:11px; margin-bottom:4px;">${escapeHtml(saldoTitle.value)}</div>
    <div class="saldo-box">
      <div class="saldo-before"><span>Saldo sebelum</span><span>${escapeHtml(formatRp(props.transaksi.saldo_sebelum))}</span></div>
      <div class="saldo-setor"><span>${escapeHtml(saldoDeltaLabel.value)}</span><span class="${nominalClass}">${escapeHtml(saldoDeltaSign.value)} ${escapeHtml(formatRp(props.transaksi.nominal))}</span></div>
      <div class="saldo-after"><span>Saldo sekarang</span><span>${escapeHtml(formatRp(props.transaksi.saldo_sesudah))}</span></div>
    </div>

    <hr>

    <div class="tc" style="font-size:10px; color:#555; line-height:1.45;">
      <div>Terima kasih atas kepercayaan Anda</div>
      <div>Simpan kwitansi ini sebagai bukti transaksi</div>
      <div style="font-size:9px; color:#8f8f8f; margin-top:3px;">Dicetak: ${escapeHtml(waktuCetak.value)}</div>
    </div>
  `

  const win = window.open('', '_blank', 'width=420,height=700')
  win.document.write(`<!DOCTYPE html><html>
<head>
  <meta charset="utf-8">
  <title>Kwitansi ${props.transaksi.no_transaksi}</title>
  <style>
    @page { size: 80mm auto; margin: 4mm; }
    body  { margin:0; font-family:'Courier New',Courier,monospace;
            font-size:11px; color:#111; width:72mm; }
    .tc   { text-align:center; }
    hr    { border:none; border-top:1px dashed #bbb; margin:8px 0; }
    .row  { display:flex; justify-content:space-between; font-size:11px; margin:2.5px 0; }
    .rowb { display:flex; justify-content:space-between; font-size:12px;
            font-weight:700; margin:3px 0; }
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
    <div
      id="struk-cetak"
      class="w-full bg-white text-gray-900 border border-gray-200 rounded p-4"
      style="font-family:'Courier New',Courier,monospace; font-size:11px;"
    >
      <div class="text-center mb-2.5">
        <p class="font-bold text-sm">{{ namaKoperasi }}</p>
        <p class="text-[10px] text-gray-500">{{ alamatKoperasi }}</p>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <div class="text-center mb-1.5">
        <span class="inline-block bg-gray-900 text-white text-[10px] px-2 py-0.5 rounded-sm tracking-wide">
          {{ receiptTitle }}
        </span>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <div class="flex justify-between text-xs mb-0.5">
        <span>No. Transaksi</span>
        <span class="font-bold">{{ transaksi.no_transaksi }}</span>
      </div>
      <div class="flex justify-between text-xs mb-0.5">
        <span>Tanggal</span>
        <span>{{ tanggalFormatted }}</span>
      </div>
      <div class="flex justify-between text-xs">
        <span>Kasir / Pengurus</span>
        <span>{{ transaksi.pengurus }}</span>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <div class="flex justify-between text-xs mb-0.5">
        <span>Nama Anggota</span>
        <span>{{ transaksi.nama_anggota }}</span>
      </div>
      <div class="flex justify-between text-xs">
        <span>No. Anggota</span>
        <span>{{ transaksi.no_anggota }}</span>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <div class="flex justify-between text-xs font-bold mb-0.5">
        <span>Jenis Simpanan</span>
        <span>{{ transaksi.jenis }}</span>
      </div>

      <div v-if="isDeposit && transaksi.tenor" class="flex justify-between text-xs">
        <span>Tenor</span>
        <span>{{ transaksi.tenor }} bulan</span>
      </div>

      <div v-if="isDeposit && transaksi.target" class="flex justify-between text-xs">
        <span>Target</span>
        <span>{{ formatRp(transaksi.target) }}</span>
      </div>

      <div class="flex justify-between text-xs">
        <span>Metode</span>
        <span>{{ transaksi.metode }}</span>
      </div>

      <div v-if="!isDeposit && transaksi.metode === 'Non-Tunai'">
        <div class="flex justify-between text-xs">
          <span>Bank</span>
          <span>{{ transaksi.bank_name }}</span>
        </div>
        <div class="flex justify-between text-xs">
          <span>Atas Nama</span>
          <span>{{ transaksi.account_name }}</span>
        </div>
        <div class="flex justify-between text-xs">
          <span>No. Rekening</span>
          <span>{{ transaksi.account_number }}</span>
        </div>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <div class="flex justify-between text-sm font-bold">
        <span>{{ nominalLabel }}</span>
        <span>{{ formatRp(transaksi.nominal) }}</span>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <p class="text-xs font-bold mb-1.5">{{ saldoTitle }}</p>
      <div class="border border-gray-200 rounded-sm px-2.5 py-2 mb-2">
        <div class="flex justify-between text-xs text-gray-500 mb-1">
          <span>Saldo sebelum</span>
          <span>{{ formatRp(transaksi.saldo_sebelum) }}</span>
        </div>
        <div class="flex justify-between text-xs mb-1">
          <span>{{ saldoDeltaLabel }}</span>
          <span :class="saldoDeltaValueClass">{{ saldoDeltaSign }} {{ formatRp(transaksi.nominal) }}</span>
        </div>
        <div class="flex justify-between text-xs font-bold pt-1.5 border-t border-dashed border-gray-300">
          <span>Saldo sekarang</span>
          <span>{{ formatRp(transaksi.saldo_sesudah) }}</span>
        </div>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <div class="text-center text-[10px] text-gray-500 leading-relaxed">
        <p>Terima kasih atas kepercayaan Anda</p>
        <p>Simpan kwitansi ini sebagai bukti transaksi</p>
        <p class="text-[9px] text-gray-400 mt-1">Dicetak: {{ waktuCetak }}</p>
      </div>
    </div>

    <button
      v-if="showPrintButton"
      @click="cetak"
      class="mt-4 w-full flex items-center justify-center gap-2 px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
    >
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
        <rect x="6" y="14" width="12" height="8" rx="1"/>
      </svg>
      Cetak Kwitansi
    </button>
  </div>
</template>
