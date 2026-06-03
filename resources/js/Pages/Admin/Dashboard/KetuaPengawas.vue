<script setup>
import CardInfo from '@/Components/CardInfo.vue';
import VerticalBarChart from '@/Components/Dashboard/VerticalBarChart.vue';
import FinancingWaffleChart from '@/Components/FinancingWaffleChart.vue';
import TransactionTable from '@/Components/Dashboard/TransactionTable.vue';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import BarChart from '@/Components/Dashboard/Barchart.vue'

defineProps({
    data: Object,
    can: Object,
    selectedFilter: String,
    selectedTransactionFilter: String,
});

</script>

<template>
    <!-- INFO -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <CardInfo v-if="can['view_kas']" title="Total Kas" :content="parseCurrencyAmount(data.total_saving_amount)" />
        <CardInfo v-if="can['view_pengurus']" title="Total Pengurus" :content="data.total_staff"
            :percentage="total_staff_percentage" />
        <CardInfo v-if="can['view_anggota']" title="Total Anggota Aktif" :content="data.total_active_member"
            :percentage="total_active_member_percentage" :filter="selectedFilter" />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <div class="card-layout col-span-3">
            <h1 class="card-title">Grafik Pendapatan Margin</h1>
            <VerticalBarChart class="col-span-3 pt-10" title="Grafik Pendapatan Margin" :data="revenues"
                :filter="selectedFilter" />
        </div>
        <div class="card-layout col-span-2">
            <TransactionTable :selected-transaction-filter="selectedTransactionFilter" :data="data" :role="role" />
        </div>
        <div class="col-span-3 grid grid-cols-2 gap-3.5">
            <CardInfo v-if="can['view_anggota']" title="Rasio Kas" :content="data.total_active_member"
                :filter="selectedFilter" />
            <CardInfo v-if="can['view_anggota']" title="Rasio Financing-to-Deposit (FDR)"
                :content="data.total_active_member" :filter="selectedFilter" />
            <div class="card-layout col-span-2">
                <div class="border-b border-stroke pb-4">
                    <h1 class="card-title">Komposisi Simpanan</h1>
                    <h2 class="text-2xl font-semibold text-primary">Rp</h2>
                    <p class="text-gray-500 font-body text-sm">Total Simpanan Masuk</p>
                </div>
                <div class="flex gap-2">
                    <BarChart />
                    <ul class="flex flex-col gap-3.5">
                        <li class="text-gray-400">JUMLAH</li>
                        <li class="bg-gray-200 p-1 rounded-lg">Rp100.000.000 <span class="text-gray-400">(52,6%)</span>
                        </li>
                        <li class="bg-gray-200 p-1 rounded-lg">Rp100.000.000 <span class="text-gray-400">(52,6%)</span>
                        </li>
                        <li class="bg-gray-200 p-1 rounded-lg">Rp100.000.000 <span class="text-gray-400">(52,6%)</span>
                        </li>
                        <li class="bg-gray-200 p-1 rounded-lg">Rp100.000.000 <span class="text-gray-400">(52,6%)</span>
                        </li>
                        <li class="bg-gray-200 p-1 rounded-lg">Rp100.000.000 <span class="text-gray-400">(52,6%)</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-layout col-span-2">
            <FinancingWaffleChart :data="{ launched: 85, underReview: 50, declined: 35 }"
                total-amount="Rp190.000.000" />
        </div>
    </div>
</template>
