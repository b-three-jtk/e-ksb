<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import BaseLayout from '../../../Layouts/Base.vue'
import BaseFunctionality from '../../../Components/Table/BaseFunctionality.vue'
import Pagination from '../../../Components/Table/Pagination.vue'
import Ringkasan from './Ringkasan.vue'
import Table from './Table.vue'
import { formatCurrency } from '../../../utils/currency'

defineOptions({
    layout: (h: any, page: any) => h(BaseLayout, { title: 'Buku Besar Personal' }, () => page),
})

const props = withDefaults(defineProps<{
    transactions?: {
        data: any[]
        current_page: number
        per_page: number
        total: number
        last_page: number
        links: any[]
    }
    memberInfo?: {
        nama: string
        no_anggota: string
        status: string
        tanggal_bergabung: string
    }
    savings?: Record<string, number>
    savingMeta?: Record<string, {
        maturity_date?: string | null
        minimum_target?: number | null
    }>
    filters?: {
        search: string
        month: string
        per_page: number
    }
}>(), {
    transactions: () => ({
        data: [],
        current_page: 1,
        per_page: 10,
        total: 0,
        last_page: 1,
        links: [],
    }),
    memberInfo: () => ({
        nama: '',
        no_anggota: '',
        status: '',
        tanggal_bergabung: '',
    }),
    savings: () => ({
        total_saldo: 0,
        simpanan_pokok: 0,
        simpanan_wajib: 0,
        tabungan_anggota: 0,
        tabungan_berjangka: 0,
        tabungan_ibadah: 0,
    }),
    savingMeta: () => ({
        tabungan_berjangka: {
            maturity_date: null,
        },
        tabungan_ibadah: {
            minimum_target: null,
        },
    }),
    filters: () => ({
        search: '',
        month: '',
        per_page: 10,
    }),
})

const columns = [
    { key: 'tanggal', label: 'Tanggal' },
    { key: 'produk', label: 'Produk' },
    { key: 'jenis', label: 'Jenis' },
    { key: 'metode', label: 'Metode' },
    { key: 'petugas', label: 'Petugas' },
    { key: 'nominal', label: 'Nominal' },
    { key: 'aksi', label: 'Aksi' },
]

const filters = ref({
    search: props.filters?.search ?? '',
    month: props.filters?.month ?? '',
    per_page: props.filters?.per_page ?? 10,
})

const months = computed(() => {
    const list: { key: string; label: string }[] = []
    const now = new Date()

    for (let i = 0; i < 12; i++) {
        const date = new Date(now.getFullYear(), now.getMonth() - i, 1)
        const year = date.getFullYear()
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const key = `${year}-${month}`
        const label = date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
        list.push({ key, label })
    }

    return list
})

const selectFilters = [
    {
        key: 'month',
        label: 'Semua',
        options: months.value,
        optionLabel: 'label',
        optionValue: 'key',
    }
]

const totalSavings = computed(() => {
    return Math.max(0, Number(props.savings?.total_saldo ?? 0))
})

const savingTypeCount = computed(() => {
    return Object.entries(props.savings ?? {})
        .filter(([key]) => key !== 'total_saldo')
        .filter(([, amount]) => Number(amount || 0) > 0)
        .length
})

const transactionCount = computed(() => props.transactions?.total ?? 0)

const memberStatusLabel = computed(() => {
    if (!props.memberInfo?.status) return '-'
    const lower = String(props.memberInfo.status).toLowerCase()
    return lower.charAt(0).toUpperCase() + lower.slice(1)
})

const applyFilters = () => {
    router.get('/user/ledger', {
        search: filters.value.search || undefined,
        month: filters.value.month || undefined,
        per_page: filters.value.per_page,
    }, {
        preserveScroll: true,
        replace: true,
    })
}

const handleSearch = (value: string) => {
    filters.value.search = value
}

const handleExport = () => {
    const params = new URLSearchParams()

    if (filters.value.search) {
        params.append('search', filters.value.search)
    }

    if (filters.value.month) {
        params.append('month', filters.value.month)
    }

    window.location.href = `/user/ledger/export?${params.toString()}`
}
</script>
<template>
    <div class="min-h-screen bg-[#f5f7f6] dark:bg-gray-900 pt-24 pb-12 max-w-8xl px-4 sm:px-6 lg:px-8">
        <div class="space-y-8 p-2 sm:p-4 lg:p-6">
            <div v-if="memberInfo" class="w-full">
                <h1 class="text-3xl font-bold font-head text-emerald-800 dark:text-emerald-500 mb-4">
                    Buku Besar Personal
                </h1>

                <div class="rounded-xl bg-accent text-emerald-900 shadow-sm overflow-hidden">
                    <div class="grid grid-cols-1 md:grid-cols-4">
                        <div class="px-4 py-4">
                            <p class="text-md font-body text-emerald-700">Halo,</p>
                            <p class="text-2xl font-head font-bold leading-tight mt-1">{{ memberInfo.nama }}</p>
                            <p class="text-md font-body text-emerald-700 mt-1">
                                {{ memberInfo.no_anggota }} · {{ memberStatusLabel }} sejak {{ memberInfo.tanggal_bergabung }}
                            </p>
                        </div>

                        <div class="px-4 py-4 md:border-l border-emerald-700/20 flex flex-col justify-center text-center">
                            <p class="text-2xl font-head font-bold">{{ savingTypeCount }}</p>
                            <p class="text-md font-body text-emerald-700 mt-1">Total Jenis Simpanan</p>
                        </div>

                        <div class="px-4 py-4 md:border-l border-emerald-700/30 flex flex-col justify-center text-center">
                            <p class="text-2xl font-head font-bold">{{ transactionCount }}</p>
                            <p class="text-md font-body text-emerald-700 mt-1">Transaksi</p>
                        </div>

                        <div class="px-4 py-4 md:border-l border-emerald-700/30 flex flex-col justify-center text-center">
                            <p class="text-2xl font-head font-bold">{{ formatCurrency(totalSavings) }}</p>
                            <p class="text-md font-body text-emerald-700 mt-1">Total Saldo</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full">
                <h2 class="text-2xl font-bold font-head text-gray-900 dark:text-white mb-4">
                    Ringkasan Saldo
                </h2>
                <Ringkasan :savings="savings" :saving-meta="savingMeta" />
            </div>

            <section class="w-full">
                <h2 class="text-2xl font-bold font-head text-gray-900 dark:text-white mb-4">
                    Riwayat Transaksi
                </h2>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden w-full">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <BaseFunctionality
                            :search="filters.search"
                            :per-page="filters.per_page"
                            :filters="{ month: filters.month }"
                            :selects="selectFilters"
                            :search-tooltip="['Produk', 'Jenis', 'Metode']"
                            :show-search-button="true"
                            :show-border="false"
                            @update:search="handleSearch"
                            @submit:search="applyFilters"
                            @update:perPage="val => { filters.per_page = val; applyFilters() }"
                            @update:filters="val => { filters.month = val.month; applyFilters() }"
                        >
                            <template #actions>
                                <button
                                    @click="handleExport"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2"
                                >
                                    <span class="icon-[mdi--file-download]" style="color: white;"></span>
                                    Export PDF
                                </button>
                            </template>
                        </BaseFunctionality>
                    </div>

                    <div class="px-3 sm:px-5 lg:px-6 py-4 sm:py-5">
                        <Table
                            :transactions="transactions.data"
                            :columns="columns"
                            :member-info="memberInfo"
                        />

                        <Pagination
                            :links="transactions.links"
                            :total="transactions.total"
                        />
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
