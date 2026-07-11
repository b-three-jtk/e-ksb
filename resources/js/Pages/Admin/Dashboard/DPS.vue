<script setup>
import CardInfo from '@/Components/CardInfo.vue';
import FdrCard from '@/Components/Dashboard/FdrCard.vue';
import BarChart from '@/Components/Dashboard/Barchart.vue';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import SkeletonStatCard from '@/Components/Dashboard/Loading/SkeletonStatCard.vue';
import SkeletonMapCard from '@/Components/Dashboard/Loading/SkeletonMapCard.vue';

const props = defineProps({
    stats: Object,
    pertumbuhan_pendapatan: Object,
    peta_simpanan: Object,
    transaksi_terbaru: Object,
    can: Object,
    selectedFilter: String,
    selectedTransactionFilter: String,
    selectedSavingsFilter: String,
});

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
