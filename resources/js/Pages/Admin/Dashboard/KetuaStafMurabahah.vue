<script setup>
import CardInfo from '@/Components/CardInfo.vue';
import VerticalBarChart from '@/Components/Dashboard/VerticalBarChart.vue';
import parseCurrencyjumlah from '@/Composables/moneyParser.js';
import EyeIcon from '@/Icons/EyeIcon.vue';
import TransactionTable from '@/Components/Dashboard/TransactionTable.vue';
import { computed } from 'vue';
import Button from '@/Components/Form/Button.vue';
import { Icon } from '@iconify/vue';
import ReviewIcon from '@/Icons/ReviewIcon.vue';
import PieChart from '@/Components/Dashboard/PieChart.vue';
import SkeletonStatCard from '@/Components/Dashboard/Loading/SkeletonStatCard.vue';
import SkeletonChartCard from '@/Components/Dashboard/Loading/SkeletonChartCard.vue';
import SkeletonMapCard from '@/Components/Dashboard/Loading/SkeletonMapCard.vue';
import SkeletonTableCard from '@/Components/Dashboard/Loading/SkeletonTableCard.vue';
import useFinancingStatus, { getStatusLabel } from '@/Composables/useFinancingStatus'
import { Link } from '@inertiajs/vue3';
import dateParser from '@/Composables/dateParser.js'

defineProps({
    stats: Object,
    pertumbuhan_pendapatan: Object,
    peta_pembiayaan: Object,
    pembayaran_terlambat: Object,
    permohonan_murabahah: Object,
    can: Object,
    role: Object,
    selectedFilter: String,
    selectedTransactionFilter: String,
});

const kolomTabelPembayaranTerlambat = computed(() => {
    const cols = [
        { key: 'no_transaksi', label: 'No. Transaksi' },
        { key: 'anggota', label: 'Anggota' },
        { key: 'hari_terlambat', label: 'Hari Terlambat' },
        { key: 'jumlah', label: 'Jumlah' },
    ];
    cols.push({ key: 'action', label: 'Aksi' });
    return cols;
});

const kolomTabelPermohonanMurabahah = computed(() => {
    const cols = [
        { key: 'no_transaksi', label: 'No. Transaksi' },
        { key: 'anggota', label: 'Anggota' },
        { key: 'status', label: 'Status' },
    ];
    cols.push({ key: 'action', label: 'Aksi' });
    return cols;
});

</script>

<template>
    <!-- INFO -->
    <SkeletonStatCard v-if="!stats" :count="3" />
    <div v-else v-if="role === 'Ketua Murabahah'" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <CardInfo title="Total Modal Belum Diputar" :content="parseCurrencyjumlah(stats.modal_sudah_dialokasi)"
            :percentage="stats.modal_sudah_dialokasi_persen" :filter="selectedFilter" />
        <CardInfo title="Jumlah Piutang Murabahah Aktif" :content="stats.total_pembiayaan_aktif"
            :percentage="stats.total_pembiayaan_aktif" :filter="selectedFilter" />
        <CardInfo title="Total Permohonan Pembiayaan" :content="stats.total_permohonan_pembiayaan"
            :percentage="stats.total_permohonan_pembiayaan_persen" :filter="selectedFilter" />
    </div>
    <div class="flex flex-col gap-4">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
            <SkeletonChartCard v-if="!pertumbuhan_pendapatan" class="col-span-3" :bars="12" :legend="2" />
            <div v-else class="card-layout lg:col-span-3">
                <h1 class="card-title">Grafik Pendapatan Margin</h1>
                <VerticalBarChart class="col-span-3 pt-10" title="Grafik Pendapatan Margin"
                    :data="pertumbuhan_pendapatan" :filter="selectedFilter" />
            </div>
            <SkeletonMapCard v-if="!peta_pembiayaan" class="col-span-2" :legend-items="4" />
            <div v-else class="card-layout lg:col-span-2">
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                    <h1 class="card-title">Peta Pembiayaan</h1>
                    <h2 class="text-2xl font-semibold text-primary mt-2">{{
                        parseCurrencyjumlah(stats.total_pembiayaan_tersalurkan) }}</h2>
                    <p class="text-gray-500 font-body text-sm">Jumlah Piutang Murabahah Aktif</p>
                </div>
                <div class="flex items-center justify-center">
                    <PieChart :data="peta_pembiayaan" class="flex items-center justify-center mt-8" />
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <SkeletonTableCard v-if="!pembayaran_terlambat" class="col-span-1"
                :columns="kolomTabelPembayaranTerlambat.length" :rows="5" />
            <div v-else class="card-layout">
                <div class="flex justify-between items-center">
                    <h1 class="card-title">Pembayaran Angsuran Terlambat</h1>
                </div>
                <TransactionTable :columns="kolomTabelPembayaranTerlambat" :rows="pembayaran_terlambat">
                    <template #jumlah="{ item }">
                        dateParser({{ item.jumlah }})
                    </template>
                    <template #action="{ item }">
                        <Link :href="`/admin/financings/show/${item.id}`">
                            <EyeIcon
                                class="w-5 h-5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" />
                        </Link>
                    </template>
                </TransactionTable>
            </div>
            <SkeletonTableCard v-if="!permohonan_murabahah" class="col-span-1"
                :columns="kolomTabelPermohonanMurabahah.length" :rows="5" />
            <div v-else class="card-layout">
                <div class="flex justify-between items-center">
                    <h1 class="card-title">Permohonan Pembiayaan Sedang Berjalan</h1>
                    <Button href="/admin/financings" variant="outline">Selengkapnya</Button>
                </div>
                <TransactionTable :columns="kolomTabelPermohonanMurabahah" :rows="permohonan_murabahah">
                    <template #status="{ item }">
                        <span :class="useFinancingStatus(item.status)">
                            {{ getStatusLabel(item.status) }}
                        </span>
                    </template>
                    <template #action="{ item }">
                        <div class="flex items-center justify-center">
                            <Button
                                v-if="can['edit_murabahah'] && (role === 'Staf Murabahah' && (item.status === 'Disetujui' || item.status === 'Ditolak' || item.status === 'Menunggu Kelengkapan Dokumen' || item.status === 'Disetujui dengan Catatan'))"
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
                        </div>
                    </template>
                </TransactionTable>
            </div>
        </div>
    </div>
</template>
