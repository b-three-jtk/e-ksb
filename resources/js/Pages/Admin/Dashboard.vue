<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { router, usePage } from '@inertiajs/vue3';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import { defineAsyncComponent } from 'vue';

const KetuaPengawas = defineAsyncComponent(() => import('./Dashboard/KetuaPengawas.vue'));
const Bendahara = defineAsyncComponent(() => import('./Dashboard/Bendahara.vue'));
const Sekretaris = defineAsyncComponent(() => import('./Dashboard/Sekretaris.vue'));
const KetuaStafMurabahah = defineAsyncComponent(() => import('./Dashboard/KetuaStafMurabahah.vue'));
const PJAnggota = defineAsyncComponent(() => import('./Dashboard/PJAnggota.vue'));
const DPS = defineAsyncComponent(() => import('./Dashboard/DPS.vue'));

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
});

const dates = ref([new Date(), new Date()]);
const selectedFilter = ref('month');
const selectedTransactionFilter = ref('all');
const selectedSavingsFilter = ref('jenis');
const selectedNearestDueFilter = ref('all');
const selectedSavingTransactionFilter = ref('all');
const isDarkMode = ref(false);

console.log(props);

onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark')

    const observer = new MutationObserver(() => {
        isDarkMode.value = document.documentElement.classList.contains('dark')
    })
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })

    router.reload({
        only: [
            'pertumbuhan_pendapatan', 'pertumbuhan_anggota', 'peta_simpanan', 'peta_pembiayaan',
            'transaksi_terbaru', 'jatuh_tempo_terdekat', 'permohonan_murabahah', 'pembayaran_terlambat', 'transaksi_simpanan_terbaru',
        ],
        preserveState: true,
    })
})

watch(dates, () => {
    applyFilter();
}, { deep: true });

watch(selectedFilter, () => {
    applyFilter();
});

watch(selectedTransactionFilter, () => {
    applyFilter();
});

watch(selectedSavingsFilter, () => {
    applyFilter();
});

watch(selectedNearestDueFilter, () => {
    applyFilter();
});

watch(selectedSavingTransactionFilter, () => {
    applyFilter();
});

const applyFilter = () => {
    router.get('/admin/dashboard', {
        start_date: dates.value[0] ? dates.value[0].toISOString().split('T')[0] : null,
        end_date: dates.value[1] ? dates.value[1].toISOString().split('T')[0] : null,
        filter_by: selectedFilter.value,
        transaction_filter: selectedTransactionFilter.value,
        savings_filter: selectedSavingsFilter.value,
        nearest_filter: selectedNearestDueFilter.value,
        saving_transaction_filter: selectedSavingTransactionFilter.value,
    }, {
        preserveState: true,
        replace: true,
        only: [
            'pertumbuhan_pendapatan', 'pertumbuhan_anggota', 'peta_simpanan', 'peta_pembiayaan',
            'transaksi_terbaru', 'jatuh_tempo_terdekat', 'permohonan_murabahah', 'pembayaran_terlambat', 'transaksi_simpanan_terbaru',
        ],
    });
};
</script>

<template>
    <AdminLayout title="Dashboard Admin">
        <div class="flex flex-col gap-4">
            <!-- FILTER -->
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
            <!-- Dashboard Ketua Pengawas -->
            <KetuaPengawas
                @update:selected-transaction-filter="selectedTransactionFilter = $event"
                :selected-transaction-filter="selectedTransactionFilter"
                @update:selected-filter="selectedFilter"
                :selected-filter="selectedFilter"
                @update:selected-savings-filter="selectedSavingsFilter = $event"
                :selected-savings-filter="selectedSavingsFilter"
                :can="can"
                v-if="role === 'Ketua' || role === 'Pengawas'"
                :stats="props.stats"
                :pertumbuhan_pendapatan="props.pertumbuhan_pendapatan"
                :peta_simpanan="props.peta_simpanan"
                :peta_pembiayaan="props.peta_pembiayaan"
                :transaksi_terbaru="props.transaksi_terbaru"
            />
            <!-- Dashboard DPS -->
            <DPS
                v-if="role === 'Dewan Pengawas Syariah'"
                @update:selected-transaction-filter="selectedTransactionFilter = $event"
                :selected-transaction-filter="selectedTransactionFilter"
                @update:selected-filter="selectedFilter"
                :selected-filter="selectedFilter"
                @update:selected-savings-filter="selectedSavingsFilter = $event"
                :selected-savings-filter="selectedSavingsFilter"
                :stats="props.stats"
                :pertumbuhan_pendapatan="props.pertumbuhan_pendapatan"
                :peta_simpanan="props.peta_simpanan"
                :peta_pembiayaan="props.peta_pembiayaan"
                :transaksi_terbaru="props.transaksi_terbaru"
            />
            <!-- Dashboard Bendahara -->
            <Bendahara
                v-if="role === 'Bendahara'"
                :stats="props.stats"
                :pertumbuhan_pendapatan="props.pertumbuhan_pendapatan"
                :selected-filter="selectedFilter"
            />
            <!-- Dashboard Sekretaris -->
            <Sekretaris
                v-if="role === 'Sekretaris'"
                :stats="props.stats"
                :pertumbuhan_anggota="props.pertumbuhan_anggota"
            />
            <!-- Dashboard Ketua Staf Murabahah -->
            <KetuaStafMurabahah
                v-if="role === 'Ketua Murabahah' || role === 'Staf Murabahah'"
                @update:selected-transaction-filter="selectedTransactionFilter = $event"
                :selected-transaction-filter="selectedTransactionFilter"
                :peta_pembiayaan="props.peta_pembiayaan"
                :pembayaran_terlambat="props.pembayaran_terlambat"
                :permohonan_murabahah="props.permohonan_murabahah"
                :pertumbuhan_pendapatan="props.pertumbuhan_pendapatan"
                :can="can"
                :stats="props.stats"
                :role="role"
            />
            <!-- Dashboard Penanggung Jawab Anggota -->
            <PJAnggota v-if="role === 'Penanggung Jawab Anggota'"
                @update:selected-nearest-due-filter="selectedNearestDueFilter = $event"
                :selected-nearest-due-filter="selectedNearestDueFilter"
                @update:selected-saving-transaction-filter="selectedSavingTransactionFilter = $event"
                :selected-saving-transaction-filter="selectedSavingTransactionFilter"
                :stats="props.stats"
                :jatuh_tempo_terdekat="props.jatuh_tempo_terdekat"
                :transaksi_simpanan_terbaru="props.transaksi_simpanan_terbaru"
            />
        </div>
    </AdminLayout>
</template>
