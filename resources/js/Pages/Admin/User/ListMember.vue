<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { reactive, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
    members: Object,
    filters: Object,
    statuses: Array,
    summary: Object,
})

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
        case 'Nonaktif':
            return 'bg-red-100 text-red-700 border border-red-200'
        case 'Mengundurkan Diri':
            return 'bg-orange-100 text-orange-700 border border-orange-200'
        case 'Menunggu Verifikasi':
            return 'bg-yellow-100 text-yellow-700 border border-yellow-200'
        case 'Ditolak':
            return 'bg-gray-100 text-gray-700 border border-gray-200'
        default:
            return 'bg-gray-100 text-gray-600 border border-gray-200'
    }
}
</script>

<template>
    <AdminLayout>
        <div class="font-body flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <!-- Title -->
            <div>
                <h1 class="font-heading text-2xl font-bold text-blue-900 dark:text-white">
                    Pengelolaan Data Anggota
                </h1>
            </div>

            <!-- Breadcrumb -->
            <div class="mt-2 sm:mt-0">
                <div class="flex items-center text-sm text-gray-500">
                    <Link href="/admin/dashboard" class="hover:text-blue-600">
                        Dashboard
                    </Link>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-blue-600 font-medium">Anggota</span>
                </div>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 mb-6">
            <h2 class="font-semibold mb-4 dark:text-gray-100">Ringkasan</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="border rounded-xl p-5 dark:text-gray-100">
                    <div class="text-2xl font-bold">{{ summary.active }}</div>
                    <div class="text-sm text-gray-500">Jumlah Anggota Aktif</div>
                </div>

                <div class="border rounded-xl p-5 dark:text-gray-100">
                    <div class="text-2xl font-bold">{{ summary.new_this_month }}</div>
                    <div class="text-sm text-gray-500">Anggota Baru Bulan Ini</div>
                </div>

                <div class="border rounded-xl p-5 dark:text-gray-100">
                    <div class="text-2xl font-bold">{{ summary.in_review }}</div>
                    <div class="text-sm text-gray-500">Menunggu Verifikasi</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <!-- Card Header -->
            <div class="flex justify-between items-center p-6 border-b">
                <div>
                    <h2 class="font-heading text-lg font-semibold text-gray-900 dark:text-gray-100">Data Anggota</h2>
                </div>
            </div>

            <!-- Filter & Search -->
            <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 dark:text-gray-100">
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500">Tampilkan</span>
                    <select 
                        v-model="filters.per_page"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>

                    </select>
                    <span class="text-sm text-gray-500">data per halaman</span>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-b">
                    <div class="relative">
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Search..."
                            class="pl-10 pr-4 py-2 border rounded-lg text-sm w-64"
                        />
                        <Icon
                            icon="mdi:magnify"
                            class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                        />
                    </div>

                    <select
                        v-model="filters.status"
                        class="border rounded-lg px-3 py-2 text-sm"
                    >
                        <option value="" class="dark:text-gray-800">Semua Status</option>
                        <option v-for="status in statuses" :key="status" :value="status" class="dark:text-gray-800">
                            {{ status }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table
                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700 dark:text-gray-100">
                        <tr>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">No</th>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">No Anggota</th>
                            <th
                                @click="toggleSort('name')"
                                class="cursor-pointer select-none font-heading px-6 py-3 text-left text-sm font-medium"
                            >
                                <div class="flex items-center gap-1">
                                    Profil Anggota
                                    <Icon
                                        v-if="filters.sort_by === 'name' && filters.sort_dir === 'asc'"
                                        icon="tabler:chevron-down"
                                        class="w-4 h-4"
                                    />
                                    <Icon
                                        v-else-if="filters.sort_by === 'name' && filters.sort_dir === 'desc'"
                                        icon="tabler:chevron-up"
                                        class="w-4 h-4"
                                    />
                                    <Icon
                                        v-else
                                        icon="tabler:chevrons-up-down"
                                        class="w-4 h-4 opacity-40"
                                    />
                                </div>
                            </th>
                            <th
                                @click="toggleSort('joined_date')"
                                class="cursor-pointer select-none font-heading px-6 py-3 text-left text-sm font-medium"
                            >
                                <div class="flex items-center gap-1">
                                    Tanggal Bergabung
                                    <Icon
                                        v-if="filters.sort_by === 'joined_date' && filters.sort_dir === 'asc'"
                                        icon="tabler:chevron-down"
                                        class="w-4 h-4"
                                    />
                                    <Icon
                                        v-else-if="filters.sort_by === 'joined_date' && filters.sort_dir === 'desc'"
                                        icon="tabler:chevron-up"
                                        class="w-4 h-4"
                                    />
                                    <Icon
                                        v-else
                                        icon="tabler:chevrons-up-down"
                                        class="w-4 h-4 opacity-40"
                                    />
                                </div>
                            </th>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">Kontak</th>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">Total Simpanan</th>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">Status</th>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">Aksi</th>
                        </tr>
                    </thead>

                    <tbody
                        class="font-body bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 ">
                        <tr
                            v-for="(member, index) in members.data" 
                            :key="member.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 text-sm dark:text-gray-300">
                                {{ (members.current_page - 1) * members.per_page + index + 1 }}
                            </td>

                            <td class="px-6 py-4 text-sm dark:text-gray-300">
                                {{ member.no_anggota }}
                            </td>

                            <td class="px-6 py-4 flex items-center gap-3">
                                <img
                                    :src="member.avatar"
                                    class="w-9 h-9 rounded-full"
                                />
                                <span class="font-body text-sm text-gray-800 dark:text-gray-300">
                                    {{ member.name }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-sm dark:text-gray-300">
                                {{ member.joined_at }}
                            </td>

                            <td class="font-body text-sm px-6 py-4 flex items-center gap-2 dark:text-gray-300">
                                <Icon icon="mdi:whatsapp" class="text-green-500" />
                                {{ member.phone }}
                            </td>

                            <td class="px-6 py-4 text-sm dark:text-gray-300">
                                {{ member.total_simpanan }}
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="px-3 py-1 text-xs rounded-full font-medium whitespace-nowrap"
                                    :class="statusClass(member.status)"
                                >
                                    {{ member.status }}
                                </span>
                            </td>

                            <td class="px-6 py-4 dark:text-gray-300">
                                <div class="flex justify-center gap-3">
                                    <!-- Edit -->
                                    <button
                                        class="text-gray-500 hover:text-blue-600 transition"
                                        title="Edit"
                                    >
                                        <Icon icon="mdi:pencil-outline" class="w-5 h-5" />
                                    </button>

                                    <!-- View -->
                                    <Link
                                        :href="`/admin/users/show/${member.id}`"
                                        class="text-gray-500 hover:text-blue-600"
                                    >
                                        <Icon icon="mdi:eye-outline" class="w-5 h-5" />
                                    </Link>

                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="members.total > 0"
                class="p-6 flex justify-center gap-1 flex-wrap text-sm"
            >
                <template v-for="link in members.links" :key="link.label">
                    <span
                        v-if="!link.url"
                        class="px-3 py-1 text-gray-400"
                        v-html="link.label"
                    />
                    <Link
                        v-else
                        :href="link.url"
                        preserve-scroll
                        preserve-state
                        class="px-3 py-1 border rounded"
                        :class="{ 'bg-blue-600 text-white': link.active }"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AdminLayout>
</template>