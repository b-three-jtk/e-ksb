<script setup>
import { Link, router } from '@inertiajs/vue3'
import { reactive, watch } from 'vue'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { Icon } from '@iconify/vue'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'
import BaseTable from '../../../Components/Table/BaseTable.vue'
import BaseFunctionality from '../../../Components/Table/BaseFunctionality.vue'
import Pagination from '../../../Components/Table/Pagination.vue'
import UserIcon from '../../../Icons/UserIcon.vue'
import Button from '../../../Components/Form/Button.vue'

const columns = [
    { key: 'no', label: 'No' },
    { key: 'nik', label: 'NIK' },
    { key: 'name', label: 'Profil Admin', sortable: true },
    { key: 'email', label: 'Email' },
    { key: 'posisi', label: 'Posisi' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'aksi', label: 'Aksi', align: 'center' },
]

const props = defineProps({
    admins: Object,
    filters: Object,
    roles: Array,
})

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    role: props.filters?.role ?? '',
    per_page: props.filters?.per_page ?? 10,
    sort_by: props.filters.sort_by ?? 'created_at',
    sort_dir: props.filters.sort_dir ?? 'desc',
})

const selectFilters = [
    {
        key: 'status',
        label: 'Semua Status',
        options: [
            { label: 'Aktif', value: 'Aktif' },
            { label: 'Tidak Aktif', value: 'Tidak Aktif' },
            { label: 'Mengundurkan Diri', value: 'Mengundurkan Diri' },
        ],
        optionLabel: 'label',
        optionValue: 'value',
    },
    {
        key: 'role',
        label: 'Semua Posisi',
        options: props.roles.map(r => ({
            label: r,
            value: r,
        })),
        optionLabel: 'label',
        optionValue: 'value',
    },
]

const applyFilters = () => {
    router.get(
        '/admin/list',
        {
            search: filters.search || undefined,
            status: filters.status || undefined,
            role: filters.role || undefined,
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
    () => [filters.per_page, filters.status, filters.role],
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
    {name: 'Dashboard', link: '/admin'},
    {name: 'Pengelolaan Admin'},
];
</script>

<template>
    <AdminLayout title="Pengelolaan Admin">
        <!-- Breadcrumb + Title -->
        <PageBreadcrumb page-title="Pengelolaan Admin" :items="breadcrumbItems" />

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <!-- Card Header -->
            <div class="flex justify-between items-center p-6 border-b">
                <div>
                    <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100">Data Admin</h2>
                </div>
                <Button size="medium" variant="secondary" href="/admin/create">
                    <Icon icon="mdi:plus" class="w-5 h-5"/>
                    Tambah Admin
                </Button>
            </div>

            <!-- Filter & Search -->
            <BaseFunctionality
                :per-page="filters.per_page"
                :search="filters.search"
                :filters="{ status: filters.status, role: filters.role }"
                :selects="selectFilters"
                @update:perPage="val => filters.per_page = val"
                @update:search="val => filters.search = val"
                @update:filters="val => {
                    filters.status = val.status
                    filters.role = val.role
                }"
            />

            <!-- Table -->
            <BaseTable
                :columns="columns"
                :data="admins.data"
                :pagination="admins"
                :sort-by="filters.sort_by"
                :sort-dir="filters.sort_dir"
                @sort="toggleSort"
            >
                <template #cell-no="{ index }">
                    {{ (admins.current_page - 1) * admins.per_page + index + 1 }}
                </template>

                <template #cell-name="{ row }">
                    <div class="flex items-center gap-3">
                        <img v-if="row.avatar" :src="row.avatar" class="w-9 h-9 rounded-full object-cover" />
                         <div
                            v-else
                            class="w-9 h-9 flex items-center justify-center
                                rounded-full bg-gray-200 dark:bg-gray-700"
                        >
                            <UserIcon />
                        </div>
                        <span class="font-medium">{{ row.name }}</span>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <span
                        class="px-3 py-1 rounded-full text-xs"
                        :class="{
                            'bg-green-100 text-green-700': row.status === 'Aktif',
                            'bg-red-100 text-red-700': row.status === 'Tidak Aktif',
                            'bg-orange-100 text-orange-700': row.status === 'Mengundurkan Diri',
                        }"
                    >
                        {{ row.status }}
                    </span>
                </template>

                <template #cell-aksi="{ row }">
                    <div class="flex justify-center gap-3">
                        <Link
                            :href="`/admin/edit/${row.id}`"
                            class="text-gray-500 hover:text-blue-600 transition"
                            title="Edit"
                        >
                            <Icon icon="mdi:pencil-outline" class="w-5 h-5"/>
                        </Link>

                        <Link
                            :href="`/admin/show/${row.id}`"
                            class="text-gray-500 hover:text-blue-600 dark:text-gray-100 dark:hover:text-blue-400"
                            title="Lihat"
                        >
                            <Icon icon="mdi:eye-outline" class="w-5 h-5"/>
                        </Link>
                    </div>
                </template>
            </BaseTable>

            <!-- Pagination -->
            <Pagination
                :links="admins.links"
                :total="admins.total"
            />
        </div>
    </AdminLayout>
</template>
