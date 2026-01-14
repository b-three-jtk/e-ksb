<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import BaseLayout from '../../../Layouts/Base.vue'
import FieldRow from '../../../Components/Form/FieldRow.vue'
import BaseFunctionality from '../../../Components/Table/BaseFunctionality.vue'
import Pagination from '../../../Components/Table/Pagination.vue'
import Ringkasan from './Ringkasan.vue'
import Table from './Table.vue'

defineOptions({
    layout: BaseLayout,
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
    savings?: {
        simpanan_pokok: number
        simpanan_wajib: number
        simpanan_sukarela: number
    }
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
        simpanan_pokok: 0,
        simpanan_wajib: 0,
        simpanan_sukarela: 0,
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
    { key: 'debit', label: 'Debit' },
    { key: 'kredit', label: 'Kredit' },
    { key: 'saldo', label: 'Saldo' },
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
        label: 'Semua Bulan',
        options: months.value,
        optionLabel: 'label',
        optionValue: 'key',
    }
]

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

let timeout: ReturnType<typeof setTimeout> | null = null

const handleSearch = (value: string) => {
    filters.value.search = value
    if (timeout) clearTimeout(timeout)
    timeout = setTimeout(applyFilters, 500)
}

onBeforeUnmount(() => {
    if (timeout) clearTimeout(timeout)
})

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
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 pt-24 pb-12 max-w-8xl px-4 sm:px-6 lg:px-8">
        <div class="space-y-6 p-6">
            <!-- Anggota Info -->
            <div v-if="memberInfo" class="mb-8 w-full">
                <h1 class="text-3xl font-bold font-head text-blue-900 dark:text-blue-600 mb-8">
                    Personal Ledger
                </h1>

                <div class="space-y-2 font-head text-black dark:text-white">
                    <FieldRow label="Nama Anggota" :value="memberInfo.nama" />
                    <FieldRow label="No Anggota" :value="memberInfo.no_anggota" />
                    <FieldRow label="Status" :value="memberInfo.status" />
                    <FieldRow label="Tanggal Bergabung" :value="memberInfo.tanggal_bergabung" />
                </div>
            </div>

            <!-- Ringkasan Simpanan -->
            <div class="w-full mb-8">
                <h2 class="text-xl font-bold font-head text-gray-800 dark:text-white mb-4">
                    Ringkasan
                </h2>
                <Ringkasan :savings="savings" />
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden w-full">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <BaseFunctionality
                                :search="filters.search"
                                :per-page="filters.per_page"
                                :filters="{ month: filters.month }"
                                :selects="selectFilters"
                                :search-tooltip="['Produk', 'Jenis', 'Metode']"
                                :show-border="false"
                                @update:search="handleSearch"
                                @update:perPage="val => { filters.per_page = val; applyFilters() }"
                                @update:filters="val => { filters.month = val.month; applyFilters() }"
                            />
                        </div>
                        
                        <div class="px-4">
                            <button
                                @click="handleExport"
                                class="inline-flex items-center gap-2 px-4 py-2 
                                    bg-blue-800 hover:bg-blue-900 
                                    text-white text-sm font-medium rounded-lg
                                    transition-colors duration-200
                                    focus:outline-none focus:ring-2 focus:ring-blue-800 focus:ring-offset-2"
                            >
                                <span class="icon-[mdi--file-download]" style="color: white;"></span>
                                Export
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="px-8 py-6">
                    <Table
                        :transactions="transactions.data"
                        :columns="columns"
                    />

                    <!-- Pagination -->
                    <Pagination
                        :links="transactions.links"
                        :total="transactions.total"
                    />
                </div>    
            </div>
        </div>
    </div>
</template>
