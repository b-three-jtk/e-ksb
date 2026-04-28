<script setup>
import AdminLayout from '../../../Layouts/Admin/Layout.vue'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'
import { useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { toast } from "vue3-toastify";
import dateParser from '@/Composables/dateParser'
import moneyParser from '@/Composables/moneyParser'
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
                :page-title="'Detail Simpanan'" :items="breadcrumbItems" />
            <div class="flex flex-col gap-4">
                <div class="card-layout flex justify-between">
                    <div class="flex gap-2 items-center">
                        <h1 class="font-semibold text-dark-text dark:text-white">No. Transaksi #{{ data.saving_transaction_code }}
                        </h1>
                    </div>
                    <div v-if="data.saving_account_code" class="flex items-center gap-4">
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
                                    <span class="font-medium text-dark-text dark:text-white">{{ moneyParser(data.saving_amount)
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Kategori Simpanan</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.saving_account.saving_type
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
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.transaction_type }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Tanggal Transaksi</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{
                                        dateParser(data.transaction_date)
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Metode Pembayaran</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.saving_payment_method }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Keterangan</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ data.saving_description ?? '-'
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
                                        data.saving_account.user.user_code }}</span>
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
        <!-- <ModalDocument ref="modalRef" modal-id="buktiModal" title="Bukti Penyetoran Simpanan" :name="data.saving_transaction_doc[0]?.name" :attachment="data.saving_transaction_doc[0]?.attachment" /> -->
    </AdminLayout>
</template>
