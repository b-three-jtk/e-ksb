<script setup>
import AdminLayout from '../../../Layouts/Admin/Layout.vue'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'
import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { toast } from "vue3-toastify";
import dateParser from '@/Composables/dateParser'
import moneyParser from '@/Composables/moneyParser'
import ModalDocument from '@/Components/ModalDocument.vue';
import Button from '@/Components/Form/Button.vue';
import { ref } from 'vue'

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

const modalRef = ref(null)
const openModalBukti = () => modalRef.value?.openModal()
</script>

<template>
    <AdminLayout title="Detail Transaksi Simpanan">
        <div class="flex flex-col">
            <PageBreadcrumb
                :page-title="data.status != 'Belum Ditinjau' ? 'Detail Simpanan' : 'Validasi Permohonan Simpanan'" :items="breadcrumbItems" />
            <div class="flex flex-col gap-4">
                <div class="card-layout flex justify-between">
                    <div class="flex gap-2 items-center">
                        <h1 class="font-semibold text-dark-text dark:text-white">No. Transaksi #{{ data.transaction_code }}
                        </h1>
                        <span :class="getStatusClass()">{{ data.status }}</span>
                    </div>
                    <div v-if="data.account_number" class="flex items-center gap-4">
                        <Button @click="openModalBukti()" variant="info">Lihat Bukti</Button>
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
                            <Button @click="acceptTransaction()" variant="success">
                                Terima
                            </Button>
                            <Button @click="showModal()" variant="danger">
                                Tolak
                            </Button>
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
                    <Button @click="hideModal()" variant="light" size="small">Batal</Button>
                    <Button @click="rejectTransaction()" size="small">Simpan</Button>
                </div>
            </div>
        </div>
        <ModalDocument ref="modalRef" modal-id="buktiModal" title="Bukti Penyetoran Simpanan" :name="data.saving_transaction_doc[0]?.name" :attachment="data.saving_transaction_doc[0]?.attachment" />
    </AdminLayout>
</template>
