<script setup>
import CardInfo from '@/Components/CardInfo.vue';
import VerticalBarChart from '@/Components/Dashboard/VerticalBarChart.vue';
import PieChart from '@/Components/Dashboard/PieChart.vue';
import TransactionTable from '@/Components/Dashboard/TransactionTable.vue';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import BarChart from '@/Components/Dashboard/Barchart.vue';
import { computed } from 'vue';
import EyeIcon from '@/Icons/EyeIcon.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    data: Object,
    can: Object,
    role: String,
    selectedFilter: String,
    selectedTransactionFilter: String,
    selectedSavingsFilter: String,
});

const tableColumns = computed(() => {
    const cols = [
        { key: 'transaction_code', label: 'No. Transaksi' },
        { key: 'user_name', label: 'Anggota' },
        { key: 'product', label: 'Produk' },
        { key: 'created_at', label: 'Tanggal' },
    ];

    if (props.role === 'Dewan Pengawas Syariah') {
        cols.splice(3, 0, { key: 'akad', label: 'Akad' });
    }

    cols.push({ key: 'action', label: 'Aksi' });
    return cols;
});

const emit = defineEmits(['update:selectedTransactionFilter', 'update:selectedSavingsFilter']);
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-7 gap-3.5">
        <div class="grid grid-cols-2 col-span-7 gap-3.5">
            <div class="grid grid-cols-2 col-span-1 flex-col gap-3.5">
                <CardInfo title="Rasio Kas" :content="data.rasio_kas" />
                <CardInfo v-if="can['view_kas']" title="Total Kas" :content="parseCurrencyAmount(data.total_kas)"
                    :percentage="data.total_kas_percentage" :filter="selectedFilter" />
                <div class="card-layout col-span-2">
                    <h1 class="card-title">Grafik Pendapatan Margin</h1>
                    <VerticalBarChart class="col-span-3 pt-10" title="Grafik Pendapatan Margin" :data="data.revenues"
                        :filter="selectedFilter" />
                </div>
            </div>
            <div class="card-layout col-span-1">
                <div class="flex justify-between">
                    <h1 class="card-title">Transaksi Terbaru</h1>
                    <div class="relative z-20 bg-transparent">
                        <select :value="selectedTransactionFilter"
                            @input="$emit('update:selectedTransactionFilter', $event.target.value)"
                            class="h-11 w-full font-body appearance-none rounded-lg border px-4 bg-white pr-11 text-sm shadow-theme-xs focus:outline-hidden dark:bg-dark-900 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="all">Semua</option>
                            <option value="simpanan">Simpanan</option>
                            <option value="pembiayaan">Pembiayaan</option>
                        </select>
                        <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400"
                            viewBox="0 0 20 20" fill="none">
                            <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>
                <TransactionTable :columns="tableColumns" :rows="data.recent_transactions">
                    <template #action="{ item }">
                        <Link
                            :href="item.product !== 'Pembiayaan' ? `/admin/savings/show/${item.id}` : `/admin/financings/show/${item.id}`">
                            <EyeIcon
                                class="w-5 h-5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" />
                        </Link>
                    </template>
                </TransactionTable>
            </div>
        </div>
        <div class="col-span-4 grid grid-cols-2 gap-3.5">
            <CardInfo title="Rasio Non-Performing Financing (NPF)" :content="data.rasio_npf" />
            <CardInfo title="Rasio Financing-to-Deposit (FDR)" :content="data.rasio_fdr" />
            <div class="card-layout col-span-2">
                <div class="border-b border-stroke pb-4">
                    <div class="flex justify-between w-full items-center">
                        <h1 class="card-title">Peta Simpanan</h1>
                        <div class="relative z-20 bg-transparent">
                            <select :value="selectedSavingsFilter"
                                @input="$emit('update:selectedSavingsFilter', $event.target.value)"
                                class="h-11 w-full font-body appearance-none px-4 bg-white pr-11 text-sm focus:outline-hidden dark:bg-dark-900 text-gray-800 dark:bg-gray-900 dark:text-white/90">
                                <option value="jenis">Berdasarkan Jenisnya</option>
                                <option value="akad">Berdasarkan Akadnya</option>
                            </select>
                            <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400"
                                viewBox="0 0 20 20" fill="none">
                                <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>
                    <h2 class="text-2xl font-semibold text-primary pt-5">{{
                        parseCurrencyAmount(data.total_simpanan_masuk) }}
                    </h2>
                    <p class="text-gray-500 font-body text-sm pt-2">Total Simpanan Masuk</p>
                </div>
                <BarChart :data="data.peta_simpanan" />
            </div>
        </div>
        <div class="card-layout col-span-3">
            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                <h1 class="card-title">Peta Pembiayaan</h1>
                <h2 class="text-2xl font-semibold text-primary mt-2">{{
                    parseCurrencyAmount(data.total_pembiayaan_tersalurkan) }}</h2>
                <p class="text-gray-500 font-body text-sm">Total Pembiayaan Tersalurkan</p>
            </div>
            <div class="flex items-center justify-center mt-15">
                <PieChart :data="data.peta_pembiayaan" class="flex items-center justify-center mt-8" />
            </div>
        </div>
    </div>
</template>
