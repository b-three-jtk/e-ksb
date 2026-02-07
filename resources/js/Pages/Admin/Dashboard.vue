<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import CardInfo from '@/Components/CardInfo.vue'
import CardStatisticBar from '@/Components/CardStatisticBar.vue'
import dateParser from '@/Composables/dateParser.js';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import { Link, router, usePage } from '@inertiajs/vue3';
import ReviewIcon from '@/Icons/ReviewIcon.vue';
import NoArchiveIcon from '@/Icons/NoArchiveIcon.vue';
import InfoCircleIcon from '@/Icons/InfoCircleIcon.vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import Button from '@/Components/Form/Button.vue';

const page = usePage()

const user = computed(() => {
    return page.props.auth?.user || null
})

const props = defineProps({
    active_user_count: Number,
    active_user_percentage: Number,
    total_saving_amount: String,
    total_financing_amount: Number,
    total_financing_percentage: Number,
    transaction_data: Object,
    registration_data: Object,
    financing_data: Object,
    financing_stats: Object,
});

const dates = ref([new Date(), new Date()]);
const selectedFilter = ref('month');
const activeIndex = ref(0);
const isDarkMode = ref(false);

onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark')

    const observer = new MutationObserver(() => {
        isDarkMode.value = document.documentElement.classList.contains('dark')
    })
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

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
                    <VueDatePicker v-model="dates" :dark="isDarkMode" range></VueDatePicker>
                </div>
                <div class="relative z-20 bg-transparent">
                    <select v-model="selectedFilter"
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
                <CardInfo title="Total Kas" :content="parseCurrencyAmount(total_saving_amount)" />
                <CardInfo title="Total Pembiayaan" :content="parseCurrencyAmount(total_financing_amount)"
                    :percentage="total_financing_percentage" />
                <CardInfo title="Jumlah Anggota Aktif" :content="active_user_count" :percentage="active_user_percentage"
                    :filter="selectedFilter" />
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <CardStatisticBar title="Statistik Permohonan Pembiayaan" :data="financing_stats"
                    :filter="selectedFilter" />
                <div class="row-span-4">
                    <div class="overflow-hidden card-layout h-full w-full">
                        <div class="flex flex-col gap-2 mb-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="card-title">Permohonan Registrasi Terbaru</h3>
                            </div>
                            <Button
                                :href="user.role?.name === 'Admin' ? '/admin/users/verification' : '/admin/users/list'"
                                variant="light" size="small">
                                See all
                            </Button>
                        </div>

                        <div class="max-w-full overflow-x-auto custom-scrollbar">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-t border-gray-100 dark:border-gray-500">
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 px-2 text-theme-xs dark:text-gray-400">
                                                Nama</p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 px-2 text-theme-xs dark:text-gray-400">
                                                Email</p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 px-2 text-theme-xs dark:text-gray-400">
                                                Unit
                                                Kerja
                                            </p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium text-gray-500 px-2 text-theme-xs dark:text-gray-400">
                                                Tanggal
                                            </p>
                                        </th>
                                        <th v-if="user.role?.name === 'Admin'" class="py-5 text-left">
                                            <p class="font-medium text-gray-500 px-2 text-theme-xs dark:text-gray-400">
                                                Aksi
                                            </p>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody v-if="registration_data?.length">
                                    <tr v-for="data in registration_data"
                                        class="border-t border-gray-100 dark:border-gray-500">
                                        <td class="py-5 px-2 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ data.name }}
                                            </p>
                                        </td>
                                        <td class="py-5 px-2 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ data.email }}
                                            </p>
                                        </td>
                                        <td class="py-5 px-2 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ data.work_unit }}
                                            </p>
                                        </td>
                                        <td class="py-5 px-2 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ dateParser(data.created_at) }}
                                            </p>
                                        </td>
                                        <td v-if="user.role?.name === 'Admin'" class="py-5 px-2 whitespace-nowrap">
                                            <Button :href="`/admin/verifikasi/${data.member_number}`" size="small" variant="accent">
                                                <ReviewIcon width="16px" height="16px" />
                                                Tinjau
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr class="border-t border-gray-100 dark:border-gray-500">
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
                <div class="row-span-4">
                    <div class="overflow-hidden card-layout h-full w-full">
                        <div class="flex flex-col gap-2 mb-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="card-title">Transaksi Terbaru</h3>
                            </div>
                            <Button href="/admin/savings/list" variant="light" size="small">
                                See all
                            </Button>
                        </div>

                        <div class="max-w-full overflow-x-auto custom-scrollbar">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-t border-gray-100 dark:border-gray-500">
                                        <th class="py-5 text-left">
                                            <p class="font-medium px-2 text-gray-500 text-theme-xs dark:text-gray-400">
                                                No.
                                                Transaksi</p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium px-2 text-gray-500 text-theme-xs dark:text-gray-400">
                                                Pemohon</p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium px-2 text-gray-500 text-theme-xs dark:text-gray-400">
                                                Total
                                            </p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium px-2 text-gray-500 text-theme-xs dark:text-gray-400">
                                                Tipe
                                            </p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium px-2 text-gray-500 text-theme-xs dark:text-gray-400">
                                                Tanggal</p>
                                        </th>
                                        <th class="py-5 text-left">
                                            <p class="font-medium px-2 text-gray-500 text-theme-xs dark:text-gray-400">
                                                Aksi
                                            </p>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody v-if="transaction_data?.length">
                                    <tr v-for="data in transaction_data"
                                        class="border-t border-gray-100 dark:border-gray-500">
                                        <td class="py-5 px-2 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ data.transaction_code }}
                                            </p>
                                        </td>
                                        <td class="py-5 px-2 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ data.user_name }}
                                            </p>
                                        </td>
                                        <td class="py-5 px-2 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ parseCurrencyAmount(data.amount) }}
                                            </p>
                                        </td>
                                        <td class="py-5 px-2 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ data.type }}
                                            </p>
                                        </td>
                                        <td class="py-5 px-2 whitespace-nowrap">
                                            <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                                {{ dateParser(data.created_at) }}
                                            </p>
                                        </td>
                                        <td class="py-5 px-2 whitespace-nowrap">
                                            <Button size="small" variant="info"
                                                :href="`/admin/savings/show/${data.id}`">
                                                <InfoCircleIcon width="18px" height="18px" />
                                                Detail
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr class="border-t border-gray-100 dark:border-gray-500">
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
                                financing_data[activeIndex]?.transaction_code ?? '' }}</p>
                        </div>
                        <Button href="#" variant="light" size="small">
                            See all
                        </Button>
                    </div>
                    <div
                        class="card-info bg-white dark:bg-gray-800 py-6 rounded-xl border border-stroke dark:border-gray-700 dark:text-gray-300 flex flex-col gap-2">
                        <div v-if="financing_data?.length" class="grid grid-cols-1 md:grid-cols-2 gap-4 px-8 pb-5 pt-2">
                            <div class="flex flex-col">
                                <p>Permintaan Produk</p>
                                <h1 class="font-semibold font-body text-lg">{{ financing_data[activeIndex]?.product_name
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
                        <div v-if="financing_data?.length" class="border border-stroke dark:border-gray-700"></div>
                        <div class="flex justify-end px-8 pt-2">
                            <Button variant="secondary">
                                <ReviewIcon width="24px" height="24px" />
                                Tinjau
                            </Button>
                        </div>
                    </div>
                    <div class="flex gap-2 justify-center text-dark-text dark:text-gray-400">
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

<style>
.dp__theme_dark {
    --dp-background-color: #1f2937 !important;
    --dp-text-color: #d1d5db !important;
    --dp-primary-text-color: #fff !important;
    --dp-accent-color: #3b82f6 !important;
    --dp-border-color: #374151 !important;
}
</style>
