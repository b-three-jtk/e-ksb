<template>
    <AdminLayout>
        <div class="flex flex-col px-20">
            <PageBreadcrumb
                :page-title="data.status != 'Belum Ditinjau' ? 'Detail Simpanan' : 'Validasi Permohonan Simpanan'" />
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
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                    <div class="flex flex-col justify-end col-span-1 lg:col-span-3">
                        <div class="card-layout col-span-1 lg:col-span-3 pb-40!">
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
                                        Wadiah Yad Dhamanah
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
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.description ?? '-'
                                        }}</span>
                                </li>
                            </ul>
                        </div>
                        <div v-if="data.status == 'Belum Ditinjau'" class="flex items-center gap-4 justify-end mt-4">
                            <button @click="acceptTransaction()"
                                class="inline-flex items-center gap-2 rounded-lg border bg-success-500 px-8 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-success-400 dark:border-gray-700 dark:text-white">
                                Terima
                            </button>
                            <button @click="showModal()"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-error-500 px-8 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-error-400 dark:border-gray-700">
                                Tolak
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-col col-span-1 lg:col-span-2 gap-2">
                        <div class="card-layout h-fit flex flex-col gap-6">
                            <h1 class="card-title">Detail Anggota</h1>
                            <ul class="grid grid-cols-1 gap-6">
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nomor Anggota</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.saving_account.user.id }}</span>
                                </li>
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nama Anggota</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.saving_account.user.name }}</span>
                                </li>
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Status Keanggotaan</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.saving_account.user.status }}</span>
                                </li>
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Unit Kerja</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.saving_account.user.work_unit.name
                                        }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="modal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-96">
                <h2 class="text-lg font-semibold mb-4 text-dark-text dark:text-white">Alasan Penolakan</h2>
                <textarea rows="4" v-model="form.description"
                    class="w-full p-2 border font-body border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    placeholder="Masukkan alasan penolakan..."></textarea>
                <div class="flex justify-end mt-4 gap-2">
                    <button @click="hideModal()" type="button"
                        class="px-8 text-theme-sm py-2.5 bg-gray-300 text-dark-text dark:text-gray-800 rounded-lg hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500">
                        Batal
                    </button>
                    <button @click="rejectTransaction()" type="button"
                        class="px-8 py-2.5 text-theme-sm bg-primary text-white rounded-lg hover:bg-brand-800">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '../../../Layouts/Admin/Layout.vue'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'
import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

const props = defineProps({
    data: { type: Object, required: true },
});

const showModal = () => {
    document.getElementById('modal').classList.remove('hidden');
};
const hideModal = () => {
    document.getElementById('modal').classList.add('hidden');
};

const form = useForm({
    description: '',
    status: '',
})

const acceptTransaction = () => {
    form.status = 'accepted'
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin menerima transaksi ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, terima',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#007943',
    }).then((result) => {
        if (result.isConfirmed) {
            form.put('/admin/savings/validate/' + props.data.id, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Sukses!',
                        text: 'Transaksi berhasil diterima.',
                        icon: 'success',
                        confirmButtonColor: '#007943',
                    }).then(() => {
                        window.location.href = route('admin.dashboard')
                    })
                },
                onError: () => {
                    Swal.fire({
                        title: 'Gagal!',
                        text: 'Gagal menerima transaksi.',
                        icon: 'error',
                        confirmButtonColor: '#007943',
                    })
                }
            })
            hideModal()
        }
    })
}

const rejectTransaction = () => {
    form.status = 'rejected'
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin menolak transaksi ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, tolak',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#007943',
    }).then((result) => {
        if (result.isConfirmed) {
            form.put('/admin/savings/validate/' + props.data.id, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Sukses!',
                        text: 'Transaksi berhasil ditolak.',
                        icon: 'success',
                        confirmButtonColor: '#007943',
                    }).then(() => {
                        window.location.href = route('admin.dashboard')
                    })
                },
                onError: () => {
                    Swal.fire({
                        title: 'Gagal!',
                        text: 'Gagal menolak transaksi.',
                        icon: 'error',
                        confirmButtonColor: '#007943',
                    })
                }
            })
            hideModal()
        }
    })
}

const getStatusClass = () => {
    const baseClass = 'font-semibold rounded-2xl px-4 text-theme-sm py-1'

    switch (props.data.status) {
        case 'Selesai':
            return `${baseClass} text-green-600 bg-green-50`
        case 'Ditolak dengan alasan':
            return `${baseClass} text-yellow-600 bg-yellow-50`
        default:
            return `${baseClass} text-gray-600 bg-gray-100`
    }
}
</script>
