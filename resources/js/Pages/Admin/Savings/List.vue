<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { Icon } from '@iconify/vue'
import { ref, computed } from 'vue'

const activeTab = ref('semua')

const tabs = [
    { key: 'semua', label: 'Semua' },
    { key: 'permohonan', label: 'Permohonan Penarikan/Penyetoran' },
    { key: 'pokok', label: 'Simpanan Pokok' },
    { key: 'wajib', label: 'Simpanan Wajib' },
    { key: 'sukarela', label: 'Simpanan Sukarela' },
]

const summary = [
    {
        title: 'Total Kas',
        value: 'Rp.18,600,000',
        trend: '+2.5%',
        up: true,
    },
    {
        title: 'Total Simpanan Keluar',
        value: 'Rp.1,600,000',
        trend: '',
        up: false,
    },
    {
        title: 'Total Simpanan Masuk',
        value: 'Rp.8,600,000',
        trend: '',
        up: true,
    },
]

const transactions = Array.from({ length: 10 }, (_, i) => ({
    id: i + 1,
    no_transaksi: 'SW15120004',
    tanggal: '12/9/2025',
    anggota: '231511041 - Diana Larasati',
    nominal: i % 3 === 1 ? -20000 : 269000,
    produk: ['Sukarela', 'Wajib', 'Pokok'][i % 3],
    jenis: i % 4 === 0 ? 'Penarikan' : 'Penyetoran',
}))

const tableTitle = computed(() => {
    switch (activeTab.value) {
        case 'permohonan':
            return 'Data Permohonan Penarikan/Penyetoran Simpanan'
        case 'pokok':
            return 'Data Simpanan Pokok'
        case 'wajib':
            return 'Data Simpanan Wajib'
        case 'sukarela':
            return 'Data Simpanan Sukarela'
        default:
            return 'Data Simpanan'
    }
})
</script>

<template>
    <AdminLayout>
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-blue-900">
                Pengelolaan Simpanan
            </h1>

            <!-- Breadcrumb -->
            <div class="mt-2 sm:mt-0">
                <div class="flex items-center text-sm text-gray-500">
                    <Link href="/admin/dashboard" class="hover:text-blue-600">
                        Dashboard
                    </Link>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-blue-600 font-medium">Pengelolaan Simpanan</span>
                </div>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="bg-white rounded-xl p-6 mb-10 relative">
            <div class="flex justify-between items-center mb-6">
                <h2 class="font-semibold">Ringkasan</h2>

                <!-- Tombol Tambah -->
                <Link
                    href="#"
                    class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700"
                >
                    <Icon icon="mdi:plus" />
                    Tambah Transaksi
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div
                    v-for="item in summary"
                    :key="item.title"
                    class="border rounded-xl p-5"
                >
                    <p class="text-sm text-gray-500">
                        {{ item.title }}
                    </p>

                    <p class="text-xl font-bold mt-1">
                        {{ item.value }}
                    </p>

                    <div v-if="item.trend" class="flex items-center gap-2 mt-2">
                        <span
                            :class="item.up ? 'text-green-600' : 'text-red-600'"
                            class="text-sm font-medium"
                        >
                            {{ item.trend }}
                        </span>
                        <Icon
                            :icon="item.up ? 'mdi:arrow-up' : 'mdi:arrow-down'"
                            :class="item.up ? 'text-green-600' : 'text-red-600'"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="relative">
            <!-- Tabs -->
            <div class="flex gap-2 absolute -top-8 left-6 z-10">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    class="px-4 py-2 rounded-lg text-sm border transition"
                    :class="activeTab === tab.key
                        ? 'bg-white text-blue-600 font-medium shadow'
                        : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl shadow overflow-hidden relative z-10">
                <!-- Header Table -->
                <div class="px-6 pb-4 p-7">
                    <h3 class="font-semibold">
                        {{ tableTitle }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        Lacak transaksi simpanan koperasi di sini
                    </p>
                </div>

                <!-- Data Simpanan Per Halaman -->
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500">Tampilkan</span>
                        <select class="border rounded px-3 py-1 text-sm">
                            <option>10</option>
                            <option>25</option>
                            <option>50</option>
                        </select>
                        <span class="text-sm text-gray-500">data per halaman</span>
                    </div>

                    <div class="flex gap-3">
                        <div class="relative">
                            <input
                                type="text"
                                placeholder="Search..."
                                class="pl-10 pr-4 py-2 border rounded-lg text-sm"
                            />
                            <Icon
                                icon="mdi:magnify"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                            />
                        </div>

                        <button class="flex items-center gap-2 border rounded-lg px-4 py-2 text-sm">
                            <Icon icon="mdi:filter-variant" />
                            Filter
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-medium">No. Transaksi</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">Tanggal</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">Anggota</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">Nominal</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">Produk Simpanan</th>
                                <th class="px-6 py-3 text-left text-sm font-medium">Jenis Transaksi</th>
                                <th class="px-6 py-3 text-center text-sm font-medium">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y">
                            <tr
                                v-for="trx in transactions"
                                :key="trx.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-6 py-4 text-sm">
                                    {{ trx.no_transaksi }}
                                </td>

                                <td class="px-6 py-4 text-sm">
                                    {{ trx.tanggal }}
                                </td>

                                <td class="px-6 py-4 text-sm">
                                    {{ trx.anggota }}
                                </td>

                                <td
                                    class="px-6 py-4 text-sm font-medium"
                                    :class="trx.nominal < 0 ? 'text-red-500' : 'text-green-500'"
                                >
                                    Rp.{{ Math.abs(trx.nominal).toLocaleString('id-ID') }}
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 text-xs rounded-full"
                                        :class="{
                                            'bg-cyan-100 text-cyan-700': trx.produk === 'Sukarela',
                                            'bg-green-100 text-green-700': trx.produk === 'Wajib',
                                            'bg-pink-100 text-pink-700': trx.produk === 'Pokok',
                                        }"
                                    >
                                        {{ trx.produk }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-sm">
                                    {{ trx.jenis }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <button
                                        class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600"
                                    >
                                        <Icon icon="mdi:eye-outline" />
                                        Tinjau
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="p-6 flex justify-center gap-3 text-sm text-gray-600">
                <button class="px-4 py-2 border rounded-lg">
                    Sebelumnya
                </button>
                <span class="px-3 py-1 bg-blue-600 text-white rounded">
                    1
                </span>
                <span>2</span>
                <span>3</span>
                <button class="px-4 py-2 border rounded-lg">
                    Berikutnya
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
