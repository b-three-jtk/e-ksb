<script setup lang="ts">
import { reactive, watch, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import BaseLayout from '../../../Layouts/Base.vue'
import BaseFunctionality from '../../../Components/Table/BaseFunctionality.vue'
import Pagination from '../../../Components/Table/Pagination.vue'
import BaseTable from '../../../Components/Table/BaseTable.vue'

defineOptions({
    layout: (h: any, page: any) =>
        h(BaseLayout, { title: 'Notifikasi Saya' }, () => page),
})

interface NotificationItem {
    id: number
    title: string
    message: string
    notification_type: string
    reminder_type: string
    is_read: boolean
    scheduled_at: string | null
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

interface NotificationFilters {
    unread?: number
    per_page?: number
}

const props = withDefaults(
    defineProps<{
        notifications: NotificationPagination
        filters?: NotificationFilters
    }>(),
    {
        filters: () => ({
            unread: 0,
            per_page: 10,
        }),
    }
)

const filters = reactive({
    unread: props.filters.unread ? 1 : 0,
    per_page: props.filters.per_page ?? 10,
})

const columns = [
    { key: 'title', label: 'Judul Notifikasi' },
    { key: 'notification_type', label: 'Jenis' },
    { key: 'reminder_type', label: 'Reminder' },
    { key: 'is_read', label: 'Status' },
    { key: 'scheduled_at', label: 'Scheduled' },
    { key: 'actions', label: 'Aksi' },
]

const tableData = computed(() =>
    props.notifications.data.map((item) => ({
        ...item,
        notification_type:
            item.notification_type === 'mandatory_saving'
                ? 'Simpanan Wajib'
                : 'Angsuran Pembiayaan',
    }))
)

const applyFilters = () => {
    router.get(
        '/user/notifications',
        {
            unread: filters.unread ? 1 : 0,
            per_page: filters.per_page,
        },
        {
            preserveState: true,
            replace: true,
        }
    )
}

const markAllAsRead = () => {
    router.post(
        '/user/notifications/mark-all-read',
        {},
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

watch(
    () => filters.unread,
    applyFilters
)

const getReadStatusClass = (isRead: boolean) => {
    return isRead
        ? 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-green-100 text-green-700'
        : 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-orange-100 text-orange-700'
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 pt-24 pb-12 max-w-8xl px-4 sm:px-6 lg:px-8">
        <div class="space-y-6 p-6">

            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold font-head text-green-800 dark:text-green-500 mb-2">
                        Notifikasi Saya
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Lihat semua notifikasi terbaru dan kelola status baca notifikasi Anda.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        @click="markAllAsRead"
                        class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 transition"
                    >
                        Tandai Semua Dibaca
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-xl font-head font-semibold text-gray-900 dark:text-gray-100">
                        Riwayat Notifikasi
                    </h2>

                    <label class="inline-flex items-center gap-3">
                        <input
                            v-model="filters.unread"
                            type="checkbox"
                            class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                        />
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            Tampilkan hanya yang belum dibaca
                        </span>
                    </label>
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

                <div class="px-6 py-6">
                    <BaseTable
                        :columns="columns"
                        :data="tableData"
                    >
                        <template #cell-title="{ row }">
                            <div class="max-w-md">
                                <div class="font-medium text-green-700 dark:text-green-400">
                                    {{ row.title }}
                                </div>
                                

                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ row.message }}
                                </div>
                            </div>
                        </template>

                        <template #cell-is_read="{ row }">
                            <span :class="getReadStatusClass(row.is_read)">
                                {{ row.is_read ? 'Dibaca' : 'Belum Dibaca' }}
                            </span>
                        </template>

                        <template #cell-scheduled_at="{ row }">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ row.scheduled_at ?? '-' }}
                            </span>
                        </template>

                        <template #cell-actions="{ row }">
                            <Link
                                :href="`/user/notifications/${row.id}`"
                                class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700 transition-colors"
                            >
                                Detail
                            </Link>
                        </template>
                    </BaseTable>

                    <Pagination
                        :links="notifications.links"
                        :total="notifications.total"
                    />
                </div>
            </div>
        </div>
    </div>
</template>