<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, reactive, watch } from 'vue'
import PageBreadcrumb from '../../../../Components/PageBreadcrumb.vue'
import BaseFunctionality from '../../../../Components/Table/BaseFunctionality.vue'
import BaseTable from '../../../../Components/Table/BaseTable.vue'
import Pagination from '../../../../Components/Table/Pagination.vue'
import Button from '@/Components/Form/Button.vue'

const isLoading = ref(false)

const props = defineProps({
    members: Object,
    workUnits: Array,
    filters: Object,
})

const columns = [
    { key: 'no', label: 'No' },
    { key: 'member_number', label: 'Nomor Anggota' },
    { key: 'name', label: 'Nama' },
    { key: 'work_unit', label: 'Unit Kerja' },
    { key: 'email', label: 'Email' },
    { key: 'aksi', label: 'Aksi' },
]

const page = usePage()

const filters = reactive({
    search: page.props.filters?.search ?? '',
    per_page: page.props.filters?.per_page ?? 10,
    sort_by: page.props.filters?.sort_by ?? 'created_at',
    sort_dir: page.props.filters?.sort_dir ?? 'desc',
    work_unit_id: page.props.filters?.work_unit_id ?? ''
})

const selectFilters = [
    {
        key: 'work_unit_id',
        label: 'Semua Unit Kerja',
        options: props.workUnits || [],
        optionLabel: 'name',
        optionValue: 'id'
    }
]

const searchTooltipItems = [
    'Nomor Anggota',
    'Nama Anggota',
    'Email'
]

const toggleSort = (column) => {
    if(filters.sort_by === column) {
        filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
    } else {
        filters.sort_by = column
        filters.sort_dir = 'asc'
    }
    applyFilters()
}

const members = computed(() => page.props.members ?? {
    data: [],
    current_page: 1,
    per_page: 10,
    total: 0,
    links: [],
})

const applyFilters = () => {
    isLoading.value = true
    router.get(
        '/admin/resignations/list',
        {
            search: filters.search || undefined,
            per_page: filters.per_page,
            sort_by: filters.sort_by,
            sort_dir: filters.sort_dir,
            work_unit_id: filters.work_unit_id || undefined,
            page: 1,
        },
        {
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isLoading.value = false
            }
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

const breadcrumbItems = [
    {name: 'Dashboard', link: '/admin'},
    {name: 'Pengunduran Diri Anggota'},
]
</script>

<template>
    <AdminLayout title="Data Pengunduran Diri Anggota">
        <PageBreadcrumb page-title="Pengunduran Diri Anggota" :items="breadcrumbItems" />

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b">
                <div>
                    <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100">Data Permohonan Pengunduran Diri Anggota</h2>
                </div>
            </div>

            <!-- Filter & Search -->
            <BaseFunctionality
                :per-page="filters.per_page"
                :search="filters.search"
                :filters="{ work_unit_id: filters.work_unit_id }"
                :selects="selectFilters"
                :search-tooltip="searchTooltipItems"
                @update:per-page="val => filters.per_page = val"
                @update:search="val => filters.search = val"
                @update:filters="val => filters.work_unit_id = val.work_unit_id"
            />

            <BaseTable
                :columns="columns"
                :data="members.data"
                :is-loading="isLoading"
                :pagination="members"
                :sort-by="filters.sort_by"
                :sort-dir="filters.sort_dir"
                @sort="toggleSort"
            >
                <template #cell-no="{ index }">
                    {{ (members.current_page - 1) * members.per_page + index + 1 }}
                </template>

                <template #cell-member_number="{ row }">
                    {{ row.member_number }}
                </template>

                <template #cell-name="{ row }">
                    {{ row.name }}
                </template>

                <template #cell-work_unit="{ row }">
                    {{ row.work_unit?.name ?? '-' }}
                </template>

                <template #cell-email="{ row }">
                    {{ row.email }}
                </template>

                <template #cell-aksi="{ row }">
                    <Button variant="info" size="small" :href="`/admin/resignation/${row.id}`">
                        <Icon icon="tabler:checklist" class="w-4 h-4" />
                            Tinjau
                        </Button>
                </template>
            </BaseTable>

            <Pagination
                :links="members.links"
                :total="members.total"
            />
        </div>
    </AdminLayout>
</template>
