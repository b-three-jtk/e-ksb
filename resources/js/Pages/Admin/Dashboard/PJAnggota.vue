<script setup>
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import TransactionTable from '@/Components/Dashboard/TransactionTable.vue';
import { computed, ref } from 'vue';
import { Icon } from '@iconify/vue';
import SkeletonTableCard from '@/Components/Dashboard/Loading/SkeletonTableCard.vue';

const props = defineProps({
    stats: Object,
    jatuh_tempo_terdekat: Object,
    transaksi_simpanan_terbaru: Object,
    selectedTransaksiSimpananFilter: String,
    selectedNearestDueFilter: String,
    ringkasan_anggota_pj: Array,
});

const selectedAnggotaId = ref('');

const selectedAnggotaData = computed(() => {
    if (!selectedAnggotaId.value || !props.ringkasan_anggota_pj) return null;
    return props.ringkasan_anggota_pj.find(a => a.id == selectedAnggotaId.value);
});

const kolomTabelJatuhTempoTerdekat = computed(() => {
    const cols = [
        { key: 'produk', label: 'Jenis' },
        { key: 'jatuh_tempo', label: 'Jatuh Tempo' },
        { key: 'anggota', label: 'Anggota' },
        { key: 'nominal', label: 'Nominal' },
        { key: 'status_notifikasi', label: 'Status Notifikasi' },
    ];
    cols.push({ key: 'action', label: 'Aksi' });
    return cols;
});

const kolomTabelTransaksiSimpanan = computed(() => {
    const cols = [
        { key: 'anggota', label: 'Anggota' },
        { key: 'jumlah', label: 'Nominal' },
        { key: 'produk', label: 'Jenis' },
    ];
    cols.push({ key: 'action', label: 'Aksi' });
    return cols;
});

const getStatusClass = (status) => {
    const base = 'inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold'
    switch (status) {
        case 'sent': return `${base} bg-green-100 text-green-700`
        case 'draft': return `${base} bg-yellow-100 text-yellow-700`
        case 'failed': return `${base} bg-red-100 text-red-700`
        default: return `${base} bg-gray-100 text-gray-700`
    }
}

const normalizeWhatsAppNumber = (phoneNumber) => {
    if (!phoneNumber) return ''
    const digits = phoneNumber.replace(/\D/g, '')
    if (digits.startsWith('0')) return `62${digits.slice(1)}`
    return digits
}

const createWhatsAppUrl = (phoneNumber, message) => {
    const waNumber = normalizeWhatsAppNumber(phoneNumber)
    const text = `Assalamualaikum, kami dari KSB ingin memberitahukan ${message || ''}`
    return waNumber ? `https://wa.me/${waNumber}?text=${encodeURIComponent(text)}` : '#'
}

const waRingkasanUrl = computed(() => {
    if (!selectedAnggotaData.value || !selectedAnggotaData.value.no_telp) return '#';
    const d = selectedAnggotaData.value;
    let msg = `berikut adalah ringkasan keuangan Anda:\n\n`;
    msg += `*Simpanan*\n`;
    if (d.simpanan_breakdown && d.simpanan_breakdown.length) {
        d.simpanan_breakdown.forEach(s => {
            msg += `  • ${s.jenis}: ${parseCurrencyAmount(s.saldo)}\n`;
        });
        msg += `  *Total: ${parseCurrencyAmount(d.total_simpanan)}*\n`;
    } else {
        msg += `  Belum memiliki simpanan\n`;
    }
    msg += `\n*Sisa Angsuran*: ${parseCurrencyAmount(d.sisa_angsuran)}`;
    return createWhatsAppUrl(d.no_telp, msg);
});

const emit = defineEmits(['update:selectedTransaksiSimpananFilter', 'update:selectedNearestDueFilter']);
</script>

