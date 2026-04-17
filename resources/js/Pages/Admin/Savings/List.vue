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

const isLoading = ref(false)
const tabGroups = [
    {
        key: 'semua',
        label: 'Semua',
        icon: 'mdi:view-dashboard-outline',
        children: [],
    },
    {
        key: 'simpanan',
        label: 'Simpanan',
        icon: 'mdi:piggy-bank-outline',
        children: [
            { key: 'pokok', label: 'Pokok' },
            { key: 'wajib', label: 'Wajib' },
        ],
    },
    {
        key: 'tabungan',
        label: 'Tabungan',
        icon: 'mdi:bank-outline',
        children: [
            { key: 'tabungan_anggota',   label: 'Anggota' },
            { key: 'tabungan_berjangka', label: 'Berjangka' },
            { key: 'tabungan_ibadah',    label: 'Ibadah' },
            { key: 'tabungan_sosial',    label: 'Sosial' },
        ],
    },
]

const openDropdown = ref(null)

const props = defineProps({
    transactions: Object,
    summary: Array,
    filters: Object,
})

const columns = [
    { key: 'no',           label: 'No' },
    { key: 'no_transaksi', label: 'No Transaksi' },
    { key: 'tanggal',      label: 'Tanggal', sortable: true },
    { key: 'anggota',      label: 'Anggota' },
    { key: 'nominal',      label: 'Nominal', align: 'right' },
    { key: 'produk',       label: 'Produk' },
    { key: 'jenis',        label: 'Jenis Transaksi' },
    { key: 'aksi',         label: 'Aksi', align: 'center' },
]

const page = usePage()

const filters = reactive({
    search:   page.props.filters?.search ?? '',
    per_page: page.props.filters?.per_page ?? 10,
    tab:      page.props.filters?.tab ?? 'semua',
    sort_by:  page.props.filters?.sort_by ?? 'transaction_date',
    sort_dir: page.props.filters?.sort_dir ?? 'desc',
})

const exportQuery = computed(() => {
    const params = {}
    if (filters.search) params.search = filters.search
    if (filters.tab)    params.tab    = filters.tab
    params.sort_by  = filters.sort_by
    params.sort_dir = filters.sort_dir
    return new URLSearchParams(params).toString()
})

const toggleSort = (column) => {
    if (filters.sort_by === column) {
        filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
    } else {
        filters.sort_by  = column
        filters.sort_dir = 'asc'
    }
    applyFilters()
}

const transactions = computed(() => page.props.transactions ?? {
    data: [], current_page: 1, per_page: 10, total: 0, links: [],
})

const summary = computed(() => page.props.summary ?? [])

const tableTitle = computed(() => {
    const map = {
        semua:               'Data Simpanan & Tabungan',
        simpanan:            'Data Semua Simpanan',
        pokok:               'Data Simpanan Pokok',
        wajib:               'Data Simpanan Wajib',
        tabungan:            'Data Semua Tabungan',
        tabungan_anggota:    'Data Tabungan Anggota',
        tabungan_berjangka:  'Data Tabungan Berjangka',
        tabungan_ibadah:     'Data Tabungan Ibadah',
    }
    return map[filters.tab] ?? 'Data Simpanan & Tabungan'
})

const getProductColor = (produk) => {
    if (!produk) return { bg: 'bg-gray-100 dark:bg-slate-700', text: 'text-gray-700 dark:text-slate-300' }
    const key = produk.toLowerCase()
    if (key.includes('pokok'))      return { bg: 'bg-blue-100 dark:bg-blue-900/40',   text: 'text-blue-700 dark:text-blue-200' }
    if (key.includes('wajib'))      return { bg: 'bg-green-100 dark:bg-green-900/40',  text: 'text-green-700 dark:text-green-200' }
    if (key.includes('anggota'))    return { bg: 'bg-amber-100 dark:bg-amber-900/40',  text: 'text-amber-700 dark:text-amber-200' }
    if (key.includes('berjangka'))  return { bg: 'bg-orange-100 dark:bg-orange-900/40',text: 'text-orange-700 dark:text-orange-200' }
    if (key.includes('ibadah'))     return { bg: 'bg-teal-100 dark:bg-teal-900/40',   text: 'text-teal-700 dark:text-teal-200' }
    return { bg: 'bg-gray-100 dark:bg-slate-700', text: 'text-gray-700 dark:text-slate-100' }
}

