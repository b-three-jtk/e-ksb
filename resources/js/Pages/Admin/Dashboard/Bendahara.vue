<script setup>
import CardInfo from '@/Components/CardInfo.vue';
import FdrCard from '@/Components/Dashboard/FdrCard.vue';
import VerticalBarChart from '@/Components/Dashboard/VerticalBarChart.vue';
import BaseTable from '@/Components/Table/BaseTable.vue';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import BarChart from '@/Components/Dashboard/Barchart.vue';
import { computed, ref } from 'vue';
import EyeIcon from '@/Icons/EyeIcon.vue';
import { Link } from '@inertiajs/vue3';
import SkeletonChartCard from '@/Components/Dashboard/Loading/SkeletonChartCard.vue';
import SkeletonMapCard from '@/Components/Dashboard/Loading/SkeletonMapCard.vue';
import SkeletonTableCard from '@/Components/Dashboard/Loading/SkeletonTableCard.vue';
import AccountIcon from '@/Icons/AccountIcon.vue';

const props = defineProps({
    stats: Object,
    pertumbuhan_pendapatan: Object,
    peta_simpanan: Object,
    transaksi_terbaru: Object,
    can: Object,
    selectedFilter: String,
    selectedTransactionFilter: String,
    selectedSavingsFilter: String,
});

const kolomTabel = computed(() => {
    const cols = [
        { key: 'anggota', label: 'Anggota', sortable: true },
        { key: 'produk', label: 'Produk' },
        { key: 'jumlah', label: 'Jumlah', sortable: true },
        { key: 'dicatat_oleh', label: 'Dicatat Oleh' },
        { key: 'tanggal', label: 'Tanggal', sortable: true },
    ];
    cols.push({ key: 'action', label: 'Aksi', align: 'center' });
    return cols;
});

const sortBy = ref('tanggal');
const sortDir = ref('desc');

const handleSort = (field) => {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
};

