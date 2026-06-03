<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue';
import { ref, reactive, computed, watch } from 'vue';
import BaseFunctionality from '@/Components/Table/BaseFunctionality.vue';
import BaseTable from '@/Components/Table/BaseTable.vue';
import Pagination from '@/Components/Table/Pagination.vue';
import CardInfo from '@/Components/CardInfo.vue';
import { usePage, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Button from '@/Components/Form/Button.vue';
import useFinancingStatus, { getStatusLabel } from '@/Composables/useFinancingStatus'
import ReviewIcon from '@/Icons/ReviewIcon.vue'

const page = usePage();

const role = computed(() => page.props.auth.role);

const can = computed(() => page.props.auth.can);

const isLoading = ref(false);

const props = defineProps({
    financings: Object,
    summary: Array,
    filters: Object,
});

const transactions = computed(() => page.props.financings ?? {
    data: [], current_page: 1, per_page: 10, total: 0, links: [],
})

const tabs = [
    { key: 'all', label: 'Semua' },
    { key: 'request', label: 'Permohonan Pembiayaan Murabahah' },
    { key: 'validated', label: 'Permohonan Tervalidasi' },
    { key: 'active', label: 'Pembiayaan Aktif' },
]

const filters = reactive({
    search: page.props.filters?.search ?? '',
    per_page: page.props.filters?.per_page ?? 10,
    tab: page.props.filters?.tab ?? 'all',
    sort_by: page.props.filters?.sort_by ?? 'created_at',
    sort_dir: page.props.filters?.sort_dir ?? 'desc',
})

const applyFilters = () => {
    isLoading.value = true
    router.get(
        '/admin/financings',
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
            onFinish: () => { isLoading.value = false },
        }
    )
}

const tableTitle = computed(() => {
    const map = {
        all: 'Data Pembiayaan Murabahah',
        request: 'Data Permintaan Pembiayaan',
        active: 'Data Pembiayaan Aktif',
    }
    return map[filters.tab] ?? 'Data Pembiayaan Murabahah'
})

const columns = computed(() => {
    const baseColumns = [
        { key: 'financing_transaction_code', label: 'No. Transaksi', align: 'center' },
        { key: 'akad_date', label: 'Tanggal Akad', align: 'center' },
        { key: 'user', label: 'Anggota', align: 'center' },
        { key: 'product_name', label: 'Nama Produk', align: 'center' },
        { key: 'status', label: 'Status', align: 'center' },
        { key: 'aksi', label: 'Aksi', align: 'center' }
    ]

    if (filters.tab === 'active') {
        baseColumns.splice(3, 0, { key: 'tenor_left', label: 'Sisa Tenor', align: 'center' })
    }

    return baseColumns
})

const toggleSort = (column) => {
    if (filters.sort_by === column) {
        filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
    } else {
        filters.sort_by = column
        filters.sort_dir = 'asc'
    }
    applyFilters()
}

const breadcrumbItems = [
    { label: 'Dashboard', link: '/admin/dashboard' },
    { label: 'Pengelolaan Pembiayaan' },
];

let timeout
watch(() => filters.search, () => {
    clearTimeout(timeout)
    timeout = setTimeout(applyFilters, 500)
})
watch(() => filters.per_page, applyFilters)
watch(() => filters.tab, applyFilters)

</script>

<template>
    <AdminLayout title="Pengelolaan Pembiayaan Murabahah">
        <PageBreadcrumb :page-title="'Pengelolaan Pembiayaan Murabahah'" :items="breadcrumbItems" />
        <!-- Ringkasan -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Ringkasan
                </h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <CardInfo v-for="item in summary" :key="item.title" :title="item.title" :content="item.value"
                    :percentage="item.percentage" />
            </div>
        </div>

        <div class="relative mt-15">
            <!-- Tabs -->
            <div class="flex gap-2 absolute -top-8 left-6 z-10">
                <button v-for="tab in tabs" :key="tab.key" @click="filters.tab = tab.key"
                    class="font-head px-4 py-2 rounded-lg text-sm border transition border-gray-200 dark:border-slate-700"
                    :class="filters.tab === tab.key
                        ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 font-medium shadow'
                        : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-600'">
                    {{ tab.label }}
                </button>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-hidden relative z-10">
                <!-- Header Table -->
                <div class="flex justify-between items-center">
                    <div class="px-6 pt-6 pb-4 border-b border-gray-100 dark:border-slate-700">
                        <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100 mb-0.5">
                            {{ tableTitle }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">
                            Lacak transaksi pembiayaan murabahah di sini
                        </p>
                    </div>
                    <Button :href="`/admin/financings/create`" variant="secondary" size="small" class="mx-6">
                        <Icon icon="mdi:plus" class="w-5 h-5 mr-1" />
                        Tambah Pembiayaan
                    </Button>
                </div>

                <!-- Functionality -->
                <BaseFunctionality :per-page="filters.per_page" :search="filters.search"
                    @update:per-page="val => filters.per_page = val" @update:search="val => filters.search = val">
                    <template #actions></template>
                </BaseFunctionality>

                <!-- Table -->
                <BaseTable :columns="columns" :align="center" :data="transactions.data" :is-loading="isLoading"
                    :pagination="transactions" :sort-by="filters.sort_by" :sort-dir="filters.sort_dir"
                    @sort="toggleSort">

                    <template #cell-status="{ row }">
                        <span :class="useFinancingStatus(row.status)">
                            {{ getStatusLabel(row.status) }}
                        </span>
                    </template>

                    <template #cell-aksi="{ row }">
                        <div class="flex justify-center">

                            <Button
                                v-if="can['edit_murabahah'] && (role === 'Staf Murabahah' && (row.status === 'Disetujui' || row.status === 'Ditolak' || row.status === 'Menunggu Kelengkapan Dokumen'))"
                                :href="`/admin/financings/draft/${row.id}`" size="small" variant="info">
                                <ReviewIcon width="18px" height="18px" />
                                Lanjutkan
                            </Button>
                            <Button
                                v-else-if="can['view_murabahah'] && ((role === 'Staf Murabahah' && ((row.status === 'Angsuran Berjalan') || (row.status === 'Belum Ditinjau') || (row.status === 'Lunas'))) || (role === 'Ketua Murabahah' && (row.status !== 'Belum Ditinjau')))"
                                :href="`/admin/financings/show/${row.id}`" size="small" variant="secondary">
                                <Icon icon="mdi:eye-outline" class="w-5 h-5" />
                                Lihat Detail
                            </Button>

                            <Button
                                v-if="can['approve_murabahah'] && (role === 'Ketua Murabahah' && (row.status === 'Belum Ditinjau'))"
                                :href="`/admin/financings/validation/${row.id}`" size="small" variant="warning">
                                <ReviewIcon width="18px" height="18px" />
                                Tinjau
                            </Button>

                        </div>
                    </template>
                </BaseTable>

                <!-- Pagination -->
                <Pagination :links="transactions.links" :total="transactions.total" />
            </div>
        </div>
    </AdminLayout>
</template>
