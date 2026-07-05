<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import BaseTable from '@/Components/Table/BaseTable.vue'
import Pagination from '@/Components/Table/Pagination.vue'
import BaseFunctionality from '@/Components/Table/BaseFunctionality.vue'
import Button from '@/Components/Form/Button.vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
    logs: Object,
    filters: Object,
})

const filters = reactive({
    search: props.filters?.search ?? '',
    per_page: props.filters?.per_page ?? 10,
    event: props.filters?.event ?? '',
    type: props.filters?.type ?? '',
})

const columns = [
    { key: 'created_at', label: 'Waktu' },
    { key: 'user.name', label: 'Pengguna' },
    { key: 'event', label: 'Aksi' },
    { key: 'auditable_type', label: 'Tipe Data' },
    { key: 'auditable_id', label: 'ID Data' },
    { key: 'actions', label: 'Detail', align: 'center' },
]

const filterSelects = [
    {
        key: 'event',
        label: 'Semua Aksi',
        options: [
            { value: 'created', label: 'Dibuat (Created)' },
            { value: 'updated', label: 'Diubah (Updated)' },
            { value: 'deleted', label: 'Dihapus (Deleted)' }
        ],
        optionLabel: 'label',
        optionValue: 'value'
    },
    {
        key: 'type',
        label: 'Semua Tipe Data',
        options: [
            { value: 'Member', label: 'Anggota' },
            { value: 'Financing', label: 'Pembiayaan Murabahah' },
            { value: 'InstallmentPaymentTransaction', label: 'Pembayaran Angsuran' },
            { value: 'SavingTransaction', label: 'Transaksi Simpanan' }
        ],
        optionLabel: 'label',
        optionValue: 'value'
    }
]

const selectedLog = ref(null)
const isModalOpen = ref(false)
const isLoading = ref(false)

const openModal = (log) => {
    selectedLog.value = log
    isModalOpen.value = true
}

const closeModal = () => {
    isModalOpen.value = false
    selectedLog.value = null
}

const applyFilters = () => {
    isLoading.value = true
    router.get(
        '/admin/logs',
        {
            search: filters.search || undefined,
            per_page: filters.per_page,
            event: filters.event || undefined,
            type: filters.type || undefined,
        },
        { 
            preserveState: true, 
            preserveScroll: true,
            replace: true,
            onFinish: () => { isLoading.value = false }
        }
    )
}

let timeout
watch(() => filters.search, () => {
    clearTimeout(timeout)
    timeout = setTimeout(applyFilters, 500)
})
watch(() => filters.per_page, applyFilters)
watch(() => filters.event, applyFilters)
watch(() => filters.type, applyFilters)

const formatDate = (dateString) => {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    }).format(date)
}

const diffKeys = computed(() => {
    if (!selectedLog.value) return []
    const oldKeys = Object.keys(selectedLog.value.old_values || {})
    const newKeys = Object.keys(selectedLog.value.new_values || {})
    return Array.from(new Set([...oldKeys, ...newKeys]))
})

const hasChanged = (key) => {
    if (!selectedLog.value) return false
    const oldVal = selectedLog.value.old_values?.[key]
    const newVal = selectedLog.value.new_values?.[key]
    return JSON.stringify(oldVal) !== JSON.stringify(newVal)
}

const typeMap = {
    'Member': 'Anggota',
    'Financing': 'Pembiayaan Murabahah',
    'InstallmentPaymentTransaction': 'Pembayaran Angsuran',
    'SavingTransaction': 'Transaksi Simpanan',
}

const formatAuditableType = (typeString) => {
    if (!typeString) return '-'
    const modelName = typeString.split('\\').pop()
    return typeMap[modelName] || modelName
}
</script>

