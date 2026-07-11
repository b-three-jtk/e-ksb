<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { router, usePage } from '@inertiajs/vue3';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import { defineAsyncComponent } from 'vue';
import { toast } from 'vue3-toastify';

const Ketua = defineAsyncComponent(() => import('./Dashboard/Ketua.vue'));
const Bendahara = defineAsyncComponent(() => import('./Dashboard/Bendahara.vue'));
const Sekretaris = defineAsyncComponent(() => import('./Dashboard/Sekretaris.vue'));
const KetuaStafMurabahah = defineAsyncComponent(() => import('./Dashboard/KetuaStafMurabahah.vue'));
const PJAnggota = defineAsyncComponent(() => import('./Dashboard/PJAnggota.vue'));
const DPS = defineAsyncComponent(() => import('./Dashboard/DPS.vue'));
const Pengawas = defineAsyncComponent(() => import('./Dashboard/Pengawas.vue'));

const page = usePage()

const role = computed(() => page.props.auth.role);

const can = computed(() => page.props.auth.can)

const props = defineProps({
    stats: Object,
    pertumbuhan_pendapatan: Object,
    pertumbuhan_anggota: Object,
    peta_simpanan: Object,
    peta_pembiayaan: Object,
    transaksi_terbaru: Object,
    jatuh_tempo_terdekat: Object,
    permohonan_murabahah: Object,
    pembayaran_terlambat: Object,
    transaksi_simpanan_terbaru: Object,
    ringkasan_anggota_pj: Object,
});

const dates = ref([new Date(), new Date()]);
const selectedFilter = ref('month');
const selectedTransactionFilter = ref('all');
const selectedSavingsFilter = ref('jenis');
const selectedNearestDueFilter = ref('all');
const selectedTransaksiSimpananFilter = ref('all');
const isDarkMode = ref(false);

console.log(props);

onMounted(() => {
    if (page.props.flash?.login_success) {
        toast.success('Login berhasil, Selamat Datang!', {
            autoClose: 3000,
            position: 'bottom-right',
        })
    }

    isDarkMode.value = document.documentElement.classList.contains('dark')

    const observer = new MutationObserver(() => {
        isDarkMode.value = document.documentElement.classList.contains('dark')
    })
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })

    router.reload({
        only: [
            'pertumbuhan_pendapatan', 'pertumbuhan_anggota', 'peta_simpanan', 'peta_pembiayaan',
            'transaksi_terbaru', 'jatuh_tempo_terdekat', 'permohonan_murabahah', 'pembayaran_terlambat', 'transaksi_simpanan_terbaru', 'ringkasan_anggota_pj',
        ],
        preserveState: true,
    })
})

const globalUpdateFields = [
    'stats',
    'pertumbuhan_pendapatan',
    'pertumbuhan_anggota',
    'peta_simpanan',
    'peta_pembiayaan',
    'transaksi_terbaru',
    'jatuh_tempo_terdekat',
    'permohonan_murabahah',
    'pembayaran_terlambat',
    'transaksi_simpanan_terbaru',
    'ringkasan_anggota_pj'
];

const filterDataMap = {
    dates: globalUpdateFields,
    selectedFilter: globalUpdateFields,
    selectedTransactionFilter: ['transaksi_terbaru'],
    selectedSavingsFilter: ['peta_simpanan'],
    selectedNearestDueFilter: ['jatuh_tempo_terdekat'],
    selectedTransaksiSimpananFilter: ['transaksi_simpanan_terbaru'],
};

const applyFilter = (changedKey) => {
    let startDate = null;
    let endDate = null;

    if (dates.value && Array.isArray(dates.value) && dates.value.length === 2 && dates.value[0] && dates.value[1]) {
        startDate = dates.value[0].toISOString();
        endDate = dates.value[1].toISOString();
    }

    router.get('/admin/dashboard', {
        filter_by: selectedFilter.value,
        transaction_filter: selectedTransactionFilter.value,
        savings_filter: selectedSavingsFilter.value,
        nearest_filter: selectedNearestDueFilter.value,
        saving_transaction_filter: selectedTransaksiSimpananFilter.value,
        start_date: startDate,
        end_date: endDate,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: filterDataMap[changedKey] || [],
    });
};

let isProgrammaticChange = false;

watch(dates, () => {
    if (isProgrammaticChange) return;
    if (dates.value && Array.isArray(dates.value) && dates.value[0] && dates.value[1]) {
        isProgrammaticChange = true;
        selectedFilter.value = 'custom';
        applyFilter('dates');
        setTimeout(() => isProgrammaticChange = false, 0);
    } else if (!dates.value) {
        applyFilter('dates');
    }
}, { deep: true });

watch(selectedFilter, () => {
    if (isProgrammaticChange) return;
    if (selectedFilter.value !== 'custom') {
        isProgrammaticChange = true;
        dates.value = null;
        applyFilter('selectedFilter');
        setTimeout(() => isProgrammaticChange = false, 0);
    }
});

watch(selectedTransactionFilter, () => applyFilter('selectedTransactionFilter'));
watch(selectedSavingsFilter, () => applyFilter('selectedSavingsFilter'));
watch(selectedNearestDueFilter, () => applyFilter('selectedNearestDueFilter'));
watch(selectedTransaksiSimpananFilter, () => applyFilter('selectedTransaksiSimpananFilter'));
</script>

