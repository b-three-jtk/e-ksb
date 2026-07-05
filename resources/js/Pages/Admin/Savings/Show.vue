<script setup>
import AdminLayout from '../../../Layouts/Admin/Layout.vue'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'
import dateParser from '@/Composables/dateParser'
import moneyParser from '@/Composables/moneyParser'
import Button from '@/Components/Form/Button.vue';
import { ref } from 'vue'
import ModalDocument from '@/Components/ModalDocument.vue';

const props = defineProps({
    data: { type: Object, required: true },
    struk_simpanan: String,
});

const breadcrumbItems = [
    {name: 'Dashboard', link: '/admin'},
    {name: 'Pengelolaan Simpanan', link: '/admin/savings'},
    {name: 'Transaksi Simpanan'},
];

const modalRef = ref(null)
const openModalBukti = () => modalRef.value?.openModal()
</script>

<template>
    <AdminLayout title="Detail Transaksi Simpanan">
        <div class="flex flex-col">
            <PageBreadcrumb
                :page-title="'Detail Simpanan'" :items="breadcrumbItems" />
            <div class="flex flex-col gap-4">
                <div class="card-layout flex justify-between">
                    <div class="flex gap-2 items-center">
                        <h1 class="font-semibold text-dark-text dark:text-white">No. Transaksi #{{ data.kode_transaksi_simpanan }}
                        </h1>
                    </div>
                    <div v-if="data.struk_simpanan" class="flex items-center gap-4">
                        <Button @click="openModalBukti()" variant="primary">Lihat Bukti</Button>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    <div class="flex flex-col justify-end col-span-1 lg:col-span-3">
                        <div class="card-layout col-span-1 lg:col-span-3 pb-40!">
                            <h2 class="card-title mb-4">Detail Transaksi</h2>
                            <ul class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nominal Simpanan</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ moneyParser(data.nominal_simpanan)
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Kategori Simpanan</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.saving_account.jenis_simpanan
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Akad</span>
                                    <span class="font-medium text-dark-text dark:text-white">
                                        Wadiah Yad Dhamanah
                                    </span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Jenis Transaksi</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.tipe_transaksi }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Tanggal Transaksi</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        dateParser(data.tgl_transaksi)
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Metode Pembayaran</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.metode_pembayaran_simpanan }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Keterangan</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.deskripsi_simpanan ?? '-'
                                    }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="flex flex-col col-span-1 lg:col-span-2 gap-2">
                        <div class="card-layout h-fit flex flex-col gap-6">
                            <h1 class="card-title">Detail Anggota</h1>
                            <ul class="grid grid-cols-1 gap-6">
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nomor Anggota</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.saving_account.anggota.user.kode_pengguna }}</span>
                                </li>
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nama Anggota</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.saving_account.anggota.user.nama }}</span>
                                </li>
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Status Keanggotaan</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.saving_account.anggota.user.status }}</span>
                                </li>
                            </ul>
                        </div>
                        <div v-if="data.account" class="card-layout flex flex-col pb-12.5! gap-6">
                            <h1 class="card-title">Informasi Rekening</h1>
                            <ul class="grid grid-cols-1 gap-6">
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nomor Rekening</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.member_bank_account?.no_rekening }}</span>
                                </li>
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nama Pemilik Rekening</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.member_bank_account?.atas_nama }}</span>
                                </li>
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nama Bank</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.member_bank_account?.nama_bank }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ModalDocument ref="modalRef" modal-id="buktiModal" title="Bukti Transaksi Simpanan" :name="struk_simpanan" :attachment="struk_simpanan" />
    </AdminLayout>
</template>