<template>
    <Head title="Audit Trail (Log Aktivitas)" />

    <AdminLayout>
        <PageBreadcrumb :page-title="'Log Aktivitas'" :items="[{ name: 'Dashboard', link: '/admin/dashboard' }, { name: 'Log Aktivitas', link: '' }]" />

        <div class="mt-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-hidden relative z-10">
                <!-- Header Table -->
                <div class="flex justify-between items-center">
                    <div class="px-6 pt-6 pb-4 w-full">
                        <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100 mb-0.5">
                            Data Log Aktivitas
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">
                            Riwayat aktivitas pengguna pada sistem, mencakup penambahan, perubahan, dan penghapusan data.
                        </p>
                    </div>
                </div>

                <BaseFunctionality 
                    :per-page="filters.per_page" 
                    :search="filters.search" 
                    :filters="filters" 
                    :selects="filterSelects"
                    @update:per-page="val => filters.per_page = val" 
                    @update:search="val => filters.search = val"
                    @update:filters="newFilters => Object.assign(filters, newFilters)">
                </BaseFunctionality>

                <BaseTable :columns="columns" :data="logs.data" :is-loading="isLoading">
                    <template #cell-created_at="{ row }">
                        {{ formatDate(row.created_at) }}
                    </template>
                    <template #cell-user.name="{ row }">
                        {{ row.user?.name || 'Sistem' }}
                    </template>
                    <template #cell-event="{ row }">
                        <span
                            class="inline-flex items-center rounded-4xl px-4 py-2 text-sm font-medium ring-1 ring-inset"
                            :class="{
                                'bg-emerald-50 text-emerald-700 ring-emerald-600/20': row.event === 'created',
                                'bg-blue-50 text-blue-700 ring-blue-600/20': row.event === 'updated',
                                'bg-red-50 text-red-700 ring-red-600/20': row.event === 'deleted'
                            }"
                        >
                            {{ row.event.toUpperCase() }}
                        </span>
                    </template>
                    <template #cell-auditable_type="{ row }">
                        {{ formatAuditableType(row.auditable_type) }}
                    </template>
                    <template #cell-actions="{ row }">
                        <Button variant="outline" size="small" @click="openModal(row)">
                            <Icon icon="heroicons:eye" class="w-4 h-4 mr-1" />
                            Detail
                        </Button>
                    </template>
                </BaseTable>

                <Pagination :links="logs.links" :from="logs.from" :to="logs.to" :total="logs.total" />
            </div>
        </div>

        <div v-if="isModalOpen" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto" @click.self="closeModal">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0" @click.self="closeModal">
                    <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl sm:p-6">
                        <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                            <button type="button" class="rounded-md bg-white dark:bg-gray-800 text-gray-400 hover:text-gray-500 focus:outline-none" @click="closeModal">
                                <span class="sr-only">Tutup</span>
                                <Icon icon="heroicons:x-mark" class="h-6 w-6" />
                            </button>
                        </div>

                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white mb-4" id="modal-title">
                                    Detail Perubahan ({{ formatAuditableType(selectedLog?.auditable_type) }})
                                </h3>
                                
                                <div class="overflow-x-auto w-full border border-gray-200 dark:border-gray-700 rounded-md">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-1/3">Kolom</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-1/3">Nilai Lama</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-1/3">Nilai Baru</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                            <tr v-for="key in diffKeys" :key="key" :class="{'bg-yellow-50 dark:bg-yellow-900/20': hasChanged(key)}">
                                                <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-900 dark:text-gray-300 font-medium">
                                                    {{ key }}
                                                </td>
                                                <td class="whitespace-pre-wrap px-3 py-2 text-sm"
                                                    :class="{
                                                        'text-red-600 dark:text-red-400 line-through bg-red-100 dark:bg-red-900/30': hasChanged(key) && selectedLog.old_values?.[key] !== undefined,
                                                        'text-gray-500 dark:text-gray-400': !hasChanged(key)
                                                    }">
                                                    {{ selectedLog.old_values?.[key] ?? '-' }}
                                                </td>
                                                <td class="whitespace-pre-wrap px-3 py-2 text-sm"
                                                    :class="{
                                                        'text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30': hasChanged(key) && selectedLog.new_values?.[key] !== undefined,
                                                        'text-gray-500 dark:text-gray-400': !hasChanged(key)
                                                    }">
                                                    {{ selectedLog.new_values?.[key] ?? '-' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-4 flex gap-x-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">ID Referensi: <span class="font-mono">{{ selectedLog?.auditable_id }}</span></span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <Button variant="secondary" @click="closeModal">Tutup</Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
