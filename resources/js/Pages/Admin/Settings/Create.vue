<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue';
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue';
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue';
import { ref } from 'vue';

const tab = ref('simpanan');

const tabs = [
    { key: 'simpanan', label: 'Simpanan' },
    { key: 'rekening', label: 'Rekening' },
    { key: 'pembiayaan', label: 'Pembiayaan' },
]

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengaturan Umum' },
];
</script>

<template>
    <AdminLayout title="Pengaturan Umum">
        <PageBreadcrumb page-title="Pengaturan Umum" :items="breadcrumbItems" />
        <div class="relative mt-12">
            <div class="flex gap-2 absolute -top-8 left-6 z-10">
                <button v-for="t in tabs" :key="t.key" @click="tab = t.key"
                    class="font-head px-4 py-2 rounded-lg text-sm border transition border-gray-200 dark:border-slate-700"
                    :class="tab === t.key
                        ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 font-medium shadow'
                        : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-600'">
                    {{ t.label }}
                </button>
            </div>
            <div class="card-layout overflow-hidden relative z-10">
                <ul v-if="tab === 'simpanan'">
                    <li class="grid grid-cols-5 py-8 justify-between border-b border-stroke">
                        <div class="flex flex-col col-span-3">
                            <h1 class="card-title">Nominal Simpanan Pokok</h1>
                            <p class="font-body text-gray-400">Merupakan jumlah simpanan pokok yang harus dibayarkan
                                oleh
                                anggota.</p>
                        </div>
                        <div class="w-full col-span-2">
                            <BaseInputAdmin type="text" isMoney placeholder="Masukkan nominal simpanan pokok" />
                        </div>
                    </li>
                    <li class="grid grid-cols-5 py-8 justify-between border-b border-stroke">
                        <div class="flex flex-col col-span-3">
                            <h1 class="card-title">Nominal Simpanan Wajib</h1>
                            <p class="font-body text-gray-400">Merupakan jumlah simpanan wajib yang harus dibayarkan
                                oleh
                                anggota.</p>
                        </div>
                        <div class="w-full col-span-2">
                            <BaseInputAdmin type="text" isMoney placeholder="Masukkan nominal simpanan wajib" />
                        </div>
                    </li>
                    <li class="grid grid-cols-5 py-8 justify-between">
                        <div class="flex flex-col col-span-3">
                            <h1 class="card-title">Jatuh Tempo Simpanan Wajib</h1>
                            <p class="font-body text-gray-400">Merupakan tanggal jatuh tempo simpanan wajib.</p>
                        </div>
                        <div class="w-full col-span-2">
                            <BaseInputAdmin type="date" />
                        </div>
                    </li>
                </ul>
                <div v-if="tab === 'rekening'">
                    <h1 class="card-title">Rekening Koperasi</h1>
                    <div class="card-layout mt-6">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-500">
                                    <th class="text-left py-5 px-2">
                                        <p class="text-dark-text dark:text-gray-400">
                                            Bank
                                        </p>
                                    </th>
                                    <th class="text-left py-5 px-2">
                                        <p class="text-dark-text dark:text-gray-400">
                                            Nomor Rekening
                                        </p>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-t border-gray-100 dark:border-gray-500">
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            Harga Perolehan Objek Pembiayaan
                                        </p>
                                    </td>
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            P
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <ul v-if="tab === 'pembiayaan'">
                    <li class="grid grid-cols-5 py-8 justify-between">
                        <div class="flex flex-col col-span-3">
                            <h1 class="card-title">Margin Pembiayaan</h1>
                            <p class="font-body text-gray-400">Merupakan persenan margin pembiayaan.</p>
                        </div>
                        <div class="w-full col-span-2 flex items-center gap-2 self-right justify-end">
                            <BaseInputAdmin type="number" />
                            % setiap objek pembiayaan
                        </div>
                    </li>
                </ul>
            </div>
        </div>

    </AdminLayout>
</template>
