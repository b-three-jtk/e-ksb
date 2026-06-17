<script setup>
import CardInfo from '@/Components/CardInfo.vue';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import EyeIcon from '@/Icons/EyeIcon.vue';
import TransactionTable from '@/Components/Dashboard/TransactionTable.vue';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import SkeletonStatCard from '@/Components/Dashboard/Loading/SkeletonStatCard.vue';
import SkeletonTableCard from '@/Components/Dashboard/Loading/SkeletonTableCard.vue';

defineProps({
    stats: Object,
    jatuh_tempo_terdekat: Object,
    transaksi_simpanan_terbaru: Object,
    selectedSavingTransactionFilter: String,
    selectedNearestDueFilter: String,
});

const kolomTabelJatuhTempoTerdekat = computed(() => {
    const cols = [
        { key: 'produk', label: 'Jenis' },
        { key: 'jatuh_tempo', label: 'Jatuh Tempo' },
        { key: 'anggota', label: 'Anggota' },
        { key: 'nominal', label: 'Nominal' },
        { key: 'status_notifikasi', label: 'Status Notifikasi' },
    ];
    return cols;
});

const kolomTabelTransaksiSimpanan = computed(() => {
    const cols = [
        { key: 'no_transaksi', label: 'No. Transaksi' },
        { key: 'anggota', label: 'Anggota' },
        { key: 'jumlah', label: 'Nominal' },
        { key: 'produk', label: 'Jenis' },
    ];
    cols.push({ key: 'action', label: 'Aksi' });
    return cols;
});

const getStatusClass = (status) => {
    const base = 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold'
    switch (status) {
        case 'sent': return `${base} bg-green-100 text-green-700`
        case 'draft': return `${base} bg-yellow-100 text-yellow-700`
        case 'failed': return `${base} bg-red-100 text-red-700`
        default: return `${base} bg-gray-100 text-gray-700`
    }
}
</script>

<template>
    <!-- INFO -->
    <SkeletonStatCard v-if="!stats" :count="3" />
    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <CardInfo
            title="Total Simpanan Masuk"
            :content="parseCurrencyAmount(stats.total_simpanan_anggota_masuk)"
            :percentage="stats.total_simpanan_anggota_masuk_persen"
        />
        <CardInfo
            title="Total Simpanan Keluar"
            :content="parseCurrencyAmount(stats.total_simpanan_anggota_keluar)"
            :percentage="stats.total_simpanan_anggota_keluar_persen"
        />
        <CardInfo
            title="Total Angsuran Belum Lunas"
            :content="parseCurrencyAmount(stats.total_angsuran_belum_lunas)"
        />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <SkeletonTableCard v-if="!jatuh_tempo_terdekat" class="col-span-1" :columns="kolomTabelJatuhTempoTerdekat.length" :rows="5" />
        <div v-else class="card-layout">
            <div class="flex justify-between items-center">
                <h1 class="card-title">Jatuh Tempo Terdekat</h1>
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
            </TransactionTable>
        </div>
        <SkeletonTableCard v-if="!transaksi_simpanan_terbaru" class="col-span-1" :columns="kolomTabelTransaksiSimpanan.length" :rows="5" />
        <div v-else class="card-layout">
            <div class="flex justify-between items-center">
                <h1 class="card-title">Transaksi Simpanan Terbaru</h1>
                <div class="relative z-20 bg-transparent">
                    <select :value="selectedSavingTransactionFilter"
                        @input="$emit('update:selectedSavingTransactionFilter', $event.target.value)"
                        class="h-11 w-full font-body appearance-none rounded-lg border px-4 bg-white pr-11 text-sm shadow-theme-xs focus:outline-hidden dark:bg-dark-900 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="all">Semua</option>
                        <option value="Simpanan Pokok">Simpanan Pokok</option>
                        <option value="Simpanan Wajib">Simpanan Wajib</option>
                        <option value="Tabungan Anggota">Tabungan Anggota</option>
                        <option value="Tabungan Berjangka">Tabungan Berjangka</option>
                        <option value="Tabungan Ibadah">Tabungan Ibadah</option>
                    </select>
                    <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400"
                        viewBox="0 0 20 20" fill="none">
                        <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
            </div>
            <TransactionTable :columns="kolomTabelTransaksiSimpanan" :rows="transaksi_simpanan_terbaru">
                <template #jumlah="{ item }">
                        {{ parseCurrencyAmount(item.jumlah) }}
                </template>
                <template #action="{ item }">
                    <Link :href="`/admin/savings/show/${item.id}`">
                        <EyeIcon
                            class="w-5 h-5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" />
                    </Link>
                </template>
            </TransactionTable>
        </div>
    </div>
</template>