<template>
    <AdminLayout title="Dashboard Admin">


        <div class="flex flex-col gap-4">
            <!-- Header & Global Filter Bar -->
            <div
                class="flex flex-col xl:flex-row justify-between items-start xl:items-center bg-white dark:bg-gray-900 rounded-xl p-5 border border-gray-200/60 dark:border-gray-800 shadow-xs">
                <div class="flex items-center gap-4">
                    <div class="rounded-lg bg-light-bg text-3xl text-brand-800 dark:text-gray-100 dark:bg-yellow-800 p-3">
                        <span class="icon-[mdi--human-hello-variant]"></span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <h1 class="card-title">
                            Selamat Datang, {{ page.props.auth.user.nama }}!
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Saat ini berada pada Periode Buku {{ new Date().getFullYear() }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 mt-4 xl:mt-0">
                    <!-- Date Range Picker -->
                    <div class="w-64">
                        <VueDatePicker v-model="dates" range :enable-time-picker="false" :is-dark="isDarkMode"
                            placeholder="Pilih Tanggal" format="dd MMM yyyy"></VueDatePicker>
                    </div>

                    <!-- Filter Dropdown -->
                    <select v-model="selectedFilter"
                        class="h-[38px] rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm focus:ring-primary focus:border-primary">
                        <option value="day">Harian</option>
                        <option value="month">Bulanan</option>
                        <option value="year">Tahunan</option>
                        <option value="custom" disabled hidden>Kustom</option>
                    </select>
                </div>
            </div>
            <Ketua @update:selected-transaction-filter="selectedTransactionFilter = $event"
                :selected-transaction-filter="selectedTransactionFilter"
                @update:selected-filter="selectedFilter = $event" :selected-filter="selectedFilter"
                @update:selected-savings-filter="selectedSavingsFilter = $event"
                :selected-savings-filter="selectedSavingsFilter" :can="can"
                v-if="role === 'Ketua' || role === 'Administrator Sistem'" :stats="props.stats"
                :pertumbuhan_pendapatan="props.pertumbuhan_pendapatan" :peta_simpanan="props.peta_simpanan"
                :peta_pembiayaan="props.peta_pembiayaan" :transaksi_terbaru="props.transaksi_terbaru" />
            <!-- Dashboard Pengawas -->
            <Pengawas @update:selected-transaction-filter="selectedTransactionFilter = $event"
                :selected-transaction-filter="selectedTransactionFilter"
                @update:selected-filter="selectedFilter = $event" :selected-filter="selectedFilter"
                @update:selected-savings-filter="selectedSavingsFilter = $event"
                :selected-savings-filter="selectedSavingsFilter" :can="can" v-if="role === 'Pengawas'"
                :stats="props.stats" :pertumbuhan_pendapatan="props.pertumbuhan_pendapatan"
                :peta_simpanan="props.peta_simpanan" :peta_pembiayaan="props.peta_pembiayaan"
                :transaksi_terbaru="props.transaksi_terbaru" />
            <!-- Dashboard DPS -->
            <DPS v-if="role === 'Dewan Pengawas Syariah'"
                @update:selected-transaction-filter="selectedTransactionFilter = $event"
                :selected-transaction-filter="selectedTransactionFilter"
                @update:selected-filter="selectedFilter = $event" :selected-filter="selectedFilter"
                @update:selected-savings-filter="selectedSavingsFilter = $event"
                :selected-savings-filter="selectedSavingsFilter" :stats="props.stats"
                :pertumbuhan_pendapatan="props.pertumbuhan_pendapatan" :peta_simpanan="props.peta_simpanan"
                :peta_pembiayaan="props.peta_pembiayaan" :transaksi_terbaru="props.transaksi_terbaru" />
            <!-- Dashboard Bendahara -->
            <Bendahara v-if="role === 'Bendahara'"
                @update:selected-transaction-filter="selectedTransactionFilter = $event"
                :selected-transaction-filter="selectedTransactionFilter"
                @update:selected-filter="selectedFilter = $event" :selected-filter="selectedFilter"
                @update:selected-savings-filter="selectedSavingsFilter = $event"
                :selected-savings-filter="selectedSavingsFilter" :can="can" :stats="props.stats"
                :pertumbuhan_pendapatan="props.pertumbuhan_pendapatan" :peta_simpanan="props.peta_simpanan"
                :transaksi_terbaru="props.transaksi_terbaru" />
            <!-- Dashboard Sekretaris -->
            <Sekretaris v-if="role === 'Sekretaris'" :stats="props.stats"
                @update:selected-filter="selectedFilter = $event" :selected-filter="selectedFilter"
                :pertumbuhan_anggota="props.pertumbuhan_anggota" />
            <!-- Dashboard Ketua Staf Murabahah -->
            <KetuaStafMurabahah v-if="role === 'Ketua Murabahah' || role === 'Staf Murabahah'"
                @update:selected-transaction-filter="selectedTransactionFilter = $event"
                :selected-transaction-filter="selectedTransactionFilter"
                @update:selected-filter="selectedFilter = $event" :selected-filter="selectedFilter"
                :peta_pembiayaan="props.peta_pembiayaan" :pembayaran_terlambat="props.pembayaran_terlambat"
                :permohonan_murabahah="props.permohonan_murabahah"
                :pertumbuhan_pendapatan="props.pertumbuhan_pendapatan" :can="can" :stats="props.stats" :role="role" />
            <!-- Dashboard Penanggung Jawab Anggota -->
            <PJAnggota v-if="role === 'Penanggung Jawab Anggota'"
                @update:selected-nearest-due-filter="selectedNearestDueFilter = $event"
                :selected-nearest-due-filter="selectedNearestDueFilter"
                @update:selected-saving-transaction-filter="selectedTransaksiSimpananFilter = $event"
                :selected-saving-transaction-filter="selectedTransaksiSimpananFilter" :stats="props.stats"
                :jatuh_tempo_terdekat="props.jatuh_tempo_terdekat"
                :transaksi_simpanan_terbaru="props.transaksi_simpanan_terbaru"
                :ringkasan_anggota_pj="props.ringkasan_anggota_pj" />
        </div>
    </AdminLayout>
</template>