const applyFilters = () => {
    isLoading.value = true
    router.get(
        '/admin/savings/list',
        {
            search:   filters.search || undefined,
            per_page: filters.per_page,
            tab:      filters.tab,
            sort_by:  filters.sort_by,
            sort_dir: filters.sort_dir,
            page:     1,
        },
        {
            preserveScroll: true,
            replace: true,
            onFinish: () => { isLoading.value = false },
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

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengelolaan Simpanan' },
]

const openActionDropdown = ref(false)

const handleAction = (type) => {
    openActionDropdown.value = false

    if (type === 'setor') {
        router.visit('/admin/simpanan/penyetoran')
    } else {
        router.visit('/admin/savings/penarikan')
    }
}
</script>

<template>
    <AdminLayout title="Daftar Simpanan & Tabungan">
        <PageBreadcrumb page-title="Pengelolaan Simpanan & Tabungan" :items="breadcrumbItems" />

        <!-- Ringkasan -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Ringkasan
                </h2>

                <!-- Button + Dropdown -->
                <div class="relative">
                    <button
                        @click="openActionDropdown = !openActionDropdown"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-secondary rounded-lg hover:bg-primary transition"
                    >
                        <Icon icon="mdi:plus" class="w-4 h-4" />
                        Tambah Transaksi
                    </button>

                    <div
                        v-if="openActionDropdown"
                        class="absolute right-0 mt-2 w-44 bg-white dark:bg-slate-800 border rounded-lg shadow z-20"
                    >
                        <button
                            @click="handleAction('setor')"
                            class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-slate-700"
                        >
                            <Icon icon="mdi:arrow-up-circle-outline" class="w-4 h-4 text-green-500" />
                            Penyetoran
                        </button>

                        <button
                            @click="handleAction('tarik')"
                            class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-slate-700"
                        >
                            <Icon icon="mdi:arrow-down-circle-outline" class="w-4 h-4 text-red-500" />
                            Penarikan
                        </button>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <CardInfo
                    v-for="item in summary"
                    :key="item.title"
                    :title="item.title"
                    :content="item.value"
                    :percentage="item.percentage"
                />
            </div>
        </div>

        <!-- Filter Tab Area -->
        <div class="bg-white dark:bg-slate-800 rounded-xl px-5 py-4 mb-4 flex flex-wrap items-center gap-3">

            <!-- Semua -->
            <button
                @click="filters.tab = 'semua'"
                class="px-4 py-2 rounded-lg text-sm font-medium border"
                :class="filters.tab === 'semua'
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-gray-50 dark:bg-slate-700 dark:text-white'"
            >
                Semua
            </button>

            <!-- Dropdown Jenis Simpanan -->
            <div class="relative">
                <button
                    @click="openDropdown = openDropdown === 'simpanan' ? null : 'simpanan'"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border bg-gray-50 dark:bg-slate-700 dark:text-white"
                >
                    Simpanan
                    <Icon
                        icon="mdi:chevron-down"
                        class="w-4 h-4 transition-transform"
                        :class="{ 'rotate-180': openDropdown === 'simpanan' }"
                    />
                </button>

                <div
                    v-if="openDropdown === 'simpanan'"
                    class="absolute mt-2 w-40 bg-white dark:bg-slate-800 border rounded-lg shadow z-10"
                >
                    <button
                        v-for="child in tabGroups.find(g => g.key === 'simpanan').children"
                        :key="child.key"
                        @click="filters.tab = child.key; openDropdown = null"
                        class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-slate-700 dark:text-white"
                    >
                        {{ child.label }}
                    </button>
                </div>
            </div>

            <!-- Dropdown Jenis Tabungan -->
            <div class="relative">
                <button
                    @click="openDropdown = openDropdown === 'tabungan' ? null : 'tabungan'"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border bg-gray-50 dark:bg-slate-700 dark:text-white"
                >
                    Tabungan
                    <Icon
                        icon="mdi:chevron-down"
                        class="w-4 h-4 transition-transform"
                        :class="{ 'rotate-180': openDropdown === 'tabungan' }"
                    />
                </button>

                <div
                    v-if="openDropdown === 'tabungan'"
                    class="absolute mt-2 w-48 bg-white dark:bg-slate-800 border rounded-lg shadow z-10"
                >
                    <button
                        v-for="child in tabGroups.find(g => g.key === 'tabungan').children"
                        :key="child.key"
                        @click="filters.tab = child.key; openDropdown = null"
                        class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-slate-700 dark:text-white"
                    >
                        {{ child.label }}
                    </button>
                </div>
            </div>

        </div>

        <!-- Table Card -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-hidden">
            <!-- Header Table -->
            <div class="px-6 pt-6 pb-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100 mb-0.5">
                    {{ tableTitle }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-slate-400">
                    Lacak transaksi simpanan dan tabungan koperasi di sini
                </p>
            </div>

            <!-- Functionality -->
            <BaseFunctionality
                :per-page="filters.per_page"
                :search="filters.search"
                @update:per-page="val => filters.per_page = val"
                @update:search="val => filters.search = val"
            >
                <template #actions>
                    <a
                        :href="`/admin/savings/export/csv?${exportQuery}`"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-green-600 text-white rounded-lg"
                    >
                        <Icon icon="mdi:file-delimited-outline" class="w-4 h-4" />
                        Export CSV
                    </a>
                    <a
                        :href="`/admin/savings/export/pdf?${exportQuery}`"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-red-600 text-white rounded-lg"
                    >
                        <Icon icon="mdi:file-pdf-box" class="w-4 h-4" />
                        Export PDF
                    </a>
                </template>
            </BaseFunctionality>

            <!-- Table -->
            <BaseTable
                :columns="columns"
                :data="transactions.data"
                :is-loading="isLoading"
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
                        {{ row.nominal < 0 ? '-' : '' }}Rp {{ Math.abs(row.nominal).toLocaleString('id-ID') }}
                    </span>
                </template>

                <template #cell-produk="{ row }">
                    <span
                        class="px-2.5 py-1 text-xs rounded-full font-medium whitespace-nowrap"
                        :class="[getProductColor(row.produk).bg, getProductColor(row.produk).text]"
                    >
                        {{ row.produk }}
                    </span>
                </template>

                <template #cell-jenis="{ row }">
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-full font-medium"
                        :class="row.jenis === 'Penarikan'
                            ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400'
                            : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400'"
                    >
                        <Icon
                            :icon="row.jenis === 'Penarikan' ? 'mdi:arrow-down-circle-outline' : 'mdi:arrow-up-circle-outline'"
                            class="w-3.5 h-3.5"
                        />
                        {{ row.jenis }}
                    </span>
                </template>

                <template #cell-aksi="{ row }">
                    <div class="flex justify-center">
                        <Link
                            :href="`/admin/savings/show/${row.id}`"
                            class="text-gray-400 hover:text-blue-600 dark:text-gray-500 dark:hover:text-blue-400 transition-colors"
                        >
                            <Icon icon="mdi:eye-outline" class="w-5 h-5" />
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
    </AdminLayout>
</template>
