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

const filterDataMap = {
    dates:                        ['pertumbuhan_pendapatan', 'pertumbuhan_anggota', 'peta_simpanan', 'peta_pembiayaan', 'transaksi_terbaru', 'jatuh_tempo_terdekat', 'permohonan_murabahah', 'pembayaran_terlambat', 'transaksi_simpanan_terbaru'],
    selectedFilter:               ['pertumbuhan_pendapatan', 'pertumbuhan_anggota'],
    selectedTransactionFilter:    ['transaksi_terbaru'],
    selectedSavingsFilter:        ['peta_simpanan'],
    selectedNearestDueFilter:     ['jatuh_tempo_terdekat'],
    selectedSavingTransactionFilter: ['transaksi_simpanan_terbaru'],
};

const applyFilter = (changedKey) => {
    router.get('/admin/dashboard', {
        filter_by:                selectedFilter.value,
        transaction_filter:       selectedTransactionFilter.value,
        savings_filter:           selectedSavingsFilter.value,
        nearest_filter:           selectedNearestDueFilter.value,
        saving_transaction_filter: selectedSavingTransactionFilter.value,
    }, {
        preserveState:  true,
        preserveScroll: true,
        replace:        true,
        only: filterDataMap[changedKey],
    });
};

watch(dates,                        () => applyFilter('dates'),                        { deep: true });
watch(selectedFilter,               () => applyFilter('selectedFilter'));
watch(selectedTransactionFilter,    () => applyFilter('selectedTransactionFilter'));
watch(selectedSavingsFilter,        () => applyFilter('selectedSavingsFilter'));
watch(selectedNearestDueFilter,     () => applyFilter('selectedNearestDueFilter'));
watch(selectedSavingTransactionFilter, () => applyFilter('selectedSavingTransactionFilter'));
</script>

<template>
    <AdminLayout title="Dashboard Admin">
        <div class="flex flex-col gap-4">
            <!-- Dashboard Ketua Pengawas -->
            <KetuaPengawas
                @update:selected-transaction-filter="selectedTransactionFilter = $event"
                :selected-transaction-filter="selectedTransactionFilter"
                @update:selected-filter="selectedFilter = $event"
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
                @update:selected-filter="selectedFilter = $event"
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
                @update:selected-filter="selectedFilter = $event"
                :selected-filter="selectedFilter"
            />
            <!-- Dashboard Sekretaris -->
            <Sekretaris
                v-if="role === 'Sekretaris'"
                :stats="props.stats"
                @update:selected-filter="selectedFilter = $event"
                :selected-filter="selectedFilter"
                :pertumbuhan_anggota="props.pertumbuhan_anggota"
            />
            <!-- Dashboard Ketua Staf Murabahah -->
            <KetuaStafMurabahah
                v-if="role === 'Ketua Murabahah' || role === 'Staf Murabahah'"
                @update:selected-transaction-filter="selectedTransactionFilter = $event"
                :selected-transaction-filter="selectedTransactionFilter"
                @update:selected-filter="selectedFilter = $event"
                :selected-filter="selectedFilter"
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
