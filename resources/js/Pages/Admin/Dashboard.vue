<template>
    <AdminLayout>
        <div class="flex flex-col gap-4">
            <div class="flex justify-between">
                <!-- TODO:  -->
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <CardInfo title="Total Kas" :content="parseCurrencyAmount(total_saving_amount)" :percentage="5" />
                <CardInfo title="Total Pembiayaan" :content="parseCurrencyAmount(total_financing_amount)"
                    :percentage="-3" />
                <CardInfo title="Jumlah Anggota Aktif" :content="active_user_count" :percentage="2" />
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <CardStatisticBar title="Statistik Penjualan" />
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
                                <tbody>
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
                                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-light-accent px-4 py-2 text-white font-medium text-xs shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 16 16" fill="none"
                                                    class="group-hover:text-gray-800 dark:group-hover:text-white transition-colors">
                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                        d="M8 3.5C8.26522 3.5 8.51957 3.39464 8.70711 3.20711C8.89464 3.01957 9 2.76522 9 2.5C9 2.23478 8.89464 1.98043 8.70711 1.79289C8.51957 1.60536 8.26522 1.5 8 1.5C7.73478 1.5 7.48043 1.60536 7.29289 1.79289C7.10536 1.98043 7 2.23478 7 2.5C7 2.76522 7.10536 3.01957 7.29289 3.20711C7.48043 3.39464 7.73478 3.5 8 3.5ZM8 5.21142e-08C8.57633 -0.000117575 9.13499 0.198892 9.58145 0.563347C10.0279 0.927802 10.3347 1.43532 10.45 2H13C13.2652 2 13.5196 2.10536 13.7071 2.29289C13.8946 2.48043 14 2.73478 14 3V15C14 15.2652 13.8946 15.5196 13.7071 15.7071C13.5196 15.8946 13.2652 16 13 16H3C2.73478 16 2.48043 15.8946 2.29289 15.7071C2.10536 15.5196 2 15.2652 2 15V3C2 2.73478 2.10536 2.48043 2.29289 2.29289C2.48043 2.10536 2.73478 2 3 2H5.55C5.66527 1.43532 5.97209 0.927802 6.41855 0.563347C6.86501 0.198892 7.42367 -0.000117575 8 5.21142e-08ZM7 5H10.5V3.5H12.5V14.5H3.5V3.5H5.5V5H7ZM10.53 8.28C10.6037 8.21134 10.6628 8.12854 10.7038 8.03654C10.7448 7.94454 10.7668 7.84523 10.7686 7.74452C10.7704 7.64382 10.7518 7.54379 10.7141 7.4504C10.6764 7.35701 10.6203 7.27218 10.549 7.20096C10.4778 7.12974 10.393 7.0736 10.2996 7.03588C10.2062 6.99816 10.1062 6.97963 10.0055 6.98141C9.90478 6.98319 9.80546 7.00523 9.71346 7.04622C9.62146 7.08721 9.53866 7.14631 9.47 7.22L7.5 9.19L7.03 8.72C6.88783 8.58752 6.69978 8.5154 6.50548 8.51882C6.31118 8.52225 6.12579 8.60097 5.98838 8.73838C5.85097 8.87579 5.77225 9.06118 5.76883 9.25548C5.7654 9.44978 5.83752 9.63783 5.97 9.78L6.97 10.78C7.11063 10.9205 7.30125 10.9993 7.5 10.9993C7.69875 10.9993 7.88937 10.9205 8.03 10.78L10.53 8.28Z"
                                                        fill="currentColor" />
                                                </svg>
                                                Tinjau
                                            </button>
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
                                <tbody>
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
                                                <span class="icon-[material-symbols--info-outline-rounded]"
                                                    style="width: 18px; height: 18px;"></span>
                                                Detail
                                            </Link>
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
                            <p class="text-gray-500">No. Transaksi #{{ financing_data[activeIndex]?.id ?? '' }}</p>
                        </div>
                        <button
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                            See all
                        </button>
                    </div>
                    <div
                        class="card-info bg-white dark:bg-gray-800 py-6 rounded-xl border border-stroke dark:border-gray-700 flex flex-col gap-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-8 pb-5 pt-2">
                            <div class="flex flex-col">
                                <p>Permintaan Produk</p>
                                <h1 class="font-semibold font-body text-lg">{{ financing_data[activeIndex]?.product_type ?? '' }}</h1>
                            </div>
                            <div class="flex flex-col">
                                <p>Nomor Anggota</p>
                                <h1 class="font-semibold font-body text-lg">{{ financing_data[activeIndex]?.member_number ?? '' }}</h1>
                            </div>
                            <div class="flex flex-col">
                                <p>Status</p>
                                <h1 class="font-semibold font-body text-lg">{{ financing_data[activeIndex]?.status ?? '' }}</h1>
                            </div>
                            <div class="flex flex-col">
                                <p>Nama Anggota</p>
                                <h1 class="font-semibold font-body text-lg">{{ financing_data[activeIndex]?.user_name ?? '' }}</h1>
                            </div>
                        </div>
                        <div class="border border-stroke"></div>
                        <div class="flex justify-end px-8 pt-2">
                            <button
                                class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-secondary px-5 py-2.5 text-white font-medium shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"
                                    fill="none"
                                    class="group-hover:text-gray-800 dark:group-hover:text-white transition-colors">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M8 3.5C8.26522 3.5 8.51957 3.39464 8.70711 3.20711C8.89464 3.01957 9 2.76522 9 2.5C9 2.23478 8.89464 1.98043 8.70711 1.79289C8.51957 1.60536 8.26522 1.5 8 1.5C7.73478 1.5 7.48043 1.60536 7.29289 1.79289C7.10536 1.98043 7 2.23478 7 2.5C7 2.76522 7.10536 3.01957 7.29289 3.20711C7.48043 3.39464 7.73478 3.5 8 3.5ZM8 5.21142e-08C8.57633 -0.000117575 9.13499 0.198892 9.58145 0.563347C10.0279 0.927802 10.3347 1.43532 10.45 2H13C13.2652 2 13.5196 2.10536 13.7071 2.29289C13.8946 2.48043 14 2.73478 14 3V15C14 15.2652 13.8946 15.5196 13.7071 15.7071C13.5196 15.8946 13.2652 16 13 16H3C2.73478 16 2.48043 15.8946 2.29289 15.7071C2.10536 15.5196 2 15.2652 2 15V3C2 2.73478 2.10536 2.48043 2.29289 2.29289C2.48043 2.10536 2.73478 2 3 2H5.55C5.66527 1.43532 5.97209 0.927802 6.41855 0.563347C6.86501 0.198892 7.42367 -0.000117575 8 5.21142e-08ZM7 5H10.5V3.5H12.5V14.5H3.5V3.5H5.5V5H7ZM10.53 8.28C10.6037 8.21134 10.6628 8.12854 10.7038 8.03654C10.7448 7.94454 10.7668 7.84523 10.7686 7.74452C10.7704 7.64382 10.7518 7.54379 10.7141 7.4504C10.6764 7.35701 10.6203 7.27218 10.549 7.20096C10.4778 7.12974 10.393 7.0736 10.2996 7.03588C10.2062 6.99816 10.1062 6.97963 10.0055 6.98141C9.90478 6.98319 9.80546 7.00523 9.71346 7.04622C9.62146 7.08721 9.53866 7.14631 9.47 7.22L7.5 9.19L7.03 8.72C6.88783 8.58752 6.69978 8.5154 6.50548 8.51882C6.31118 8.52225 6.12579 8.60097 5.98838 8.73838C5.85097 8.87579 5.77225 9.06118 5.76883 9.25548C5.7654 9.44978 5.83752 9.63783 5.97 9.78L6.97 10.78C7.11063 10.9205 7.30125 10.9993 7.5 10.9993C7.69875 10.9993 7.88937 10.9205 8.03 10.78L10.53 8.28Z"
                                        fill="currentColor" />
                                </svg>
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

<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import CardInfo from '@/Components/CardInfo.vue'
import CardStatisticBar from '@/Components/CardStatisticBar.vue'
import dateParser from '@/Composables/dateParser.js';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    active_user_count: Number,
    total_saving_amount: Number,
    total_financing_amount: Number,
    transaction_data: Object,
    registration_data: Object,
    financing_data: Object,
});

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

console.log(props.financing_data);
</script>
