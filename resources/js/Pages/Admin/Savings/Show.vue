<script setup>
import AdminLayout from '../../../Layouts/Admin/Layout.vue'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'
import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { toast } from "vue3-toastify";
import dateParser from '@/Composables/dateParser'
import moneyParser from '@/Composables/moneyParser'

const props = defineProps({
    data: { type: Object, required: true },
});

const showModal = () => {
    document.getElementById('modal').classList.remove('hidden');
};
const hideModal = () => {
    document.getElementById('modal').classList.add('hidden');
};

const showModalDoc = () => {
    document.getElementById('modalDoc').classList.remove('hidden');
};
const hideModalDoc = () => {
    document.getElementById('modalDoc').classList.add('hidden');
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
                    toast("Transaksi berhasil diterima!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    }).then(() => {
                        router.visit(route('admin.dashboard'))
                    })
                },
                onError: () => {
                    toast("Gagal menerima transaksi.", {
                        "type": "error",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    })
                }
            })
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
            hideModal()
            form.put('/admin/savings/validate/' + props.data.id, {
                onSuccess: () => {
                    toast("Transaksi berhasil ditolak!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    }).then(() => {
                        router.visit(route('admin.dashboard'))
                    })
                },
                onError: () => {
                    toast("Gagal menolak transaksi.", {
                        "type": "error",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    })
                }
            })
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

const breadcrumbItems = [
    {name: 'Dashboard', link: '/admin'},
    {name: 'Pengelolaan Simpanan', link: '/admin/savings/list'},
    {name: 'Transaksi Simpanan'},
];
</script>

<template>
    <AdminLayout title="Detail Transaksi Simpanan">
        <div class="flex flex-col px-20">
            <PageBreadcrumb
                :page-title="data.status != 'Belum Ditinjau' ? 'Detail Simpanan' : 'Validasi Permohonan Simpanan'" :items="breadcrumbItems" />
            <div class="flex flex-col gap-6">
                <div class="card-layout flex justify-between">
                    <div class="flex gap-2 items-center">
                        <h1 class="font-semibold text-dark-text dark:text-white">No. Transaksi #{{ data.id }}
                        </h1>
                        <span :class="getStatusClass()">{{ data.status }}</span>
                    </div>
                    <div v-if="data.account_number" class="flex items-center gap-4">
                        <button @click="showModalDoc()"
                            class="inline-flex items-center gap-2 rounded-lg border bg-blue-accent px-6 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-blue-accent/70 dark:border-gray-700 dark:text-white dark:hover:bg-blue-accent/70 dark:hover:text-gray-200">
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
                                    <span class="font-medium text-dark-text dark:text-white">{{ moneyParser(data.amount)
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
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        dateParser(data.transaction_date)
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
                                        data.saving_account.user.member_number }}</span>
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
                        <div v-if="data.account" class="card-layout flex flex-col pb-12.5! gap-6">
                            <h1 class="card-title">Informasi Rekening</h1>
                            <ul class="grid grid-cols-1 gap-6">
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nomor Rekening</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.account_number }}</span>
                                </li>
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nama Pemilik Rekening</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.account?.account_name }}</span>
                                </li>
                                <li class="flex lg:flex-row flex-col gap-2 justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nama Bank</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        data.account?.bank_name }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="modal" @click.self="hideModal()" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden">
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
        <div id="modalDoc" @click.self="hideModalDoc()" class="fixed inset-0 bg-black/50 flex items-center justify-center pt-44 pb-22 h-screen hidden">
            <div class="bg-white max-h-[80vh] rounded-2xl dark:bg-gray-800">
                <div class="flex justify-between px-4">
                    <h1 class="card-title p-4">Bukti Pembayaran</h1>
                    <button @click="hideModalDoc()" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="px-4 pb-4 max-h-[70vh] overflow-y-scroll custom-scrollbar">
                    <img :src="data.saving_transaction_doc[0]?.attachment" alt="Bukti Pembayaran" class="w-full h-auto object-contain" />
                    <p class="mt-2 text-center text-gray-600 dark:text-gray-400">{{ data.saving_transaction_doc[0]?.name
                    }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
