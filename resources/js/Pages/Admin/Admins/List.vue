<script setup>
import { Link, router } from '@inertiajs/vue3'
import { reactive, watch } from 'vue'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
    admins: Object,
    filters: Object,
    roles: Array,
})

const filters = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? '',
    role: props.filters?.role ?? '',
    perPage: props.filters?.perPage ?? 10,
})

watch(filters, () => {
    router.get(
        '/admin/admins',
        filters,
        { preserveState: true, replace: true }
    )
})
</script>

<template>
    <AdminLayout>
        <div class="font-body flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <!-- Title -->
            <div>
                <h1 class="font-heading text-2xl font-bold text-blue-900 dark:text-white">
                    Pengelolaan Admin
                </h1>
            </div>

            <!-- Breadcrumb -->
            <div class="mt-2 sm:mt-0">
                <div class="flex items-center text-sm text-gray-500">
                    <Link href="/admin/dashboard" class="hover:text-blue-600">
                        Dashboard
                    </Link>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-blue-600 font-medium">Pengelolaan Admin</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <!-- Card Header -->
            <div class="flex justify-between items-center p-6 border-b">
                <div>
                    <h2 class="font-heading text-lg font-semibold text-gray-900">Data Admin</h2>
                </div>

                <Link
                    href="/admin/create"
                    class="font-heading bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700 text-sm"
                >
                    + Tambah Admin
                </Link>
            </div>

            <!-- Filter & Search -->
            <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500">Tampilkan</span>
                    <select 
                        v-model="filters.perPage"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option>10</option>
                        <option>25</option>
                        <option>50</option>
                        <option>100</option>
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

                    <select v-model="filters.status" class="border rounded-md px-3 py-2 text-sm">
                        <option value="">Semua Status</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Tidak Aktif">Tidak Aktif</option>
                    </select>

                    <select v-model="filters.role" class="border rounded-md px-3 py-2 text-sm">
                        <option value="">Semua Posisi</option>
                        <option
                            v-for="role in roles"
                            :key="role"
                            :value="role"
                        >
                            {{ role }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table
                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">No</th>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">NIK</th>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">Profil Anggota</th>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">Email</th>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">Posisi</th>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">Status</th>
                            <th class="font-heading px-6 py-3 text-left text-sm font-medium">Aksi</th>
                        </tr>
                    </thead>

                    <tbody
                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="(admin, index) in admins.data" 
                            :key="admin.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 text-sm">
                                {{ admins.from + index }}
                            </td>

                            <td class="px-6 py-4 text-sm">
                                {{ admin.nik }}
                            </td>

                            <td class="px-6 py-4 flex items-center gap-3">
                                <img
                                    :src="admin.avatar"
                                    class="w-9 h-9 rounded-full"
                                />
                                <span class="font-medium text-gray-800">
                                    {{ admin.name }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-sm">
                                {{ admin.email }}
                            </td>

                            <td class="px-6 py-4 text-sm">
                                {{ admin.posisi }}
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="px-4 py-1 rounded-full text-xs"
                                    :class="admin.status === 'Aktif'
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-gray-200 text-gray-600'"
                                >
                                    {{ admin.status }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
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
                                        :href="`/admin/show/${admin.id}`"
                                        class="text-gray-500 hover:text-blue-600"
                                    >
                                        <Icon icon="mdi:eye-outline" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-6 flex gap-2 justify-center">
                <Link
                    v-for="link in admins.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    class="px-3 py-1 border rounded"
                    :class="{
                        'bg-blue-600 text-white': link.active,
                        'text-gray-400': !link.url
                    }"
                />
            </div>
        </div>
    </AdminLayout>
</template>