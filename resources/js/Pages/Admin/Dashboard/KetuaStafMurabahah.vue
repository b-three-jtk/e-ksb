<script setup>
import CardInfo from '@/Components/CardInfo.vue';
import VerticalBarChart from '@/Components/Dashboard/VerticalBarChart.vue';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import EyeIcon from '@/Icons/EyeIcon.vue';
import TransactionTable from '@/Components/Dashboard/TransactionTable.vue';
import { computed } from 'vue';
import Button from '@/Components/Form/Button.vue';
import { Icon } from '@iconify/vue';
import ReviewIcon from '@/Icons/ReviewIcon.vue';
import useFinancingStatus, { getStatusLabel } from '@/Composables/useFinancingStatus'
import PieChart from '@/Components/Dashboard/PieChart.vue';

defineProps({
    data: Object,
    can: Object,
    role: Object,
    selectedFilter: String,
    selectedTransactionFilter: String,
});

const tableLatePaymentColumns = computed(() => {
    const cols = [
        { key: 'transaction_code', label: 'No. Transaksi' },
        { key: 'user_name', label: 'Anggota' },
        { key: 'days_overdue', label: 'Hari Terlambat' },
        { key: 'amount', label: 'Jumlah' },
    ];
    cols.push({ key: 'action', label: 'Aksi' });
    return cols;
});

const tableMurabahaRequestsColumns = computed(() => {
    const cols = [
        { key: 'transaction_code', label: 'No. Transaksi' },
        { key: 'user_name', label: 'Anggota' },
        { key: 'status', label: 'Status' },
    ];
    cols.push({ key: 'action', label: 'Aksi' });
    return cols;
});

</script>

<template>
    <!-- INFO -->
    <div v-if="role === 'Ketua Murabahah'" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <CardInfo title="Total Modal Belum Diputar" :content="parseCurrencyAmount(data.total_saving_amount)" />
        <CardInfo title="Jumlah Pembiayaan Aktif" :content="data.total_staff" :percentage="total_staff_pct" />
        <CardInfo title="Total Permohonan Pembiayaan" :content="data.total_active_member"
            :percentage="total_active_member_pct" :filter="selectedFilter" />
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
                <h2 class="text-2xl font-semibold text-primary mt-2">{{
                    parseCurrencyAmount(data.total_pembiayaan_tersalurkan) }}</h2>
                <p class="text-gray-500 font-body text-sm">Total Pembiayaan Tersalurkan</p>
            </div>
            <div class="flex items-center justify-center">
                <PieChart :data="data.peta_pembiayaan" class="flex items-center justify-center mt-8" />
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card-layout">
            <div class="flex justify-between items-center">
                <h1 class="card-title">Pembayaran Angsuran Terlambat</h1>
                <div class="bg-white border border-stroke px-4 py-2 rounded-lg">Selengkapnya</div>
            </div>
            <TransactionTable :columns="tableLatePaymentColumns" :rows="data.late_payment_installments">
                <template #action="{ item }">
                    <Link :href="`/admin/financings/show/${item.id}`">
                    <EyeIcon
                        class="w-5 h-5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" />
                    </Link>
                </template>
            </TransactionTable>
        </div>
        <div class="card-layout">
            <div class="flex justify-between items-center">
                <h1 class="card-title">Permohonan Pembiayaan Sedang Berjalan</h1>
                <div class="bg-white border border-stroke px-4 py-2 rounded-lg">Selengkapnya</div>
            </div>
            <TransactionTable :columns="tableMurabahaRequestsColumns" :rows="data.murabahah_requests">
                <template #cell-status="{ item }">
                    <span :class="useFinancingStatus(item.status)">
                        {{ getStatusLabel(item.status) }}
                    </span>
                </template>
                <template #action="{ item }">
                    <Button
                        v-if="can['edit_murabahah'] && (role === 'Staf Murabahah' && (item.status === 'Disetujui' || item.status === 'Ditolak' || item.status === 'Menunggu Kelengkapan Dokumen'))"
                        :href="`/admin/financings/draft/${item.id}`" size="small" variant="transparent">
                        <ReviewIcon width="18px" height="18px" />
                    </Button>
                    <Button
                        v-else-if="can['view_murabahah'] && ((role === 'Staf Murabahah' && ((item.status === 'Angsuran Berjalan') || (item.status === 'Belum Ditinjau') || (item.status === 'Lunas'))) || (role === 'Ketua Murabahah' && (item.status !== 'Belum Ditinjau')))"
                        :href="`/admin/financings/show/${item.id}`" size="small" variant="transparent">
                        <Icon icon="mdi:eye-outline" class="w-5 h-5" />
                    </Button>

                    <Button
                        v-if="can['approve_murabahah'] && (role === 'Ketua Murabahah' && (item.status === 'Belum Ditinjau'))"
                        :href="`/admin/financings/validation/${item.id}`" size="small" variant="transparent">
                        <ReviewIcon width="18px" height="18px" />
                    </Button>
                </template>
            </TransactionTable>
        </div>
    </div>
</template>
