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
    selectedNearestDueFilter: String,
    anggota_bermasalah_pj: Array,
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

const createProblemWaUrl = (phoneNumber, masalahArray) => {
    const waNumber = normalizeWhatsAppNumber(phoneNumber)
    let text = `Assalamualaikum, kami dari KSB ingin menginformasikan beberapa hal terkait keanggotaan Anda:\n\n`;
    masalahArray.forEach(m => text += `- ${m}\n`);
    text += `\nMohon untuk segera menindaklanjuti. Terima kasih.`;
    return waNumber ? `https://wa.me/${waNumber}?text=${encodeURIComponent(text)}` : '#'
}

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
                <div>
                    <h1 class="card-title">Daftar Anggota Bermasalah</h1>
                    <p class="text-sm text-gray-400">Anggota dengan tunggakan simpanan atau angsuran</p>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!anggota_bermasalah_pj || anggota_bermasalah_pj.length === 0" class="flex flex-col items-center justify-center py-6 text-center">
                <Icon icon="mdi:check-circle-outline" class="w-16 h-16 text-green-200 dark:text-green-700 mb-3" />
                <p class="text-sm text-gray-400">Semua anggota dalam status baik dan lancar</p>
            </div>

            <div v-else class="flex flex-col gap-4 max-h-136 overflow-y-auto pr-2 custom-scrollbar">
                <div v-for="anggota in anggota_bermasalah_pj" :key="anggota.id" class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 bg-white dark:bg-gray-800 shadow-sm">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-gray-200">{{ anggota.nama }}</h3>
                            <p class="text-xs text-gray-500">{{ anggota.no_telp || 'Tidak ada nomor telepon' }}</p>
                        </div>
                        <a v-if="anggota.no_telp" :href="createProblemWaUrl(anggota.no_telp, anggota.daftar_masalah)" target="_blank" class="inline-flex items-center justify-center rounded-lg bg-green-500 py-2 px-4 gap-2 text-white hover:bg-green-600">
                            Kirim Peringatan <Icon icon="mdi:whatsapp" class="w-4 h-4" />
                        </a>
                    </div>
                    <div class="space-y-1.5">
                        <div v-for="(masalah, idx) in anggota.daftar_masalah" :key="idx" class="flex items-start gap-2 bg-red-50 dark:bg-red-900/20 p-2 rounded-lg border border-red-100 dark:border-red-900/50">
                            <Icon icon="mdi:alert" class="w-4 h-4 text-red-500 shrink-0 mt-0.5" />
                            <span class="text-sm font-medium text-red-700 dark:text-red-400">{{ masalah }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
