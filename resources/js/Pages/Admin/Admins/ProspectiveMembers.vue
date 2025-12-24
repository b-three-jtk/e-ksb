<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'

// Data statis dulu (nanti dari backend)
const prospectiveMembers = [
    {
        id: 1,
        name: 'Diana Larasati',
        nik: 'SW15120004',
        unit_kerja: 'JTK',
        email: 'dianalarasati@gmail.com',
    },
    {
        id: 2,
        name: 'Ahmad Rizki',
        nik: 'SW15120005',
        unit_kerja: 'JTK',
        email: 'ahmad@example.com',
    },
    {
        id: 3,
        name: 'Siti Nurhaliza',
        nik: 'SW15120006',
        unit_kerja: 'JTK',
        email: 'siti@example.com',
    },
]
</script>

<template>
    <AdminLayout>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <!-- Title -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Verifikasi Calon Anggota
                </h1>
            </div>

            <!-- Breadcrumb -->
            <div class="mt-2 sm:mt-0">
                <div class="flex items-center text-sm text-gray-500">
                    <Link href="/admin/dashboard" class="hover:text-blue-600">
                        Dashboard
                    </Link>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-blue-600 font-medium">Verifikasi</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <!-- Header -->
            <div
                class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Data Calon Anggota
                </h2>
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

                <div class="flex items-center gap-3">
                    <div class="relative">
                        <input
                            type="text"
                            placeholder="Search..."
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    </div>
                    <button
                        class="flex items-center gap-2 border border-gray-300 rounded-lg px-4 py-2 text-sm hover:bg-gray-50 transition"
                    >
                        <FunnelIcon class="w-4 h-4" />
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
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">
                                No
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">
                                Nama
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">
                                NIK
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">
                                Unit Kerja
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr
                            v-for="(member, index) in prospectiveMembers"
                            :key="member.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 text-sm">
                                {{ index + 1 }}
                            </td>

                            <td class="px-6 py-4 text-sm">
                                {{ member.name }}
                            </td>

                            <td class="px-6 py-4 text-sm">
                                {{ member.nik }}
                            </td>

                            <td class="px-6 py-4 text-sm">
                                {{ member.unit_kerja }}
                            </td>

                            <td class="px-6 py-4 text-sm">
                                {{ member.email }}
                            </td>

                            <td class="px-6 py-4">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium transition">
                                    Tinjau
                                </button>
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
