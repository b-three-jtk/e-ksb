<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { Icon } from '@iconify/vue'

// Data statis dulu (nanti dari backend)
const admins = [
    {
        id: 1,
        nik: 'SW15120004',
        name: 'Diana Larasati',
        email: 'dianalarasati@gmail.com',
        posisi: 'Bendahara',
        status: 'Aktif',
        avatar: 'https://i.pravatar.cc/40?img=5',
    },
    {
        id: 1,
        nik: 'SW15120009',
        name: 'Alvaro Cleosanda',
        email: 'alvarooo@gmail.com',
        posisi: 'Administratif',
        status: 'Aktif',
        avatar: 'https://i.pravatar.cc/40?img=5',
    },
    {
        id: 1,
        nik: 'SW15120006',
        name: 'Hekal Mlanaf',
        email: 'kallll@gmail.com',
        posisi: 'Administrasi',
        status: 'Aktif',
        avatar: 'https://i.pravatar.cc/40?img=5',
    },
]
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
                    href="/admin/admins/create"
                    class="font-heading bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700 text-sm"
                >
                    + Tambah Admin
                </Link>
            </div>

            <!-- Filter & Search -->
            <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500">Tampilkan</span>
                    <select class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                            type="text"
                            placeholder="Search..."
                            class="pl-10 pr-4 py-2 border rounded-lg text-sm w-64"
                        />
                        <Icon
                            icon="mdi:magnify"
                            class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                        />
                    </div>

                    <button class="flex items-center gap-2 border rounded-lg px-4 py-2 text-sm">
                        <Icon icon="mdi:filter-variant" class="w-4 h-4" />
                        Filter
                    </button>
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
                            v-for="(admin, index) in admins" 
                            :key="admin.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 text-sm">
                                {{ index + 1 }}
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
                                    class="px-4 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium"
                                >
                                    Aktif
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
                                    <button
                                        class="text-gray-500 hover:text-blue-600 transition"
                                        title="Detail"
                                    >
                                        <Icon icon="mdi:eye-outline" class="w-5 h-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                class="p-6 flex justify-center items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                <button class="px-4 py-2 border rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    Sebelumnya
                </button>
                <span class="px-3 py-1 bg-blue-600 text-white rounded">
                    1
                </span>
                <span>2</span>
                <span>3</span>
                <button class="px-4 py-2 border rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    Berikutnya
                </button>
            </div>
        </div>
    </AdminLayout>
</template>