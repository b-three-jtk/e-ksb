<template>
    <AdminLayout>
        <div class="flex flex-col px-20">
            <PageBreadcrumb :page-title="'Detail Simpanan'" />
            <div class="flex flex-col gap-6">
                <div class="card-layout flex justify-between">
                    <div class="flex gap-2 items-center">
                        <h1 class="font-semibold text-dark-text dark:text-white">No. Transaksi #{{ data.id }}
                        </h1>
                        <span :class="getStatusClass()">{{ data.status }}</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <button
                            class="inline-flex items-center gap-2 rounded-lg border bg-blue-accent px-6 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:text-white dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                            Lihat Bukti
                        </button>
                        <button
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                            Edit
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    <div class="card-layout col-span-1 lg:col-span-3">
                        <h2 class="card-title mb-4">Detail Transaksi</h2>
                        <ul class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Nominal Simpanan</span>
                                <span class="font-medium text-dark-text dark:text-white">Rp {{ data.amount
                                    }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Kategori Simpanan</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ data.saving_account.type
                                    }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Akad</span>
                                <span class="font-medium text-dark-text dark:text-white">
                                    {{ data.type === 'Simpanan Sukarela' ? 'Wadiah Yad Dhamanah' :
                                    'Musyarakah'
                                    }}
                                </span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Jenis Transaksi</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ data.type }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Tanggal Transaksi</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ data.transaction_date
                                    }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Metode Pembayaran</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ data.method }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Keterangan</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ data.description
                                    }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="flex flex-col col-span-1 lg:col-span-2 gap-2">
                        <div class="card-layout h-full flex flex-col gap-6">
                            <h1 class="card-title">Detail Anggota</h1>
                            <ul class="grid grid-cols-1 gap-6">
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nomor Anggota</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.saving_account.user.id }}</span>
                                </li>
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nama Anggota</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.saving_account.user.name }}</span>
                                </li>
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Status Keanggotaan</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.saving_account.user.status }}</span>
                                </li>
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Unit Kerja</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.saving_account.user.work_unit.name
                                        }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '../../../Layouts/Admin/Layout.vue'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'

const props = defineProps({
    data: { type: Object, required: true },
});

const getStatusClass = () => {
    const baseClass = 'font-semibold rounded-2xl px-6 text-sm py-1'

    switch (props.data.status) {
        case 'Selesai':
            return `${baseClass} text-green-600 bg-green-50`
        case 'Ditolak dengan alasan':
            return `${baseClass} text-yellow-600 bg-yellow-50`
        default:
            return `${baseClass} text-gray-600 bg-gray-50`
    }
}
</script>
