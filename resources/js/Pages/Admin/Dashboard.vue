<script setup>
import { ref, watch } from 'vue';
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import CardInfo from '@/Components/CardInfo.vue'
import CardStatisticBar from '@/Components/CardStatisticBar.vue'
import dateParser from '@/Composables/dateParser.js';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import { Link, router } from '@inertiajs/vue3';
import ReviewIcon from '@/Icons/ReviewIcon.vue';
import NoArchiveIcon from '@/Icons/NoArchiveIcon.vue';
import InfoCircleIcon from '@/Icons/InfoCircleIcon.vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';

const props = defineProps({
    active_user_count: Number,
    active_user_percentage: Number,
    total_saving_amount: Number,
    total_financing_amount: Number,
    transaction_data: Object,
    registration_data: Object,
    financing_data: Object,
    financing_stats: Object,
});

const dates = ref([new Date(), new Date()]);
const selectedFilter = ref('month');
const activeIndex = ref(0);

const nextFinancingData = () => {
    if (activeIndex.value < props.financing_data.length - 1) {
        activeIndex.value += 1;
    }
};

const prevFinancingData = () => {
    if (activeIndex.value > 0) {
        activeIndex.value -= 1;
    }
};

watch(dates, () => {
    applyFilter();
}, { deep: true });

watch(selectedFilter, () => {
    applyFilter();
});

