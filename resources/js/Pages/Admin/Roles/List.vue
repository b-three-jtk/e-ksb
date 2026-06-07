<script setup>
import { Link, router } from '@inertiajs/vue3'
import { reactive, watch } from 'vue'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import BaseTable from '@/Components/Table/BaseTable.vue'
import BaseFunctionality from '@/Components/Table/BaseFunctionality.vue'
import Pagination from '@/Components/Table/Pagination.vue'
import { Icon } from '@iconify/vue';
import Button from '@/Components/Form/Button.vue';

const props = defineProps({
    roles: Object,
    filters: Object,
})

const columns = [
    { key: 'no', label: 'No' },
    { key: 'name', label: 'Peran' },
    { key: 'permission_count', label: 'Jumlah Akses' },
    { key: 'actions', label: 'Aksi' },
]

const filters = reactive({
    search: props.filters?.search ?? '',
    per_page: props.filters?.per_page ?? 10,
    sort_by: props.filters?.sort_by ?? 'name',
    sort_dir: props.filters?.sort_dir ?? 'asc',
})

const applyFilters = () => {
    router.get(
        '/admin/roles',
        {
            search: filters.search || undefined,
            per_page: filters.per_page,
            sort_by: filters.sort_by,
            sort_dir: filters.sort_dir,
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

watch(
    () => [filters.per_page],
    applyFilters
)

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
    { name: 'Dashboard', link: '/admin' },
    { name: 'Peran dan Akses' },
]
</script>

<template>
    <AdminLayout title="Peran dan Akses">
        <PageBreadcrumb page-title="Peran dan Akses" :items="breadcrumbItems" />

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b">
                <div>
                    <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100">Daftar Peran dan Akses</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kelola hak akses tiap peran dalam aplikasi.</p>
                </div>
                <Button href="/admin/roles/create" variant="secondary">
                    <Icon icon="mdi:plus" class="w-5 h-5 mr-1" />
                    Tambah Peran
                </Button>
            </div>

            <BaseFunctionality
                :per-page="filters.per_page"
                :search="filters.search"
                :showSearchButton="false"
                :selects="[]"
                @update:perPage="val => filters.per_page = val"
                @update:search="val => filters.search = val"
            />

            <BaseTable
                :columns="columns"
                :data="roles.data"
                :pagination="roles"
                :sort-by="filters.sort_by"
                :sort-dir="filters.sort_dir"
                @sort="toggleSort"
            >
                <template #cell-no="{ index }">
                    {{ (roles.current_page - 1) * roles.per_page + index + 1 }}
                </template>

                <template #cell-actions="{ row }">
                    <Button
                        :href="`/admin/roles/${row.id}/edit`" size="small" variant="secondary">
                        <Icon icon="mdi:pencil-outline" class="w-5 h-5" />
                        Edit
                    </Button>
                </template>
            </BaseTable>

            <Pagination :links="roles.links" :total="roles.total" />
        </div>
    </AdminLayout>
</template>
