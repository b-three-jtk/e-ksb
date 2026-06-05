<script setup>
import CardInfo from '@/Components/CardInfo.vue';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import EyeIcon from '@/Icons/EyeIcon.vue';
import TransactionTable from '@/Components/Dashboard/TransactionTable.vue';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    data: Object,
    can: Object,
    role: Object,
    selectedSavingTransactionFilter: String,
    selectedNearestDueFilter: String,
});

const tableNearestDueColumns = computed(() => {
    const cols = [
        { key: 'product', label: 'Jenis' },
        { key: 'due_date', label: 'Jatuh Tempo' },
        { key: 'user_name', label: 'Anggota' },
        { key: 'nominal', label: 'Nominal' },
        { key: 'status', label: 'Status' },
    ];
    return cols;
});

const tableSavingTransactionColumns = computed(() => {
    const cols = [
        { key: 'transaction_code', label: 'No. Transaksi' },
        { key: 'user_name', label: 'Anggota' },
        { key: 'amount', label: 'Nominal' },
        { key: 'product', label: 'Jenis' },
    ];
    cols.push({ key: 'action', label: 'Aksi' });
    return cols;
});

</script>

<template>
    <!-- INFO -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        <CardInfo title="Total Simpanan Masuk" :content="parseCurrencyAmount(data.total_simpanan_masuk)" :percentage="data.total_simpanan_masuk_percentage" />
        <CardInfo title="Total Simpanan Keluar" :content="parseCurrencyAmount(data.total_simpanan_keluar)" :percentage="data.total_simpanan_keluar_percentage" />
        <CardInfo title="Total Angsuran Belum Lunas" :content="parseCurrencyAmount(data.total_angsuran_belum_lunas)" />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card-layout">
            <div class="flex justify-between items-center">
                <h1 class="card-title">Jatuh Tempo Terdekat</h1>
                <div class="relative z-20 bg-transparent">
                    <select :value="selectedNearestDueFilter"
                        @input="$emit('update:selectedNearestDueFilter', $event.target.value)"
                        class="h-11 w-full font-body appearance-none rounded-lg border px-4 bg-white pr-11 text-sm shadow-theme-xs focus:outline-hidden dark:bg-dark-900 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="all">Semua</option>
                        <option value="simpanan">Simpanan</option>
                        <option value="pembiayaan">Pembiayaan</option>
                    </select>
                    <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400"
                        viewBox="0 0 20 20" fill="none">
                        <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
            </div>
            <TransactionTable :columns="tableNearestDueColumns" :rows="data.nearest_due">
                <template #nominal="{ item }">
                    {{ parseCurrencyAmount(item.nominal) }}
                </template>
            </TransactionTable>
        </div>
        <div class="card-layout">
            <div class="flex justify-between items-center">
                <h1 class="card-title">Transaksi Simpanan Terbaru</h1>
                <div class="relative z-20 bg-transparent">
                    <select :value="selectedSavingTransactionFilter"
                        @input="$emit('update:selectedSavingTransactionFilter', $event.target.value)"
                        class="h-11 w-full font-body appearance-none rounded-lg border px-4 bg-white pr-11 text-sm shadow-theme-xs focus:outline-hidden dark:bg-dark-900 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="all">Semua</option>
                        <option value="Simpanan Pokok">Simpanan Pokok</option>
                        <option value="Simpanan Wajib">Simpanan Wajib</option>
                        <option value="Tabungan Anggota">Tabungan Anggota</option>
                        <option value="Tabungan Berjangka">Tabungan Berjangka</option>
                        <option value="Tabungan Ibadah">Tabungan Ibadah</option>
                    </select>
                    <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400"
                        viewBox="0 0 20 20" fill="none">
                        <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
            </div>
            <TransactionTable :columns="tableSavingTransactionColumns" :rows="data.recent_saving_transactions">
                <template #amount="{ item }">
                        {{ parseCurrencyAmount(item.amount) }}
                </template>
                <template #action="{ item }">
                    <Link :href="`/admin/savings/show/${item.id}`">
                        <EyeIcon
                            class="w-5 h-5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" />
                    </Link>
                </template>
            </TransactionTable>
        </div>
    </div>
</template>
