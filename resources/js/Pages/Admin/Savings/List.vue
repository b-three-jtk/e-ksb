<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, reactive, watch } from 'vue'

const tabs = [
    { key: 'semua', label: 'Semua' },
    { key: 'permohonan', label: 'Permohonan Penarikan/Penyetoran' },
    { key: 'pokok', label: 'Simpanan Pokok' },
    { key: 'wajib', label: 'Simpanan Wajib' },
    { key: 'sukarela', label: 'Simpanan Sukarela' },
]

const page = usePage()

const filters = reactive({
    search: page.props.filters?.search ?? '',
    per_page: page.props.filters.per_page ?? 10,
    tab: page.props.filters.tab ?? 'semua',
    sort_by: page.props.filters?.sort_by ?? 'transaction_date',
    sort_dir: page.props.filters?.sort_dir ?? 'desc'
})

const toggleSortDate = () => {
    if(filters.sort_by === 'transaction_date') {
        filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
    } else {
        filters.sort_by = 'transaction_date'
        filters.sort_dir = 'desc'
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

const goToPage = (url) => {
    router.get(
        url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        }
    )
}

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
    <AdminLayout>
        <!-- Header -->
        <div class="font-body flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-blue-900 dark:text-blue-300">
                Pengelolaan Simpanan
            </h1>

            <!-- Breadcrumb -->
            <div class="mt-2 sm:mt-0">
                <div class="flex items-center text-sm text-gray-500">
                    <Link href="/admin/dashboard" class="hover:text-blue-600">
                        Dashboard
                    </Link>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-blue-600 font-medium">Pengelolaan Simpanan</span>
                </div>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 mb-10 relative">
            <div class="flex justify-between items-center mb-6">
                <h2 class="font-head font-semibold dark:text-white">Ringkasan</h2>

                <!-- Tombol Tambah -->
                <Link
                    href="#"
                    class="font-head inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"
                >
                    <Icon icon="mdi:plus" />
                    Tambah Transaksi
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div
                    v-for="item in summary"
                    :key="item.title"
                    class="border rounded-xl p-5"
                >
                    <p class="font-body text-sm text-gray-500 dark:text-gray-100">
                        {{ item.title }}
                    </p>

                    <p class="font-head text-xl font-bold mt-1 dark:text-gray-100">
                        {{ item.value }}
                    </p>

                    <div v-if="item.trend" class="flex items-center gap-2 mt-2">
                        <span
                            :class="item.up ? 'text-green-600' : 'text-red-600'"
                            class="text-sm font-medium"
                        >
                            {{ item.trend }}
                        </span>
                        <Icon
                            :icon="item.up ? 'mdi:arrow-up' : 'mdi:arrow-down'"
                            :class="item.up ? 'text-green-600' : 'text-red-600'"
                        />
                    </div>
                </div>
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
                <div class="px-6 pb-4 p-7">
                    <h3 class="font-head font-semibold text-gray-900 dark:text-slate-100">
                        {{ tableTitle }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-slate-400">
                        Lacak transaksi simpanan koperasi di sini
                    </p>
                </div>

                <!-- Data Simpanan Per Halaman -->
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500">Tampilkan</span>
                        <select
                            v-model="filters.per_page" 
                            class="border rounded px-3 py-1 text-sm bg-white dark:bg-slate-700 border-gray-200 dark:border-slate-600 text-gray-900 dark:text-slate-100">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                        <span class="font-head text-sm text-gray-500">data per halaman</span>
                    </div>

                    <div class="flex gap-3">
                        <div class="relative">
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="Search..."
                                class="font-head pl-10 pr-4 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 border-gray-200 dark:border-slate-600 text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-400"
                            />
                            <Icon
                                icon="mdi:magnify"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                            />
                        </div>

                        <!-- <button class="font-head flex items-center gap-2 border rounded-lg px-4 py-2 text-sm bg-white dark:bg-slate-700 border-gray-200 dark:border-slate-600 text-gray-900 dark:text-slate-100">
                            <Icon icon="mdi:filter-variant" />
                            Filter
                        </button> -->
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y">
                        <thead class="font-head bg-gray-50 dark:bg-slate-700 dark:text-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-medium">No</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">No. Transaksi</th>
                                <th
                                    @click="toggleSortDate"
                                    class="px-6 py-3 text-left text-sm font-medium cursor-pointer select-none"
                                >
                                    <div class="flex items-center gap-1">
                                        Tanggal
                                        <Icon
                                            v-if="filters.sort_by === 'transaction_date' && filters.sort_dir === 'asc'"
                                            icon="tabler:chevron-up"
                                            class="w-4 h-4"
                                        />
                                        <Icon
                                            v-else-if="filters.sort_by === 'transaction_date' && filters.sort_dir === 'desc'"
                                            icon="tabler:chevron-down"
                                            class="w-4 h-4"
                                        />
                                        <Icon
                                            v-else
                                            icon="tabler:chevrons-up-down"
                                            class="w-4 h-4 opacity-40"
                                        />
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-sm font-medium">Anggota</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">Nominal</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">Produk Simpanan</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">Jenis Transaksi</th>
                                <th class="px-6 py-3 text-center text-sm font-medium">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700 dark:text-gray-100">
                            <tr
                                v-for="(trx, index) in transactions.data"
                                :key="trx.id"
                                class="hover:bg-gray-50 dark:hover:bg-slate-700"
                            >
                                <td class="px-6 py-4 text-sm">
                                    {{ (transactions.current_page - 1) * transactions.per_page + index + 1 }}
                                </td>


                                <td class="px-6 py-4 text-sm">
                                    {{ trx.no_transaksi }}
                                </td>

                                <td class="px-6 py-4 text-sm">
                                    {{ trx.tanggal }}
                                </td>

                                <td class="px-6 py-4 text-sm">
                                    {{ trx.anggota }}
                                </td>

                                <td
                                    class="px-6 py-4 text-sm font-medium"
                                    :class="trx.nominal < 0 ? 'text-red-500' : 'text-green-500'"
                                >
                                    Rp.{{ Math.abs(trx.nominal).toLocaleString('id-ID') }}
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 text-xs rounded-full"
                                        :class="{
                                            'bg-cyan-100 text-cyan-700': trx.produk === 'Sukarela',
                                            'bg-green-100 text-green-700': trx.produk === 'Wajib',
                                            'bg-pink-100 text-pink-700': trx.produk === 'Pokok',
                                        }"
                                    >
                                        {{ trx.produk }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-sm">
                                    {{ trx.jenis }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <button
                                        class="font-head inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600"
                                    >
                                        <Icon icon="mdi:eye-outline" />
                                        Tinjau
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div 
                v-if="transactions.total"
                class="font-head p-6 flex justify-center gap-3 text-sm text-gray-600 dark:text-slate-400">
                <template
                    v-for="link in transactions.links"
                    :key="link.label"    
                >
                    <span
                        v-if="!link.url"
                        class="px-3 py-1 text-gray-400"
                        v-html="link.label"
                    />
                    <button
                        v-else
                        @click="goToPage(link.url)"
                        class="px-3 py-1 border rounded"
                        :class="{ 'bg-blue-600 text-white': link.active }"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