<template>
    <!-- INFO -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <SkeletonTableCard v-if="!jatuh_tempo_terdekat" class="col-span-1" :columns="kolomTabelJatuhTempoTerdekat.length" :rows="5" />
        <div v-else class="card-layout">
            <div class="flex justify-between items-center">
                <h1 class="card-title">Daftar Jatuh Tempo Terdekat</h1>
                <div class="relative z-20 bg-transparent">
                    <select :value="selectedNearestDueFilter"
                        @input="$emit('update:selectedNearestDueFilter', $event.target.value)"
                        class="h-11 w-full font-body appearance-none rounded-lg border px-4 bg-white pr-11 text-sm shadow-theme-xs focus:outline-hidden dark:bg-dark-900 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="all">Semua</option>
                        <option value="simpanan">Simpanan</option>
                        <option value="pembiayaan">Pembiayaan</option>
                    </select>
                    <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400"
                        viewBox="0 0 20 20" fill="none">
                        <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
            </div>
            <TransactionTable :columns="kolomTabelJatuhTempoTerdekat" :rows="jatuh_tempo_terdekat">
                <template #cell-status_notifikasi="{ row }">
                    <span :class="getStatusClass(row.status)">
                        {{ row.status }}
                    </span>
                </template>
                <template #nominal="{ item }">
                    {{ parseCurrencyAmount(item.nominal) }}
                </template>
                <template #action="{ item }">
                    <a
                        v-if="item.no_telp"
                        :href="createWhatsAppUrl(item.no_telp, item.message)"
                        class="inline-flex items-center justify-center rounded-lg bg-green-500 px-3 py-2 text-sm font-medium text-white hover:bg-green-600"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <Icon icon="mdi:whatsapp" class="mr-2 h-4 w-4" />
                        WhatsApp
                    </a>
                    <span
                        v-else
                        class="inline-flex items-center justify-center rounded-lg bg-gray-200 px-3 py-2 text-sm font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                    >
                        -
                    </span>
                </template>
            </TransactionTable>
        </div>
        <div class="card-layout flex flex-col gap-5">
            <!-- Header with icon -->
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                    <Icon icon="mdi:account-search" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <h1 class="card-title">Cek Anggota</h1>
                    <p class="text-sm text-gray-400">Pilih anggota untuk melihat ringkasan keuangan</p>
                </div>
            </div>

            <!-- Dropdown -->
            <div class="relative w-full">
                <Icon icon="mdi:magnify" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 z-10 pointer-events-none" />
                <select v-model="selectedAnggotaId" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl pl-10 pr-10 py-3.5 text-base focus:ring-2 focus:ring-primary/30 focus:border-primary appearance-none transition-all">
                    <option value="" disabled>Pilih Anggota</option>
                    <option v-for="anggota in ringkasan_anggota_pj" :key="anggota.id" :value="anggota.id">
                        {{ anggota.nama }}
                    </option>
                </select>
                <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-400" viewBox="0 0 20 20" fill="none">
                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>

            <!-- Empty state -->
            <div v-if="!selectedAnggotaData" class="flex flex-col items-center justify-center py-6 text-center">
                <Icon icon="mdi:account-circle-outline" class="w-16 h-16 text-gray-200 dark:text-gray-700 mb-3" />
                <p class="text-sm text-gray-400">Pilih anggota di atas untuk melihat informasi</p>
            </div>

            <template v-if="selectedAnggotaData">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold"
                        :class="selectedAnggotaData.status === 'Aktif' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                        <span class="w-1.5 h-1.5 rounded-full" :class="selectedAnggotaData.status === 'Aktif' ? 'bg-green-500' : 'bg-red-500'"></span>
                        {{ selectedAnggotaData.status }}
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ selectedAnggotaData.nama }}</span>
                </div>

                <div class="bg-linear-to-br from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 rounded-xl p-4 border border-blue-100 dark:border-blue-900/50">
                    <div class="flex items-center gap-2 mb-3">
                        <Icon icon="mdi:wallet" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300">Simpanan</h3>
                        <span class="ml-auto text-base font-bold text-blue-700 dark:text-blue-300">{{ parseCurrencyAmount(selectedAnggotaData.total_simpanan) }}</span>
                    </div>
                    <div class="space-y-2">
                        <div v-for="(s, idx) in selectedAnggotaData.simpanan_breakdown" :key="idx"
                            class="flex items-center justify-between bg-white/70 dark:bg-gray-800/50 rounded-lg px-3 py-2.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                    :class="{
                                        'bg-emerald-100 dark:bg-emerald-900/40': s.jenis === 'Simpanan Pokok',
                                        'bg-blue-100 dark:bg-blue-900/40': s.jenis === 'Simpanan Wajib',
                                        'bg-purple-100 dark:bg-purple-900/40': s.jenis === 'Tabungan Anggota',
                                        'bg-amber-100 dark:bg-amber-900/40': s.jenis === 'Tabungan Berjangka',
                                        'bg-pink-100 dark:bg-pink-900/40': s.jenis === 'Tabungan Ibadah',
                                    }">
                                    <Icon
                                        :icon="s.jenis === 'Simpanan Pokok' ? 'mdi:shield-check' :
                                            s.jenis === 'Simpanan Wajib' ? 'mdi:calendar-check' :
                                            s.jenis === 'Tabungan Anggota' ? 'mdi:piggy-bank' :
                                            s.jenis === 'Tabungan Berjangka' ? 'mdi:timer-sand' : 'mdi:mosque'"
                                        class="w-4 h-4"
                                        :class="{
                                            'text-emerald-600 dark:text-emerald-400': s.jenis === 'Simpanan Pokok',
                                            'text-blue-600 dark:text-blue-400': s.jenis === 'Simpanan Wajib',
                                            'text-purple-600 dark:text-purple-400': s.jenis === 'Tabungan Anggota',
                                            'text-amber-600 dark:text-amber-400': s.jenis === 'Tabungan Berjangka',
                                            'text-pink-600 dark:text-pink-400': s.jenis === 'Tabungan Ibadah',
                                        }" />
                                </div>
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ s.jenis }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ parseCurrencyAmount(s.saldo) }}</span>
                        </div>
                        <div v-if="!selectedAnggotaData.simpanan_breakdown?.length" class="text-center py-2 text-sm text-gray-400">
                            Belum memiliki simpanan
                        </div>
                    </div>
                </div>

                <div class="bg-linear-to-br from-orange-50 to-red-50 dark:from-orange-950/30 dark:to-red-950/30 rounded-xl p-4 border border-orange-100 dark:border-orange-900/50">
                    <div class="flex items-center gap-2">
                        <Icon icon="mdi:receipt-text-clock" class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                        <h3 class="text-sm font-semibold text-orange-800 dark:text-orange-300">Sisa Angsuran</h3>
                        <span class="ml-auto text-base font-bold text-orange-700 dark:text-orange-300">{{ parseCurrencyAmount(selectedAnggotaData.sisa_angsuran) }}</span>
                    </div>
                </div>

                <a
                    v-if="selectedAnggotaData.no_telp"
                    :href="waRingkasanUrl"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-green-500 hover:bg-green-600 active:bg-green-700 px-4 py-3.5 text-sm font-semibold text-white transition-all shadow-sm hover:shadow-md"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <Icon icon="mdi:whatsapp" class="w-5 h-5" />
                    Kirim Ringkasan via WhatsApp
                </a>
                <div v-else class="w-full text-center p-3 text-sm text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                    <Icon icon="mdi:phone-off" class="w-5 h-5 mx-auto mb-1 text-gray-300" />
                    Nomor telepon tidak terdaftar
                </div>
            </template>
        </div>
    </div>
</template>