const sortedTransaksi = computed(() => {
    if (!props.transaksi_terbaru) return [];
    return [...props.transaksi_terbaru].sort((a, b) => {
        let valA = a[sortBy.value];
        let valB = b[sortBy.value];

        if (sortBy.value === 'jumlah') {
            valA = Number(valA) || 0;
            valB = Number(valB) || 0;
        } else if (sortBy.value === 'tanggal') {
            valA = new Date(valA).getTime();
            valB = new Date(valB).getTime();
        } else {
            valA = String(valA || '').toLowerCase();
            valB = String(valB || '').toLowerCase();
        }

        if (valA < valB) return sortDir.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortDir.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const getProductColor = (produk) => {
    if (!produk) return { bg: 'bg-gray-100 dark:bg-slate-700', text: 'text-gray-700 dark:text-slate-300' }
    const key = produk.toLowerCase()
    if (key.includes('pokok')) return { bg: 'bg-blue-100 dark:bg-blue-900/40', text: 'text-blue-700 dark:text-blue-200' }
    if (key.includes('wajib')) return { bg: 'bg-green-100 dark:bg-green-900/40', text: 'text-green-700 dark:text-green-200' }
    if (key.includes('anggota')) return { bg: 'bg-amber-100 dark:bg-amber-900/40', text: 'text-amber-700 dark:text-amber-200' }
    if (key.includes('berjangka')) return { bg: 'bg-orange-100 dark:bg-orange-900/40', text: 'text-orange-700 dark:text-orange-200' }
    if (key.includes('ibadah')) return { bg: 'bg-teal-100 dark:bg-teal-900/40', text: 'text-teal-700 dark:text-teal-200' }
    if (key.includes('pembiayaan')) return { bg: 'bg-indigo-100 dark:bg-indigo-900/40', text: 'text-indigo-700 dark:text-indigo-200' }
    return { bg: 'bg-gray-100 dark:bg-slate-700', text: 'text-gray-700 dark:text-slate-100' }
}

const emit = defineEmits(['update:selectedTransactionFilter', 'update:selectedSavingsFilter', 'update:selectedFilter']);
</script>

<template>
    <!-- INFO - BARIS SATU -->
    <div class="grid grid-cols-6 gap-4">
        <div class="col-span-6 grid grid-cols-2 gap-4">
            <CardInfo title="Total Modal Belum Dialokasi (Kas)" :content="parseCurrencyAmount(stats.total_kas)"
                :percentage="stats.total_kas_persen" />
            <CardInfo title="Total Modal Sudah Dialokasi" :content="parseCurrencyAmount(stats.modal_sudah_dialokasi)"
                :percentage="stats.modal_sudah_dialokasi_persen" :filter="selectedFilter" />
        </div>
        <SkeletonMapCard v-if="!peta_simpanan" class="col-span-3" :legend-items="4" />
        <div v-else class="card-layout col-span-3">
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
                        <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
            </div>
            <BarChart :height="360" :data="peta_simpanan" />
        </div>
        <SkeletonChartCard v-if="!pertumbuhan_pendapatan" class="col-span-3" :bars="12" :legend="2" />
        <div v-else class="card-layout col-span-3">
            <div class="flex justify-between">
                <h1 class="card-title">Grafik Pendapatan Margin</h1>
            </div>
            <VerticalBarChart height="340" class="pt-10" title="Grafik Pendapatan Margin" :data="pertumbuhan_pendapatan"
                :filter="selectedFilter" />
        </div>

        <SkeletonTableCard v-if="!transaksi_terbaru" class="col-span-4" :columns="kolomTabel.length" :rows="5" />
        <div v-else class="card-layout col-span-4">
            <div class="flex justify-between">
                <h1 class="card-title">Transaksi Terbaru</h1>
                <div class="relative z-20 bg-transparent">
                    <select :value="selectedTransactionFilter"
                        @input="$emit('update:selectedTransactionFilter', $event.target.value)"
                        class="h-11 w-full font-body appearance-none rounded-lg border px-4 bg-white pr-11 text-sm shadow-theme-xs focus:outline-hidden dark:bg-dark-900 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="all">Semua</option>
                        <option value="simpanan">Simpanan</option>
                        <option value="pembiayaan">Pembiayaan</option>
                        <option value="angsuran">Pembayaran Angsuran</option>
                    </select>
                    <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400"
                        viewBox="0 0 20 20" fill="none">
                        <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <BaseTable :columns="kolomTabel" :data="sortedTransaksi" :sort-by="sortBy" :sort-dir="sortDir"
                    @sort="handleSort">
                    <template #cell-produk="{ row }">
                        <span class="px-3 py-1 text-base rounded-full font-medium whitespace-nowrap"
                            :class="[getProductColor(row.produk).bg, getProductColor(row.produk).text]">
                            {{ row.produk }}
                        </span>
                    </template>
                    <template #cell-jumlah="{ row }">
                        <span class="font-medium">
                            {{ Number(row.jumlah).toLocaleString('id-ID') }}
                        </span>
                    </template>
                    <template #cell-tanggal="{ row }">
                        {{ formatDate(row.tanggal) }}
                    </template>
                    <template #cell-action="{ row }">
                        <Link
                            :href="row.produk !== 'Pembiayaan' && !row.produk.includes('Pembayaran Angsuran') ? `/admin/savings/show/${row.id}` : `/admin/pembiayaan/show/${row.id}`">
                            <EyeIcon
                                class="w-6 h-6 text-gray-400 hover:text-primary dark:text-gray-500 dark:hover:text-blue-400 transition-colors" />
                        </Link>
                    </template>
                </BaseTable>
            </div>
        </div>
        <div class="card-layout col-span-2 bg-light-bg! dark:bg-brand-900/60!">
            <h1 class="card-title text-center">Menu Pintasan</h1>
            <div class="flex flex-col gap-4 mt-6">
                <Link href="/admin/akun"
                    class="bg-white dark:bg-light-bg/20 dark:border-stroke/30 border border-stroke px-4 py-6 flex justify-between items-center rounded-xl hover:bg-gray-50 transition">
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-secondary text-white  rounded-full flex justify-center text-2xl items-center w-11 h-11">
                            <AccountIcon/>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-medium dark:text-gray-200">Pengelolaan Akun</h2>
                            <p class="text-gray-500 text-sm font-body dark:text-gray-300">Pengelolaan Chart of Account koperasi</p>
                        </div>
                    </div>
                    <div class="text-secondary dark:text-gray-300 text-3xl">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
                <Link href="/admin/kas"
                    class="bg-white dark:bg-light-bg/20 dark:border-stroke/30 border border-stroke px-4 py-6 flex justify-between items-center rounded-xl hover:bg-gray-50 transition">
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-secondary text-white  rounded-full flex justify-center text-2xl items-center w-11 h-11">
                            <span class="icon-[solar--calculator-bold]"></span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-medium dark:text-gray-200">Pengelolaan Kas</h2>
                            <p class="text-gray-500 text-sm font-body dark:text-gray-300">Pengelolaan kas koperasi</p>
                        </div>
                    </div>
                    <div class="text-secondary dark:text-gray-300 text-3xl">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
                <Link href="/admin/pembiayaan"
                    class="bg-white dark:bg-light-bg/20 dark:border-stroke/30 border border-stroke px-4 py-6 flex justify-between items-center rounded-xl hover:bg-gray-50 transition">
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-secondary text-white  rounded-full flex justify-center text-2xl items-center w-11 h-11">
                            <span class="icon-[tdesign--money-filled]"></span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-medium dark:text-gray-200">Pembiayaan Murabahah</h2>
                            <p class="text-gray-500 text-sm font-body dark:text-gray-300">Pengelolaan pembiayaan
                                murabahah di sini</p>
                        </div>
                    </div>
                    <div class="text-secondary dark:text-gray-300 text-3xl">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
                <Link href="/admin/pembiayaan"
                    class="bg-white dark:bg-light-bg/20 dark:border-stroke/30 border border-stroke px-4 py-6 flex justify-between items-center rounded-xl hover:bg-gray-50 transition">
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-secondary text-white  rounded-full flex justify-center text-2xl items-center w-11 h-11">
                            <span class="icon-[mdi--hand-coin]"></span>
                        </div>
                        <div class="flex flex-col">
                            <h2 class="text-lg font-medium dark:text-gray-200">Simpanan</h2>
                            <p class="text-gray-500 text-sm font-body dark:text-gray-300">Pengelolaan simpanan di sini
                            </p>
                        </div>
                    </div>
                    <div class="text-secondary dark:text-gray-300 text-3xl">
                        <span class="icon-[material-symbols--chevron-right-rounded]"></span>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>
