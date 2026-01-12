<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { reactive, watch } from 'vue'
import { Icon } from '@iconify/vue'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'
import BaseFunctionality from '../../../Components/Table/BaseFunctionality.vue'
import BaseTable from '../../../Components/Table/BaseTable.vue'
import CardInfo from '../../../Components/CardInfo.vue'
import Pagination from '../../../Components/Table/Pagination.vue'

const props = defineProps({
    members: Object,
    filters: Object,
    statuses: Array,
    summary: Object,
})

const columns = [
    { key: 'no', label: 'No' },
    { key: 'no_anggota', label: 'No Anggota' },
    { key: 'profil', label: 'Profil Anggota', sortable: true },
    { key: 'joined_at', label: 'Tanggal Bergabung', sortable: true },
    { key: 'phone', label: 'Kontak' },
    { key: 'total_simpanan', label: 'Total Simpanan', align: 'right' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'aksi', label: 'Aksi', align: 'center' },
]

const selectFilters = [
    {
        key: 'status',
        label: 'Semua Status',
        options: props.statuses.map(s => ({
            label: s,
            value: s
        })),
        optionLabel: 'label',
        optionValue: 'value',
    }
]

const filters = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    per_page: props.filters.per_page ?? 10,
    sort_by: props.filters.sort_by ?? 'joined_date',
    sort_dir: props.filters.sort_dir ?? 'desc',
})

const applyFilters = () => {
    router.get(
        '/admin/anggota',
        {
            search: filters.search || undefined,
            status: filters.status || undefined,
            per_page: filters.per_page,
            sort_by: filters.sort_by,
            sort_dir: filters.sort_dir,
        },
        {
            preserveScroll: true,
            replace: true,
            preserveState: false,
        }
    )
}

let timeout
watch(() => filters.search, () => {
    clearTimeout(timeout)
    timeout = setTimeout(applyFilters, 500)
})

watch(() => filters.per_page, applyFilters)
watch(() => filters.status, applyFilters)

const toggleSort = (column) => {
    if (filters.sort_by === column) {
        filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
    } else {
        filters.sort_by = column
        filters.sort_dir = 'asc'
    }
    applyFilters()
}

const statusClass = (status) => {
    switch (status) {
        case 'Aktif':
            return 'bg-green-100 text-green-700 border border-green-200'
        case 'Tidak Aktif':
            return 'bg-red-100 text-red-700 border border-red-200'
        case 'Mengundurkan Diri':
            return 'bg-orange-100 text-orange-700 border border-orange-200'
        default:
            return 'bg-gray-100 text-gray-600 border border-gray-200'
    }
}
</script>

<template>
    <AdminLayout title="Daftar Anggota">
        <!-- Breadcrumb + Title -->
         <PageBreadcrumb page-title="Pengelolaan Data Anggota"/>

        <!-- Ringkasan -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 mb-6">
            <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Ringkasan</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <CardInfo
                    title="Jumlah Anggota Aktif"
                    :content="summary.active"
                    :percentage="summary.active_percent"
                />
                <CardInfo
                    title="Anggota Baru Bulan Ini"
                    :content="summary.new_this_month"
                    :percentage="summary.new_percent"
                />
                <CardInfo
                    title="Menunggu Verifikasi"
                    :content="summary.in_review"
                    :percentage="summary.in_review_percent"
                />
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <!-- Card Header -->
            <div class="flex justify-between items-center p-6 border-b">
                <div>
                    <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100">Data Anggota</h2>
                </div>
            </div>

            <!-- Filter & Search -->
            <BaseFunctionality
                :per-page="filters.per_page"
                :search="filters.search"
                :filters="{ status: filters.status }"
                :selects="selectFilters"
                @update:per-page="val => filters.per_page = val"
                @update:search="val => filters.search = val"
                @update:filters="val => filters.status = val.status"
            />

            <!-- Table -->
            <BaseTable
                :columns="columns"
                :data="members.data"
                :pagination="members"
                :sort-by="filters.sort_by"
                :sort-dir="filters.sort_dir"
                @sort="toggleSort"
            >
                <template #cell-no="{ index }">
                    {{ (members.current_page - 1) * members.per_page + index + 1 }}
                </template>

                <template #cell-profil="{ row }">
                    <div class="flex items-center gap-3">
                        <img :src="row.avatar" class="w-9 h-9 rounded-full" />
                        <span>{{ row.name }}</span>
                    </div>
                </template>

                <template #cell-phone="{ row }">
                    <div class="flex items-center gap-2">
                        <Icon icon="mdi:whatsapp" class="text-green-500" />
                        {{ row.phone }}
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <span
                        class="px-3 py-1 text-xs rounded-full font-medium"
                        :class="statusClass(row.status)"
                    >
                        {{ row.status }}
                    </span>
                </template>

                <template #cell-aksi="{ row }">
                    <div class="flex justify-center gap-3">
                        <Link
                            :href="`/admin/users/${row.id}/edit`"
                            class="text-gray-500 hover:text-blue-600 dark:text-gray-100 dark:hover:text-blue-400"
                        >
                            <Icon icon="mdi:pencil-outline" class="w-5 h-5" />
                        </Link>

                        <Link
                            :href="`/admin/users/show/${row.id}`"
                            class="text-gray-500 hover:text-blue-600 dark:text-gray-100 dark:hover:text-blue-400"
                        >
                            <Icon icon="mdi:eye-outline" class="w-5 h-5" />
                        </Link>
                    </div>
                </template>
            </BaseTable>

            <!-- Pagination -->
            <Pagination
                :links="members.links"
                :total="members.total"
            />
        </div>
    </AdminLayout>
</template>
