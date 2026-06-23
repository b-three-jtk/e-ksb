<script setup>
import { usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, reactive, watch, onMounted } from 'vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import BaseFunctionality from '@/Components/Table/BaseFunctionality.vue'
import BaseTable from '@/Components/Table/BaseTable.vue'
import Pagination from '@/Components/Table/Pagination.vue'
import Button from '@/Components/Form/Button.vue'
import Swal from 'sweetalert2'
import { useWhatsAppResignation } from '@/Composables/useWhatsAppResignation'
import { toast } from 'vue3-toastify'

const isLoading = ref(false)

const props = defineProps({
    members: Object,
    filters: Object,
})

const page = usePage()

const can = computed(() => page.props.auth.can);

const columns = computed(() => {
    const baseColumns = [
        { key: 'no', label: 'No' },
        { key: 'user_code', label: 'Nomor Anggota' },
        { key: 'name', label: 'Nama' },
        { key: 'email', label: 'Email' },
    ]

    if (can.value?.['edit_pengunduran_diri']) {
        baseColumns.push({ key: 'aksi', label: 'Aksi' })
    }

    return baseColumns
})

const filters = reactive({
    search: page.props.filters?.search ?? '',
    per_page: page.props.filters?.per_page ?? 10,
    sort_by: page.props.filters?.sort_by ?? 'created_at',
    sort_dir: page.props.filters?.sort_dir ?? 'desc',
})

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

const breadcrumbItems = [
    {name: 'Dashboard', link: '/admin'},
    {name: 'Pengunduran Diri Anggota'},
]

const { sendResignationToWhatsApp } = useWhatsAppResignation(toast)
const resignationInfo = ref(null)

const showResignationInfo = async () => {
    if (!resignationInfo.value) return

    const result = await Swal.fire({
        title: 'Pengunduran Diri Disetujui',
        html: `
            <div style="text-align:left;font-size:14px;line-height:1.8">
                <div><strong>Nama:</strong> ${resignationInfo.value.name ?? '-'}</div>
                <div><strong>Nomor Anggota:</strong> ${resignationInfo.value.user_code ?? '-'}</div>
            </div>
        `,
        icon: 'success',
        confirmButtonText: 'Kirim ke WhatsApp',
        confirmButtonColor: '#009141',
        showCancelButton: true,
        cancelButtonText: 'Tutup',
    })

    if (result.isConfirmed) {
        sendResignationToWhatsApp(resignationInfo.value)
    }

    resignationInfo.value = null
}

onMounted(() => {
    resignationInfo.value = page.props.flash?.resignation_info ?? null
    if (resignationInfo.value) {
        showResignationInfo()
    }
})
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
                :search-tooltip="searchTooltipItems"
                @update:per-page="val => filters.per_page = val"
                @update:search="val => filters.search = val"
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
                <template #cell-aksi="{ row }">
                    <Button variant="warning" size="small" :href="`/admin/resignations/${row.id}`">
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
