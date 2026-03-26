<script setup>
import { computed } from 'vue'

const props = defineProps({
  transaksi: {
    type: Object,
    required: true,
  },
  namaKoperasi  : { type: String, default: 'Koperasi Syariah Berkah' },
  alamatKoperasi: { type: String, default: 'Komplek Puri Cipageran Indah 2, RW 21, Desa Ngamprah, Kec. Tanimulya, Kabupaten Bandung Barat' },
  telpKoperasi  : { type: String, default: '(022) 1234-5678' },
})

const formatRp = (val) =>
  'Rp ' + Number(val || 0).toLocaleString('id-ID')

const tanggalFormatted = computed(() => {
  const d = new Date(props.transaksi.tanggal)
  return (
    d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) +
    '  ' +
    d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
  )
})

const waktuCetak = computed(() => {
  const d = new Date()
  return (
    d.toLocaleDateString('id-ID') + ' ' +
    d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) +
    ' WIB'
  )
})

const saldoTitle = computed(() =>
  'SALDO ' + (props.transaksi.jenis || '').toUpperCase()
)

function cetak() {
  const el  = document.getElementById('struk-cetak')
  const isi = el ? el.innerHTML : ''
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
    <!-- Konten struk (id dipakai oleh fungsi cetak()) -->
    <div
      id="struk-cetak"
      class="w-full bg-white text-gray-900 border border-gray-200 rounded p-4"
      style="font-family:'Courier New',Courier,monospace; font-size:11px;"
    >
      <!-- Header koperasi -->
      <div class="text-center mb-2.5">
        <p class="font-bold text-sm">{{ namaKoperasi }}</p>
        <p class="text-[10px] text-gray-500">{{ alamatKoperasi }}</p>
        <p class="text-[10px] text-gray-500">Telp. {{ telpKoperasi }}</p>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <div class="text-center mb-1.5">
        <span class="inline-block bg-gray-900 text-white text-[10px] px-2 py-0.5 rounded-sm tracking-wide">
          KWITANSI PENYETORAN SIMPANAN
        </span>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <!-- Info transaksi -->
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

      <!-- Info anggota -->
      <div class="flex justify-between text-xs mb-0.5">
        <span>Nama Anggota</span>
        <span>{{ transaksi.nama_anggota }}</span>
      </div>
      <div class="flex justify-between text-xs">
        <span>No. Anggota</span>
        <span>{{ transaksi.no_anggota }}</span>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <!-- Jenis & metode -->
      <div class="flex justify-between text-xs font-bold mb-0.5">
        <span>Jenis Simpanan</span>
        <span>{{ transaksi.jenis }}</span>
      </div>

      <div v-if="transaksi.tenor" class="flex justify-between text-xs">
        <span>Tenor</span>
        <span>{{ transaksi.tenor }} bulan</span>
      </div>

      <div v-if="transaksi.target" class="flex justify-between text-xs">
        <span>Target</span>
        <span>{{ formatRp(transaksi.target) }}</span>
      </div>
      
      <div class="flex justify-between text-xs">
        <span>Metode</span>
        <span>{{ transaksi.metode }}</span>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <!-- Nominal -->
      <div class="flex justify-between text-sm font-bold">
        <span>NOMINAL SETOR</span>
        <span>{{ formatRp(transaksi.nominal) }}</span>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <!-- Saldo -->
      <p class="text-xs font-bold mb-1.5">{{ saldoTitle }}</p>
      <div class="border border-gray-200 rounded-sm px-2.5 py-2 mb-2">
        <div class="flex justify-between text-xs text-gray-500 mb-1">
          <span>Saldo sebelum</span>
          <span>{{ formatRp(transaksi.saldo_sebelum) }}</span>
        </div>
        <div class="flex justify-between text-xs mb-1">
          <span>+ Setoran ini</span>
          <span class="text-green-700">+ {{ formatRp(transaksi.nominal) }}</span>
        </div>
        <div class="flex justify-between text-xs font-bold pt-1.5 border-t border-dashed border-gray-300">
          <span>Saldo sekarang</span>
          <span>{{ formatRp(transaksi.saldo_sesudah) }}</span>
        </div>
      </div>

      <hr class="border-dashed border-gray-300 my-2" />

      <!-- Footer -->
      <div class="text-center text-[10px] text-gray-500 leading-relaxed">
        <p>Terima kasih atas kepercayaan Anda</p>
        <p>Simpan kwitansi ini sebagai bukti transaksi</p>
        <p class="text-[9px] text-gray-400 mt-1">Dicetak: {{ waktuCetak }}</p>
      </div>
    </div>

    <!-- Tombol cetak -->
    <button
      @click="cetak"
      class="mt-4 w-full flex items-center justify-center gap-2 px-6 py-2.5
             bg-blue-600 text-white text-sm font-medium rounded-lg
             hover:bg-blue-700 transition-colors"
    >
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="2">
        <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
        <rect x="6" y="14" width="12" height="8" rx="1"/>
      </svg>
      Cetak Kwitansi
    </button>
    <p class="mt-1.5 text-xs text-gray-400 text-center">
      Atur ukuran kertas ke 80mm di printer settings
    </p>
  </div>
</template>