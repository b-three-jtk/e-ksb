<script setup>
import CardInfo from '@/Components/CardInfo.vue';
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
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <CardInfo title="Total Simpanan Masuk" :content="parseCurrencyAmount(data.total_saving_amount)" />
        <CardInfo title="Total Simpanan Keluar" :content="data.total_staff" :percentage="total_staff_percentage" />
        <CardInfo title="Total Angsuran Belum Lunas" :content="data.total_active_member"
            :percentage="total_active_member_percentage" :filter="selectedFilter" />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card-layout">
            <div class="flex justify-between items-center">
                <h1 class="card-title">Jatuh Tempo Terdekat</h1>
                <div class="bg-white border border-stroke px-4 py-2 rounded-lg">Selengkapnya</div>
            </div>
            <div class="max-w-full mt-4 overflow-x-auto custom-scrollbar">
                <table class="min-w-full">
                    <thead
                        class="border-y-2 border-gray-100 dark:border-gray-500 font-medium text-gray-500 px-2 dark:text-gray-400">
                        <tr class="">
                            <td class="py-5 text-center">Jenis</td>
                            <td class="py-5 text-center">Jatuh Tempo</td>
                            <td class="py-5 text-center">Anggota</td>
                            <td class="py-5 text-center">Nominal</td>
                            <td class="py-5 text-center">Status</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="data in data.recent_transactions"
                            class="border-y-2 border-gray-100 dark:border-gray-500">
                            <td class="py-5 text-center">{{ data.transaction_code }}</td>
                            <td class="py-5 text-center">{{ data.user_name }}</td>
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
                <h1 class="card-title">Transaksi Simpanan Terbaru</h1>
                <div class="bg-white border border-stroke px-4 py-2 rounded-lg">Selengkapnya</div>
            </div>
            <div class="max-w-full mt-4 overflow-x-auto custom-scrollbar">
                <table class="min-w-full">
                    <thead
                        class="border-y-2 border-gray-100 dark:border-gray-500 font-medium text-gray-500 px-2 dark:text-gray-400">
                        <tr class="">
                            <td class="py-5 text-center">No. Transaksi</td>
                            <td class="py-5 text-center">Anggota</td>
                            <td class="py-5 text-center">Nominal</td>
                            <td class="py-5 text-center">Jenis</td>
                            <td class="py-5 text-center">Aksi</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="data in data.recent_transactions"
                            class="border-y-2 border-gray-100 dark:border-gray-500">
                            <td class="py-5 text-center">{{ data.transaction_code }}</td>
                            <td class="py-5 text-center">{{ data.user_name }}</td>
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
