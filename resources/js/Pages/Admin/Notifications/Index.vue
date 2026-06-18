<script setup lang="ts">
import { reactive, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import AdminLayout from '../../../Layouts/Admin/Layout.vue'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'
import BaseFunctionality from '../../../Components/Table/BaseFunctionality.vue'
import BaseTable from '../../../Components/Table/BaseTable.vue'
import Pagination from '../../../Components/Table/Pagination.vue'
import BaseSelect from '../../../Components/Form/BaseSelect.vue' 
import BaseInput from '../../../Components/Form/BaseInput.vue'

interface NotificationItem {
    id: number
    member_name: string
    title: string
    message: string
    phone_number: string | null
    notification_type: string
    reminder_type: string
    status: string
    is_read: boolean
    scheduled_at: string | null
    sent_at: string | null
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface NotificationPagination {
    data: NotificationItem[]
    current_page: number
    per_page: number
    total: number
    last_page: number
    links: PaginationLink[]
}

interface Filters {
    periode?: string
    notification_type?: string
    status?: string
    is_read?: string
    search?: string
    per_page?: number
}

const props = defineProps<{
    notifications: NotificationPagination
    filters: Filters
}>()

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Monitoring Notifikasi', link: '' },
]

const columns = [
    { key: 'no', label: 'No' },
    { key: 'member_name', label: 'Nama Anggota' },
    { key: 'title', label: 'Judul' },
    { key: 'notification_type', label: 'Jenis Notifikasi' },
    { key: 'reminder_type', label: 'Reminder' },
    { key: 'status', label: 'Status Pengiriman' },
    { key: 'is_read', label: 'Status Dibaca' },
    { key: 'sent_at', label: 'Dikirim Pada' },
    { key: 'actions', label: 'Aksi' },
]

const filters = reactive({
    periode: props.filters.periode ?? '',
    notification_type: props.filters.notification_type ?? '',
    status: props.filters.status ?? '',
    is_read: props.filters.is_read ?? '',
    search: props.filters.search ?? '',
    per_page: props.filters.per_page ?? 10,
})

const applyFilters = () => {
    router.get(
        '/admin/notifications',
        {
            periode: filters.periode || undefined,
            notification_type: filters.notification_type || undefined,
            status: filters.status || undefined,
            is_read: filters.is_read || undefined,
            search: filters.search || undefined,
            per_page: filters.per_page,
        },
        {
            preserveState: true,
            replace: true,
        }
    )
}

watch(() => filters.periode, applyFilters)
watch(() => filters.notification_type, applyFilters)
watch(() => filters.status, applyFilters)
watch(() => filters.is_read, applyFilters)

let timeout: ReturnType<typeof setTimeout>

watch(() => filters.search, () => {
    clearTimeout(timeout)
    timeout = setTimeout(applyFilters, 500)
})

const getStatusClass = (status: string) => {
    const base = 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold'
    switch (status) {
        case 'terkirim': return `${base} bg-green-100 text-green-700`
        case 'draf': return `${base} bg-yellow-100 text-yellow-700`
        case 'gagal_kirim': return `${base} bg-red-100 text-red-700`
        default: return `${base} bg-gray-100 text-gray-700`
    }
}

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'draf': return 'Draf'
        case 'terkirim': return 'Terkirim'
        case 'gagal_kirim': return 'Gagal Kirim'
        default: return status
    }
}

const getReadStatusClass = (isRead: boolean) => {
    return isRead
        ? 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-green-100 text-green-700'
        : 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-orange-100 text-orange-700'
}

const normalizeWhatsAppNumber = (phoneNumber: string) => {
    const digits = String(phoneNumber || '').replace(/\D/g, '')
    if (!digits) return ''
    if (digits.startsWith('62')) return digits
    if (digits.startsWith('0')) return `62${digits.slice(1)}`
    return digits
}

