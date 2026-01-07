<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { reactive, watch } from 'vue'
import { Icon } from '@iconify/vue'
import BaseTable from '../../../../Components/Table/BaseTable.vue'
import BaseFunctionality from '../../../../Components/Table/BaseFunctionality.vue'
import Pagination from '../../../../Components/Table/Pagination.vue'
import PageBreadcrumb from '../../../../Components/PageBreadcrumb.vue'


const columns = [
    { key: 'no', label: 'No' },
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'nik', label: 'NIK' },
    { key: 'unit_kerja', label: 'Unit Kerja' },
    { key: 'email', label: 'Email' },
    { key: 'aksi', label: 'Aksi'},
]

const props = defineProps({
    prospectiveMembers: Object,
    filters: Object,
    workUnits: Array,
    title: String,
})

const selectFilters = [
    {
        key: 'work_unit_id',
        label: 'Semua Unit Kerja',
        options: props.workUnits,
        optionLabel: 'name',
        optionValue: 'id',
    }
]

const filters = reactive({
    search: props.filters.search ?? '',
    work_unit_id: props.filters.work_unit_id ?? '',
    per_page: props.filters.per_page ?? 10,
    sort_by: props.filters.sort_by ?? 'created_at',
    sort_dir: props.filters.sort_dir ?? 'desc',
})

const applyFilters = () => {
    router.get(
        '/admin/verifikasi',
        {
            search: filters.search || undefined,
            work_unit_id: filters.work_unit_id || undefined,
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
watch(() => filters.work_unit_id, applyFilters)

const toggleSort = (column) => {
    if (filters.sort_by === column) {
        filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
    } else {
        filters.sort_by = column
        filters.sort_dir = 'asc'
    }
    applyFilters()
}
</script>

<template>
    <AdminLayout>
        <!-- Breadcrumb + Title -->
        <PageBreadcrumb page-title="Verifikasi Calon Anggota" />

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b">
                <div>
                    <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100">Data Calon Anggota</h2>
                </div>
            </div>

            <!-- Filter & Search -->
            <BaseFunctionality
                :per-page="filters.per_page"
                :search="filters.search"
                :filters="{ work_unit_id: filters.work_unit_id }"
                :selects="selectFilters"
                @update:perPage="val => filters.per_page = val"
                @update:search="val => filters.search = val"
                @update:filters="val => filters.work_unit_id = val.work_unit_id"
            />

            <!-- Table -->
             <BaseTable
                :columns="columns"
                :data="prospectiveMembers.data"
                :pagination="prospectiveMembers"
                :sort-by="filters.sort_by"
                :sort-dir="filters.sort_dir"
                @sort="toggleSort"
            >
                <template #cell-no="{ index }">
                    {{ (prospectiveMembers.current_page - 1) * prospectiveMembers.per_page + index + 1 }}
                </template>

                <template #cell-aksi="{ row }">
                    <Link
                        :href="`/admin/verifikasi/${row.member_number}`"
                        class="inline-flex items-center gap-2
                            bg-blue-light-600 hover:bg-blue-light-900 text-white px-4 py-2 rounded-lg"
                    >
                        <Icon icon="tabler:checklist" class="w-4 h-4" />
                        Tinjau
                    </Link>
                </template>
            </BaseTable>

            <!-- Pagination -->
            <Pagination
                :links="prospectiveMembers.links"
                :total="prospectiveMembers.total"
            />
        </div>
    </AdminLayout>
</template>