// everytime dates change, update data accordingly
const applyFilter = () => {
    router.get('/admin/dashboard', {
        start_date: dates.value[0] ? dates.value[0].toISOString().split('T')[0] : null,
        end_date: dates.value[1] ? dates.value[1].toISOString().split('T')[0] : null,
        filter_by: selectedFilter.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

</script>

<template>
    <AdminLayout title="Dashboard Admin">
        <div class="flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <div class="mr-auto min-w-75">
                    <VueDatePicker v-model="dates" range></VueDatePicker>
                </div>
                <div class="relative z-20 bg-transparent">
                    <select
                        v-model="selectedFilter"
                        class="h-11 w-full font-body appearance-none rounded-lg border px-4 bg-white pr-11 text-sm shadow-theme-xs focus:outline-hidden dark:bg-dark-900 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="day">Harian</option>
                        <option value="month">Bulanan</option>
                        <option value="year">Tahunan</option>
                    </select>
                    <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400"
                        viewBox="0 0 20 20" fill="none">
                        <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <CardInfo title="Total Kas" :content="parseCurrencyAmount(total_saving_amount)" :percentage="5" />
                <CardInfo title="Total Pembiayaan" :content="parseCurrencyAmount(total_financing_amount)"
                    :percentage="-3" />
                <CardInfo title="Jumlah Anggota Aktif" :content="active_user_count" :percentage="active_user_percentage" :filter="selectedFilter" />
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <CardStatisticBar title="Statistik Permohonan Pembiayaan" :data="Object.values(financing_stats)" />
                <div class="row-span-4 lg:row-span-4">
                    <div class="overflow-hidden card-layout h-full w-full">
                        <div class="flex flex-col gap-2 mb-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="card-title">Permohonan Registrasi Terbaru</h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                    <svg class="stroke-current fill-white dark:fill-gray-800" width="20" height="20"
                                        viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2.29004 5.90393H17.7067" stroke="" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M17.7075 14.0961H2.29085" stroke="" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path
                                            d="M12.0826 3.33331C13.5024 3.33331 14.6534 4.48431 14.6534 5.90414C14.6534 7.32398 13.5024 8.47498 12.0826 8.47498C10.6627 8.47498 9.51172 7.32398 9.51172 5.90415C9.51172 4.48432 10.6627 3.33331 12.0826 3.33331Z"
                                            fill="" stroke="" stroke-width="1.5" />
                                        <path
                                            d="M7.91745 11.525C6.49762 11.525 5.34662 12.676 5.34662 14.0959C5.34661 15.5157 6.49762 16.6667 7.91745 16.6667C9.33728 16.6667 10.4883 15.5157 10.4883 14.0959C10.4883 12.676 9.33728 11.525 7.91745 11.525Z"
                                            fill="" stroke="" stroke-width="1.5" />
                                    </svg>
                                    Filter
                                </button>

                                <button
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                    See all
                                </button>
                            </div>
                        </div>

                        <div class="max-w-full overflow-x-auto custom-scrollbar">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                Nama</p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                Email</p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Unit
                                                Kerja
                                            </p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                Tanggal
                                            </p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Aksi
                                            </p>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody v-if="registration_data?.length">
                                    <tr v-for="data in registration_data" :key="index"
                                        class="border-t border-gray-100 dark:border-gray-800">
                                        <td class="py-5 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ data.name }}
                                            </p>
                                        </td>
                                        <td class="py-5 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ data.email }}
                                            </p>
                                        </td>
                                        <td class="py-5 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ data.work_unit }}
                                            </p>
                                        </td>
                                        <td class="py-5 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ dateParser(data.created_at) }}
                                            </p>
                                        </td>
                                        <td class="py-5 whitespace-nowrap">
                                            <button
                                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-light-accent px-4 py-2 text-white font-medium text-xs shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-light-accent dark:text-white dark:hover:bg-light-accent/70] dark:hover:text-gray-200">
                                                <ReviewIcon width="16px" height="16px" />
                                                Tinjau
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td colspan="5" class="py-5 text-center">
                                            <NoArchiveIcon width="120px" height="120px" />
                                            <p class="text-dark-text text-sm dark:text-gray-400 pt-4">
                                                Tidak ada data registrasi terbaru.
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row-span-4 lg:row-span-4">
                    <div class="overflow-hidden card-layout h-full w-full">
                        <div class="flex flex-col gap-2 mb-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="card-title">Transaksi Terbaru</h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                    <svg class="stroke-current fill-white dark:fill-gray-800" width="20" height="20"
                                        viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2.29004 5.90393H17.7067" stroke="" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M17.7075 14.0961H2.29085" stroke="" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path
                                            d="M12.0826 3.33331C13.5024 3.33331 14.6534 4.48431 14.6534 5.90414C14.6534 7.32398 13.5024 8.47498 12.0826 8.47498C10.6627 8.47498 9.51172 7.32398 9.51172 5.90415C9.51172 4.48432 10.6627 3.33331 12.0826 3.33331Z"
                                            fill="" stroke="" stroke-width="1.5" />
                                        <path
                                            d="M7.91745 11.525C6.49762 11.525 5.34662 12.676 5.34662 14.0959C5.34661 15.5157 6.49762 16.6667 7.91745 16.6667C9.33728 16.6667 10.4883 15.5157 10.4883 14.0959C10.4883 12.676 9.33728 11.525 7.91745 11.525Z"
                                            fill="" stroke="" stroke-width="1.5" />
                                    </svg>
                                    Filter
                                </button>

                                <button
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                    See all
                                </button>
                            </div>
                        </div>

                        <div class="max-w-full overflow-x-auto custom-scrollbar">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">No.
                                                Transaksi</p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                Pemohon</p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Total
                                            </p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Tipe
                                            </p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                                Tanggal</p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Aksi
                                            </p>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody v-if="transaction_data?.length">
                                    <tr v-for="data in transaction_data" :key="index"
                                        class="border-t border-gray-100 dark:border-gray-800">
                                        <td class="py-5 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ data.id }}
                                            </p>
                                        </td>
                                        <td class="py-5 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ data.user_name }}
                                            </p>
                                        </td>
                                        <td class="py-5 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ parseCurrencyAmount(data.amount) }}
                                            </p>
                                        </td>
                                        <td class="py-5 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ data.type }}
                                            </p>
                                        </td>
                                        <td class="py-5 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ dateParser(data.created_at) }}
                                            </p>
                                        </td>
                                        <td class="py-5 whitespace-nowrap">
                                            <Link :href="`/admin/savings/show/${data.id}`"
                                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-dark-text font-medium text-xs shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                                <InfoCircleIcon width="18px" height="18px" />
                                                Detail
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td colspan="5" class="py-5 text-center">
                                            <NoArchiveIcon width="120px" height="120px" />
                                            <p class="text-dark-text text-sm dark:text-gray-400 pt-4">
                                                Tidak ada data transaksi terbaru.
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div
                    class="flex flex-col px-8 py-6 bg-light-bg rounded-2xl border border-gray-200 dark:bg-gray-800 dark:border-gray-700 gap-4">
                    <div class="flex justify-between items-center">
                        <div class="flex flex-col gap-2">
                            <h1 class="card-title">Pengajuan Pembiayaan Murabahah</h1>
                            <p v-if="financing_data?.length" class="text-gray-500 dark:text-gray-300">No. Transaksi #{{
                                financing_data[activeIndex]?.id ?? '' }}</p>
                        </div>
                        <button
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                            See all
                        </button>
                    </div>
                    <div
                        class="card-info bg-white dark:bg-gray-800 py-6 rounded-xl border border-stroke dark:border-gray-700 dark:text-gray-300 flex flex-col gap-2">
                        <div v-if="financing_data?.length" class="grid grid-cols-1 md:grid-cols-2 gap-4 px-8 pb-5 pt-2">
                            <div class="flex flex-col">
                                <p>Permintaan Produk</p>
                                <h1 class="font-semibold font-body text-lg">{{ financing_data[activeIndex]?.product_type
                                    ?? '' }}</h1>
                            </div>
                            <div class="flex flex-col">
                                <p>Nomor Anggota</p>
                                <h1 class="font-semibold font-body text-lg">{{
                                    financing_data[activeIndex]?.member_number ?? '' }}</h1>
                            </div>
                            <div class="flex flex-col">
                                <p>Status</p>
                                <h1 class="font-semibold font-body text-lg">{{ financing_data[activeIndex]?.status ?? ''
                                    }}</h1>
                            </div>
                            <div class="flex flex-col">
                                <p>Nama Anggota</p>
                                <h1 class="font-semibold font-body text-lg">{{ financing_data[activeIndex]?.user_name ??
                                    '' }}</h1>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center gap-4 pb-2">
                            <span class="icon-[streamline-freehand-color--task-list-pin-1]"
                                style="width: 100px; height: 100px;"></span>
                            <p class="text-center text-dark-text dark:text-gray-400">Tidak ada data pengajuan
                                pembiayaan.</p>
                        </div>
                        <div v-if="financing_data?.length" class="border border-stroke"></div>
                        <div class="flex justify-end px-8 pt-2">
                            <button v-if="financing_data?.length"
                                class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-secondary px-5 py-2.5 text-white font-medium shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-secondary dark:text-gray-100 dark:hover:bg-secondary/70 dark:hover:text-gray-200">
                                <ReviewIcon width="24px" height="24px" />
                                Tinjau
                            </button>
                        </div>
                    </div>
                    <div class="flex gap-2 justify-center text-dark-text">
                        <button @click="prevFinancingData">
                            <span class="icon-[tabler--chevron-left]" style="width: 24px; height: 24px;"></span>
                        </button>
                        <button @click="nextFinancingData">
                            <span class="icon-[tabler--chevron-right]" style="width: 24px; height: 24px;"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