const createWhatsAppUrl = (phoneNumber: string, message: string) => {
    const waNumber = normalizeWhatsAppNumber(phoneNumber)
    const text = `Assalamualaikum, kami dari KSB ingin memberitahukan ${message || ''}`
    return waNumber ? `https://wa.me/${waNumber}?text=${encodeURIComponent(text)}` : '#'
}
</script>

<template>
    <AdminLayout title="Monitoring Notifikasi">
        <PageBreadcrumb page-title="Monitoring Notifikasi" :items="breadcrumbItems" />

        <div class="space-y-6">

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Filter Data
                    </h2>
                </div>

                <div class="p-6">
                    <div class="grid gap-4 md:grid-cols-5 items-end">
                        
                        <BaseInput
                            v-model="filters.periode"
                            type="month"
                            label="Periode"
                        />

                        <BaseSelect
                            v-model="filters.notification_type"
                            label="Jenis Notifikasi"
                        >
                            <option value="">Semua Jenis</option>
                            <option value="mandatory_saving">Simpanan Wajib</option>
                            <option value="installment">Angsuran Pembiayaan</option>
                        </BaseSelect>

                        <BaseSelect
                            v-model="filters.status"
                            label="Status Pengiriman"
                        >
                            <option value="">Semua Status</option>
                            <option value="draf">Draf</option>
                            <option value="terkirim">Terkirim</option>
                            <option value="gagal_kirim">Gagal Kirim</option>
                        </BaseSelect>

                        <BaseSelect
                            v-model="filters.is_read"
                            label="Status Dibaca"
                        >
                            <option value="">Semua</option>
                            <option value="0">Belum Dibaca</option>
                            <option value="1">Sudah Dibaca</option>
                        </BaseSelect>

                        <BaseInput
                            v-model="filters.search"
                            type="text"
                            label="Cari Nama Anggota"
                        />

                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Data Notifikasi
                    </h2>
                </div>

                <BaseFunctionality
                    :show-search="false"
                    :show-search-button="false"
                    :show-border="true"
                    :per-page="filters.per_page"
                    @update:perPage="
                        val => {
                            filters.per_page = val
                            applyFilters()
                        }
                    "
                />

                <BaseTable
                    :columns="columns"
                    :data="notifications.data.map((item, idx) => ({
                        ...item,
                        no: ((notifications.current_page - 1) * notifications.per_page) + idx + 1
                    }))"
                >
                    <template #cell-notification_type="{ row }">
                        {{
                            row.notification_type === 'mandatory_saving'
                                ? 'Simpanan Wajib'
                                : 'Angsuran Pembiayaan'
                        }}
                    </template>

                    <template #cell-status="{ row }">
                        <span :class="getStatusClass(row.status)">
                            {{ getStatusLabel(row.status) }}
                        </span>
                    </template>

                    <template #cell-is_read="{ row }">
                        <span :class="getReadStatusClass(row.is_read)">
                            {{ row.is_read ? 'Sudah Dibaca' : 'Belum Dibaca' }}
                        </span>
                    </template>

                    <template #cell-scheduled_at="{ row }">
                        {{ row.scheduled_at ?? '-' }}
                    </template>

                    <template #cell-sent_at="{ row }">
                        {{ row.sent_at ?? '-' }}
                    </template>

                    <template #cell-actions="{ row }">
                        <a
                            v-if="row.phone_number"
                            :href="createWhatsAppUrl(row.phone_number, row.message)"
                            class="inline-flex items-center justify-center rounded-lg bg-green-500 px-3 py-2 text-sm font-medium text-white hover:bg-green-600"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Icon icon="mdi:whatsapp" class="mr-2 h-4 w-4" />
                            WhatsApp
                        </a>
                        <span
                            v-else
                            class="inline-flex items-center justify-center rounded-lg bg-gray-200 px-3 py-2 text-sm font-medium text-gray-600"
                        >
                            -
                        </span>
                    </template>
                </BaseTable>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <Pagination
                        :links="notifications.links"
                        :total="notifications.total"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>