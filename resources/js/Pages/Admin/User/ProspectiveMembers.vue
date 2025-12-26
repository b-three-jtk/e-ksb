<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { reactive, watch } from 'vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    prospectiveMembers: Object,
    filters: Object,
    workUnits: Array,
    title: String,
})

const filters = reactive({
    search: props.filters.search ?? '',
    work_unit_id: props.filters.work_unit_id ?? '',
    per_page: props.filters.per_page ?? 10,
})

const applyFilters = () => {
    router.get(
        '/admin/verifikasi',
        {
            search: filters.search || undefined,
            work_unit_id: filters.work_unit_id || undefined,
            per_page: filters.per_page,
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
watch(() => filters.work_unit_id, applyFilters)
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
                    <select v-model.number="filters.per_page" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                    <span class="text-sm text-gray-500">data per halaman</span>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative">
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Cari nama, NIK, email..."
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    </div>

                    <select v-model="filters.work_unit_id" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Unit Kerja</option>
                        <option v-for="unit in workUnits" :key="unit.id" :value="unit.id">
                            {{ unit.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">NIK</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Unit Kerja</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="(member, index) in prospectiveMembers.data" 
                            :key="member.id" 
                            class="hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <td class="px-6 py-4 text-sm">
                                {{ (prospectiveMembers.current_page - 1) * prospectiveMembers.per_page + index + 1 }}
                            </td>
                            <td class="px-6 py-4 text-sm">{{ member.name }}</td>
                            <td class="px-6 py-4 text-sm">{{ member.nik }}</td>
                            <td class="px-6 py-4 text-sm">{{ member.unit_kerja }}</td>
                            <td class="px-6 py-4 text-sm">{{ member.email }}</td>
                            <td class="px-6 py-4">
                                <Link :href="`/admin/users/show/${member.id}`" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium transition inline-block">
                                    Tinjau
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="prospectiveMembers.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada calon anggota yang perlu diverifikasi.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="prospectiveMembers.total > 0"
                class="p-6 flex justify-center gap-1 flex-wrap text-sm"
                >
                    <template v-for="link in prospectiveMembers.links" :key="link.label">
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
