<script setup>
import VerticalBarChart from '@/Components/Dashboard/VerticalBarChart.vue';
import { Link } from '@inertiajs/vue3';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import CardInfo from '@/Components/CardInfo.vue';
import SkeletonChartCard from '@/Components/Dashboard/Loading/SkeletonChartCard.vue';

defineProps({
    stats: Object,
    pertumbuhan_pendapatan: Object,
    selectedFilter: String,
});

</script>

<template>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <CardInfo
            title="Total Modal Belum Dialokasi (Kas)"
            :content="parseCurrencyAmount(stats.total_kas)"
            :percentage="stats.total_kas_persen"
        />
        <CardInfo
            title="Total Modal Sudah Dialokasi"
            :content="parseCurrencyAmount(stats.modal_sudah_dialokasi)"
            :percentage="stats.modal_sudah_dialokasi_persen"
            :filter="selectedFilter"
        />
    </div>
    <div class="grid grid-cols-5 gap-4">
        <SkeletonChartCard v-if="!pertumbuhan_pendapatan" class="col-span-3" :bars="12" :legend="2" />
        <div v-else class="card-layout col-span-3">
            <h1 class="card-title">Grafik Pendapatan Margin</h1>
            <VerticalBarChart
                class="col-span-3"
                title="Grafik Pendapatan Margin"
                :data="pertumbuhan_pendapatan"
                :filter="selectedFilter"
            />
        </div>
        <div class="card-layout col-span-2 bg-light-bg! dark:bg-brand-900/60!">
            <h1 class="card-title text-center">Menu Pintasan</h1>
            <div class="flex flex-col gap-4 mt-6">
                <Link href="/admin/kas"
                    class="bg-white dark:bg-light-bg/20 dark:border-stroke/30 border border-stroke px-4 py-6 flex justify-between items-center rounded-xl hover:bg-gray-50 transition">
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-secondary text-white  rounded-full flex justify-center text-2xl items-center w-11 h-11">
                            <span class="icon-[solar--calculator-bold]"></span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-medium dark:text-gray-200">Pengelolaan Kas</h2>
                            <p class="text-gray-500 text-sm font-body dark:text-gray-300">Pengelolaan kas koperasi</p>
                        </div>
                    </div>
                    <div class="text-secondary dark:text-gray-300 text-3xl">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
                <Link href="/admin/financings"
                    class="bg-white dark:bg-light-bg/20 dark:border-stroke/30 border border-stroke px-4 py-6 flex justify-between items-center rounded-xl hover:bg-gray-50 transition">
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-secondary text-white  rounded-full flex justify-center text-2xl items-center w-11 h-11">
                            <span class="icon-[tdesign--money-filled]"></span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-medium dark:text-gray-200">Pembiayaan Murabahah</h2>
                            <p class="text-gray-500 text-sm font-body dark:text-gray-300">Pengelolaan pembiayaan murabahah di sini</p>
                        </div>
                    </div>
                    <div class="text-secondary dark:text-gray-300 text-3xl">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
                <Link href="/admin/financings"
                    class="bg-white dark:bg-light-bg/20 dark:border-stroke/30 border border-stroke px-4 py-6 flex justify-between items-center rounded-xl hover:bg-gray-50 transition">
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-secondary text-white  rounded-full flex justify-center text-2xl items-center w-11 h-11">
                            <span class="icon-[mdi--hand-coin]"></span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-medium dark:text-gray-200">Simpanan</h2>
                            <p class="text-gray-500 text-sm font-body dark:text-gray-300">Pengelolaan simpanan di sini</p>
                        </div>
                    </div>
                    <div class="text-secondary dark:text-gray-300 text-3xl">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>
