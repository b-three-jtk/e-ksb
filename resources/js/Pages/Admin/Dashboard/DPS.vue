<script setup>
import CardInfo from '@/Components/CardInfo.vue';
import FdrCard from '@/Components/Dashboard/FdrCard.vue';
import VerticalBarChart from '@/Components/Dashboard/VerticalBarChart.vue';
import BaseTable from '@/Components/Table/BaseTable.vue';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import BarChart from '@/Components/Dashboard/Barchart.vue';
import { computed, ref } from 'vue';
import EyeIcon from '@/Icons/EyeIcon.vue';
import { Link } from '@inertiajs/vue3';
import SkeletonStatCard from '@/Components/Dashboard/Loading/SkeletonStatCard.vue';
import SkeletonChartCard from '@/Components/Dashboard/Loading/SkeletonChartCard.vue';
import SkeletonMapCard from '@/Components/Dashboard/Loading/SkeletonMapCard.vue';
import SkeletonTableCard from '@/Components/Dashboard/Loading/SkeletonTableCard.vue';

const props = defineProps({
    stats: Object,
    pertumbuhan_pendapatan: Object,
    peta_pembiayaan: Object,
    peta_simpanan: Object,
    transaksi_terbaru: Object,
    can: Object,
    selectedFilter: String,
    selectedTransactionFilter: String,
    selectedSavingsFilter: String,
});

const kolomTabel = computed(() => {
    const cols = [
        { key: 'anggota', label: 'Anggota', sortable: true },
        { key: 'produk', label: 'Produk' },
        { key: 'akad', label: 'Akad' },
        { key: 'jumlah', label: 'Jumlah', sortable: true },
        { key: 'dicatat_oleh', label: 'Dicatat Oleh' },
        { key: 'tanggal', label: 'Tanggal', sortable: true },
    ];
    cols.push({ key: 'action', label: 'Aksi', align: 'center' });
    return cols;
});

const sortBy = ref('tanggal');
const sortDir = ref('desc');

const handleSort = (field) => {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
};

const sortedTransaksi = computed(() => {
    if (!props.transaksi_terbaru) return [];
    return [...props.transaksi_terbaru].sort((a, b) => {
        let valA = a[sortBy.value];
        let valB = b[sortBy.value];

        if (sortBy.value === 'jumlah') {
            valA = Number(valA) || 0;
            valB = Number(valB) || 0;
        } else if (sortBy.value === 'tanggal') {
            valA = new Date(valA).getTime();
            valB = new Date(valB).getTime();
        } else {
            valA = String(valA || '').toLowerCase();
            valB = String(valB || '').toLowerCase();
        }

        if (valA < valB) return sortDir.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortDir.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const getProductColor = (produk) => {
    if (!produk) return { bg: 'bg-gray-100 dark:bg-slate-700', text: 'text-gray-700 dark:text-slate-300' }
    const key = produk.toLowerCase()
    if (key.includes('pokok')) return { bg: 'bg-blue-100 dark:bg-blue-900/40', text: 'text-blue-700 dark:text-blue-200' }
    if (key.includes('wajib')) return { bg: 'bg-green-100 dark:bg-green-900/40', text: 'text-green-700 dark:text-green-200' }
    if (key.includes('anggota')) return { bg: 'bg-amber-100 dark:bg-amber-900/40', text: 'text-amber-700 dark:text-amber-200' }
    if (key.includes('berjangka')) return { bg: 'bg-orange-100 dark:bg-orange-900/40', text: 'text-orange-700 dark:text-orange-200' }
    if (key.includes('ibadah')) return { bg: 'bg-teal-100 dark:bg-teal-900/40', text: 'text-teal-700 dark:text-teal-200' }
    if (key.includes('pembiayaan')) return { bg: 'bg-indigo-100 dark:bg-indigo-900/40', text: 'text-indigo-700 dark:text-indigo-200' }
    return { bg: 'bg-gray-100 dark:bg-slate-700', text: 'text-gray-700 dark:text-slate-100' }
}

const emit = defineEmits(['update:selectedTransactionFilter', 'update:selectedSavingsFilter', 'update:selectedFilter']);

const descriptions = {
    'Total Kas': 'Nilai ini menunjukkan total kas yang dimiliki oleh koperasi untuk periode yang dipilih.',
    'Total Piutang Murabahah': 'Nilai ini menunjukkan total piutang murabahah yang belum dilunasi oleh anggota untuk periode yang dipilih.',
};
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 items-start">
        <div class="col-span-3 flex flex-col gap-4">
            <SkeletonStatCard v-if="!stats" :count="1" />
            <FdrCard v-else :fdr="props.stats.rasio_fdr" />
            <SkeletonMapCard v-if="!peta_simpanan" class="col-span-3" :legend-items="4" />
            <div v-else class="card-layout col-span-3">
                <div class="flex justify-between w-full items-center">
                    <h1 class="card-title">Peta Simpanan</h1>
                    <div class="relative z-20 bg-transparent">
                        <select :value="selectedSavingsFilter"
                            @input="$emit('update:selectedSavingsFilter', $event.target.value)"
                            class="h-11 w-full font-body appearance-none px-4 bg-white pr-11 text-sm focus:outline-hidden dark:bg-dark-900 text-gray-800 dark:bg-gray-900 dark:text-white/90">
                            <option value="jenis">Berdasarkan Jenisnya</option>
                            <option value="akad">Berdasarkan Akadnya</option>
                        </select>
                        <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400"
                            viewBox="0 0 20 20" fill="none">
                            <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>
                <BarChart :height="300" :data="peta_simpanan" />
            </div>
        </div>

        <div class="col-span-3 grid grid-cols-2 gap-4">
            <CardInfo title="Total Kas" :content="parseCurrencyAmount(props.stats.total_kas)"
                :percentage="props.stats.total_kas_persen" :filter="props.selectedFilter"
                :deskripsi="descriptions['Total Kas']" />
            <CardInfo title="Total Piutang Murabahah"
                :content="parseCurrencyAmount(props.stats.total_pembiayaan_tersalurkan)"
                :percentage="props.stats.total_pembiayaan_tersalurkan_persen" :filter="props.selectedFilter"
                :deskripsi="descriptions['Total Piutang Murabahah']" />

            <SkeletonChartCard v-if="!pertumbuhan_pendapatan" class="col-span-2" :bars="12" :legend="2" />
            <div v-else class="card-layout col-span-2">
                <div class="flex justify-between">
                    <h1 class="card-title">Grafik Pendapatan Margin</h1>
                </div>
                <VerticalBarChart height="390" class="pt-10" title="Grafik Pendapatan Margin"
                    :data="pertumbuhan_pendapatan" :filter="selectedFilter" />
            </div>
        </div>
    </div>
</template>
