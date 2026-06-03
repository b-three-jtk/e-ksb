<script setup>
import VerticalBarChart from '@/Components/Dashboard/VerticalBarChart.vue';
import { Link } from '@inertiajs/vue3';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import CardInfo from '@/Components/CardInfo.vue';

defineProps({
    data: Object,
    can: Object,
    selectedFilter: String,
});
</script>

<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <CardInfo v-if="can['view_kas']" title="Total Kas" :content="parseCurrencyAmount(data.total_saving_amount)" />
        <CardInfo title="Total Modal Belum Dialokasi" :content="parseCurrencyAmount(data.total_saving_amount)"
            :percentage="total_staff_percentage" />
        <CardInfo title="Total Modal Sudah Dialokasi" :content="parseCurrencyAmount(data.total_saving_amount)"
            :percentage="total_active_member_percentage" :filter="selectedFilter" />
    </div>
    <div class="grid grid-cols-5 gap-3.5">
        <div class="card-layout">
            <h1 class="card-title">Grafik Pendapatan Margin</h1>
            <VerticalBarChart class="col-span-3" title="Grafik Pendapatan Margin" :data="data.revenues"
            :filter="selectedFilter" />
        </div>
        <div class="card-layout col-span-2 bg-light-bg!">
            <h1 class="card-title text-center">Menu Pintasan</h1>
            <div class="flex flex-col gap-3.5 mt-6">
                <Link href="/admin/jurnal-umum"
                    class="bg-white border border-stroke px-4 py-6 flex justify-between items-center rounded-xl hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3.5">
                        <div
                            class="bg-secondary text-white  rounded-full flex justify-center text-2xl items-center w-11 h-11">
                            <span class="icon-[solar--calculator-bold]"></span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-medium">Jurnal Umum</h2>
                            <p class="text-gray-500 text-sm font-body">Pengelolaan Kas Koperasi</p>
                        </div>
                    </div>
                    <div class="text-secondary text-3xl">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
                <Link href="/admin/financings"
                    class="bg-white border border-stroke px-4 py-6 flex justify-between items-center rounded-xl hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3.5">
                        <div
                            class="bg-secondary text-white  rounded-full flex justify-center text-2xl items-center w-11 h-11">
                            <span class="icon-[tdesign--money-filled]"></span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-medium">Pembiayaan Murabahah</h2>
                            <p class="text-gray-500 text-sm font-body">Pengelolaan pembiayaan murabahah di sini</p>
                        </div>
                    </div>
                    <div class="text-secondary text-3xl">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
                <Link href="/admin/financings"
                    class="bg-white border border-stroke px-4 py-6 flex justify-between items-center rounded-xl hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3.5">
                        <div
                            class="bg-secondary text-white  rounded-full flex justify-center text-2xl items-center w-11 h-11">
                            <span class="icon-[mdi--hand-coin]"></span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-medium">Simpanan</h2>
                            <p class="text-gray-500 text-sm font-body">Pengelolaan simpanan di sini</p>
                        </div>
                    </div>
                    <div class="text-secondary text-3xl">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>
