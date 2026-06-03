<script setup>
import CardInfo from '@/Components/CardInfo.vue';
import VerticalBarChart from '@/Components/Dashboard/VerticalBarChart.vue';
import FinancingWaffleChart from '@/Components/FinancingWaffleChart.vue';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import EyeIcon from '@/Icons/EyeIcon.vue';

defineProps({
    data: Object,
    can: Object,
    role: Object,
    selectedFilter: String,
    selectedTransactionFilter: String,
});

</script>

<template>
    <!-- INFO -->
    <div v-if="role === 'Ketua Murabahah'" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <CardInfo title="Total Modal Belum Diputar" :content="parseCurrencyAmount(data.total_saving_amount)" />
        <CardInfo title="Jumlah Pembiayaan Aktif" :content="data.total_staff" :percentage="total_staff_percentage" />
        <CardInfo title="Total Permohonan Pembiayaan" :content="data.total_active_member"
            :percentage="total_active_member_percentage" :filter="selectedFilter" />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <div class="card-layout col-span-3">
            <h1 class="card-title">Grafik Pendapatan Margin</h1>
            <VerticalBarChart class="col-span-3 pt-10" title="Grafik Pendapatan Margin" :data="revenues"
                :filter="selectedFilter" />
        </div>
        <div class="card-layout col-span-2">
            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                <h1 class="card-title">Peta Pembiayaan</h1>
                <h2 class="text-2xl font-semibold text-primary mt-2">Rp{{ totalAmount }}</h2>
                <p class="text-gray-500 font-body text-sm">Total Pembiayaan Tersalurkan</p>
            </div>
            <FinancingWaffleChart :data="{ launched: 85, underReview: 50, declined: 35 }" />
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card-layout">
            <div class="flex justify-between items-center">
                <h1 class="card-title">Pembayaran Angsuran Terlambat</h1>
                <div class="bg-white border border-stroke px-4 py-2 rounded-lg">Selengkapnya</div>
            </div>
            <div class="max-w-full mt-4 overflow-x-auto custom-scrollbar">
                <table class="min-w-full">
                    <thead
                        class="border-y-2 border-gray-100 dark:border-gray-500 font-medium text-gray-500 px-2 dark:text-gray-400">
                        <tr class="">
                            <td class="py-5 text-center">No. Transaksi</td>
                            <td class="py-5 text-center">Nama Nasabah</td>
                            <td class="py-5 text-center">Waktu Telat</td>
                            <td class="py-5 text-center">Aksi</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="data in data.recent_transactions"
                            class="border-y-2 border-gray-100 dark:border-gray-500">
                            <td class="py-5 text-center">{{ data.transaction_code }}</td>
                            <td class="py-5 text-center">{{ data.user_name }}</td>
                            <td class="py-5 text-center">{{ data.created_at }}</td>
                            <td class="py-5 text-center">
                                <a
                                    :href="data.product === 'Simpanan' ? `/admin/savings/show/${data.id}` : `/admin/financings/show/${data.id}`">
                                    <EyeIcon
                                        class="w-5 h-5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" />
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-layout">
            <div class="flex justify-between items-center">
                <h1 class="card-title">Permohonan Pembiayaan Sedang Berjalan</h1>
                <div class="bg-white border border-stroke px-4 py-2 rounded-lg">Selengkapnya</div>
            </div>
            <div class="max-w-full mt-4 overflow-x-auto custom-scrollbar">
                <table class="min-w-full">
                    <thead
                        class="border-y-2 border-gray-100 dark:border-gray-500 font-medium text-gray-500 px-2 dark:text-gray-400">
                        <tr class="">
                            <td class="py-5 text-center">No. Transaksi</td>
                            <td class="py-5 text-center">Nama Objek</td>
                            <td class="py-5 text-center">Status</td>
                            <td class="py-5 text-center">Aksi</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="data in data.recent_transactions"
                            class="border-y-2 border-gray-100 dark:border-gray-500">
                            <td class="py-5 text-center">{{ data.transaction_code }}</td>
                            <td class="py-5 text-center">{{ data.user_name }}</td>
                            <td class="py-5 text-center">{{ data.created_at }}</td>
                            <td class="py-5 text-center">
                                <a
                                    :href="data.product === 'Simpanan' ? `/admin/savings/show/${data.id}` : `/admin/financings/show/${data.id}`">
                                    <EyeIcon
                                        class="w-5 h-5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" />
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
