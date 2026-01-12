<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, reactive, watch } from 'vue'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'
import CardInfo from '../../../Components/CardInfo.vue'
import BaseFunctionality from '../../../Components/Table/BaseFunctionality.vue'
import BaseTable from '../../../Components/Table/BaseTable.vue'
import Pagination from '../../../Components/Table/Pagination.vue'

const tabs = [
    { key: 'semua', label: 'Semua' },
    { key: 'permohonan', label: 'Permohonan Penarikan/Penyetoran' },
    { key: 'pokok', label: 'Simpanan Pokok' },
    { key: 'wajib', label: 'Simpanan Wajib' },
    { key: 'sukarela', label: 'Simpanan Sukarela' },
]

const props = defineProps({
    transactions: Object,
    summary: Array,
    filters: Object,
})

const columns = [
    { key: 'no', label: 'No' },
    { key: 'no_transaksi', label: 'No Transaksi' },
    { key: 'tanggal', label: 'Tanggal', sortable: true },
    { key: 'anggota', label: 'Anggota' },
    { key: 'nominal', label: 'Nominal', align: 'right' },
    { key: 'produk', label: 'Produk Simpanan' },
    { key: 'jenis', label: 'Jenis Transaksi' },
    { key: 'aksi', label: 'Aksi', align: 'center' },
]

const page = usePage()

const filters = reactive({
    search: page.props.filters?.search ?? '',
    per_page: page.props.filters.per_page ?? 10,
    tab: page.props.filters.tab ?? 'semua',
    sort_by: page.props.filters?.sort_by ?? 'transaction_date',
    sort_dir: page.props.filters?.sort_dir ?? 'desc'
})

const toggleSort = (column) => {
    if(filters.sort_by === column) {
        filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
    } else {
        filters.sort_by = column
        filters.sort_dir = 'asc'
    }
    applyFilters()
}

const transactions = computed(() => page.props.transactions ?? {
    data: [],
    current_page: 1,
    per_page: 10,
    total: 0,
    links: [],
})

const summary = computed(() => page.props.summary ?? [])

const tableTitle = computed(() => {
    switch (filters.tab) {
        case 'permohonan':
            return 'Data Permohonan Penarikan/Penyetoran Simpanan'
        case 'pokok':
            return 'Data Simpanan Pokok'
        case 'wajib':
            return 'Data Simpanan Wajib'
        case 'sukarela':
            return 'Data Simpanan Sukarela'
        default:
            return 'Data Simpanan'
    }
})

const getProductColor = (produk) => {
    if (!produk) {
        return {
            bg: 'bg-gray-100 dark:bg-slate-700',
            text: 'text-gray-700 dark:text-slate-300',
        }
    }

    const key = produk.toLowerCase().trim()

    if (key.includes('pokok')) {
        return {
            bg: 'bg-blue-100 dark:bg-blue-900/40',
            text: 'text-blue-700 dark:text-blue-100',
        }
    }

    if (key.includes('wajib')) {
        return {
            bg: 'bg-green-100 dark:bg-green-900/40',
            text: 'text-green-700 dark:text-green-100',
        }
    }

    if (key.includes('sukarela')) {
        return {
            bg: 'bg-purple-100 dark:bg-purple-900/40',
            text: 'text-purple-700 dark:text-purple-100',
        }
    }

    return {
        bg: 'bg-gray-100 dark:bg-slate-700',
        text: 'text-gray-700 dark:text-slate-100',
    }
}

const applyFilters = () => {
    router.get(
        '/admin/savings',
        {
            search: filters.search || undefined,
            per_page: filters.per_page,
            tab: filters.tab,
            sort_by: filters.sort_by,
            sort_dir: filters.sort_dir,
            page: 1,
        },
        {
            preserveScroll: true,
            replace: true,
        }
    )
}

let timeout
watch(() => filters.search, () => {
    clearTimeout(timeout)
    timeout = setTimeout(applyFilters, 500)
})

watch(() => filters.per_page, applyFilters)
watch(() => filters.tab, applyFilters)
</script>

<template>
    <AdminLayout title="Daftar Simpanan">
        <!-- Title + Breadcrumb -->
        <PageBreadcrumb page-title="Pengelolaan Simpanan" />

        <!-- Ringkasan -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 mb-10 relative">
            <div class="flex justify-between items-center mb-6">
                <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Ringkasan</h2>

                <!-- Tombol Tambah -->
                <Link
                    href="#"
                    class="font-head inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"
                >
                    <Icon icon="mdi:plus" />
                    Tambah Transaksi
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <CardInfo
                    v-for="item in summary"
                    :key="item.title"
                    :title="item.title"
                    :content="item.value"
                    :percentage="item.percentage"
                />
            </div>
        </div>

        <div class="relative mt-15">
            <!-- Tabs -->
            <div class="flex gap-2 absolute -top-8 left-6 z-10">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="filters.tab = tab.key"
                    class="font-head px-4 py-2 rounded-lg text-sm border transition border-gray-200 dark:border-slate-700"
                     :class="filters.tab === tab.key
                        ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 font-medium shadow'
                        : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-600'"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-hidden relative z-10">
                <!-- Header Table -->
                <div class="px-6 pb-4 p-7 border-b">
                    <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1.5">
                        {{ tableTitle }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-slate-400">
                        Lacak transaksi simpanan koperasi di sini
                    </p>
                </div>

                <!-- Functionality -->
                <BaseFunctionality
                    :per-page="filters.per_page"
                    :search="filters.search"
                    @update:per-page="val => filters.per_page = val"
                    @update:search="val => filters.search = val"
                />

                <!-- Table -->
                <BaseTable
                    :columns="columns"
                    :data="transactions.data"
                    :pagination="transactions"
                    :sort-by="filters.sort_by"
                    :sort-dir="filters.sort_dir"
                    @sort="toggleSort"
                >
                    <template #cell-no="{ index }">
                        {{ (transactions.current_page - 1) * transactions.per_page + index + 1 }}
                    </template>

                    <template #cell-nominal="{ row }">
                        <span
                            :class="row.nominal < 0 ? 'text-red-500' : 'text-green-500'"
                            class="font-medium"
                        >
                            Rp {{ Math.abs(row.nominal).toLocaleString('id-ID') }}
                        </span>
                    </template>

                    <template #cell-produk="{ row }">
                        <span
                            class="px-3 py-1 text-xs rounded-full font-medium"
                            :class="[
                                getProductColor(row.produk).bg,
                                getProductColor(row.produk).text
                            ]"
                        >
                            {{ row.produk }}
                        </span>
                    </template>


                    <template #cell-aksi="{ row }">
                        <div class="flex justify-center gap-3">
                            <Link
                                v-if="filters.tab === 'permohonan'"
                                :href="`/admin/savings/${row.id}`"
                                class="inline-flex items-center gap-2
                                    bg-blue-light-600 hover:bg-blue-light-900 text-white px-4 py-2 rounded-lg"
                            >
                                <Icon icon="tabler:checklist" class="w-4 h-4" />
                                Tinjau
                            </Link>

                            <Link
                                v-else
                                :href="`/admin/savings/${row.id}`"
                                class="text-gray-500 hover:text-blue-600 dark:text-gray-100 dark:hover:text-blue-400"
                            >
                                <Icon icon="mdi:eye-outline" class="w-5 h-5"/>
                            </Link>
                        </div>
                    </template>
                </BaseTable>

                <!-- Pagination -->
                <Pagination
                    :links="transactions.links"
                    :total="transactions.total"
                />
            </div>
        </div>
    </AdminLayout>
</template>
