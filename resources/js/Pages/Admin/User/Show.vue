<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue';
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue';
import dateParser from '@/Composables/dateParser.js';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import NoArchiveIcon from '@/Icons/NoArchiveIcon.vue';
import UserIcon from '@/Icons/UserIcon.vue';
import Swal from 'sweetalert2';
import { useForm } from '@inertiajs/vue3'
import Button from '@/Components/Form/Button.vue';
import InfoCircleIcon from '@/Icons/InfoCircleIcon.vue'
import EyeIcon from '@/Icons/EyeIcon.vue';
import ModalDocument from '@/Components/ModalDocument.vue';
import { ref } from 'vue'
import ModalMutasi from '@/Components/ModalMutasi.vue';

const props = defineProps({
    user: { type: Object, required: true },
    ktp_photo: String,
    kk_photo: String,
});

const form = useForm({
    status: '',
});

const ktpModalRef = ref(null)
const kkModalRef = ref(null)

const mutasiModalRef = ref(null)
const riwayatModalRef = ref(null)
const isLoadingMutasi = ref(false)
const isLoadingRiwayat = ref(false)

const mutasiData = ref([])
const riwayatData = ref([])

const selectedAccount = ref(null)
const selectedFinancing = ref(null)

const openKtpModal = () => ktpModalRef.value?.openModal()
const openKkModal = () => kkModalRef.value?.openModal()

const nonactiveUser = () => {
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin menonaktifkan pengguna ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, nonaktifkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#007943',
    }).then((result) => {
        if (result.isConfirmed) {
            form.put((`/admin/users/${props.user.id}/nonactive`), {
                onSuccess: () => {
                    Swal.fire('Berhasil', 'Pengguna berhasil dinonaktifkan!', 'success').then(() => {
                        router.push(route('admin.users.index'))
                    })
                },
                onError: () => {
                    Swal.fire('Gagal', 'Gagal menonaktifkan pengguna.', 'error')
                }
            })
        }
    })
}

const getStatusClass = () => {
    const baseClass = 'font-semibold rounded-2xl px-4 text-theme-sm py-1 border border-stroke'

    switch (props.user.status) {
        case 'Aktif':
            return `${baseClass} text-green-600 bg-green-50`
        case 'Tidak Aktif':
            return `${baseClass} text-red-600 bg-red-100`
        case 'Mengundurkan Diri':
            return `${baseClass} text-yellow-600 bg-yellow-100`
        case 'Dalam Peninjauan':
            return `${baseClass} text-blue-600 bg-blue-100`
        case 'Menunggu Pembayaran':
            return `${baseClass} text-purple-600 bg-purple-100`
        case 'Ditolak dengan alasan':
            return `${baseClass} text-orange-600 bg-orange-100`
        default:
            return `${baseClass} text-gray-600 bg-gray-100`
    }
}

const openMutasiModal = async (account) => {
    selectedAccount.value = account
    isLoadingMutasi.value = true
    mutasiModalRef.value?.openModal()

    try {
        const res = await axios.get(`/admin/accounts/${account.id}/mutasi`)
        mutasiData.value = res.data
    } catch (error) {
        console.error('Error fetching mutasi:', error)
    } finally {
        isLoadingMutasi.value = false
    }
}

const openRiwayatModal = async (financing) => {
    selectedFinancing.value = financing
    isLoadingRiwayat.value = true
    riwayatModalRef.value?.openModal()

    try {
        const res = await axios.get(`/admin/financings/${financing.id}/riwayat`)
        riwayatData.value = res.data
    } catch (error) {
        console.error('Error fetching riwayat:', error)
    } finally {
        isLoadingRiwayat.value = false
    }
}

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Pengelolaan Data Anggota', link: '/admin/users/list' },
    { name: 'Detail Anggota' },
];
</script>

<template>
    <AdminLayout title="Detail Anggota">
        <div class="flex flex-col">
            <PageBreadcrumb :page-title="'Detail Anggota'" :items="breadcrumbItems" />
            <div class="flex flex-col gap-6">
                <div class="card-layout flex flex-col xl:flex-row justify-between gap-4 items-center">
                    <div class="flex flex-col xl:flex-row justify-center items-center text-center xl:text-left gap-6">
                        <div v-if="user.profile_picture">
                            <img class="w-20 h-20 rounded-full object-cover bg-gray-400" :src="user.profile_picture"
                                alt="User avatar">
                        </div>
                        <div v-else
                            class="w-20 h-20 rounded-full bg-white border border-stroke flex items-center justify-center text-gray-500">
                            <UserIcon />
                        </div>
                        <div class="flex flex-col justify-center gap-1">
                            <div class="flex gap-2">
                                <h1 class="card-title">{{ user.name }}</h1>
                                <span :class="getStatusClass()">{{ user.status }}</span>
                            </div>
                            <p class="text-gray-500">
                                {{ user.role.name }} - {{ user.user_code }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <button @click="nonactiveUser()" v-if="user.status === 'Aktif'"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-error-500 px-5 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-error-400 dark:border-gray-700 dark:hover:bg-error-500/70 dark:hover:text-gray-200">
                            <span class="icon-[tabler--ban]" style="width: 24px; height: 24px;"></span>
                            Nonaktifkan
                        </button>
                    </div>
                </div>
                <div class="card-layout grid gap-5">
                    <div class="card-layout py-0! grid xl:grid-cols-2 grid-cols-1">
                        <div class="grid grid-cols-1 gap-8 py-6">
                            <h1 class="card-title">Identitas</h1>
                            <ul class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">NIK</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.nik }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Jenis Kelamin</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.member.gender ?? '-'
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Tanggal Lahir</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.member.birth_date ?? '-'
                                        }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Pendidikan Terakhir</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.member.last_education ??
                                        '-'
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Status Pernikahan</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.member.marital_status ??
                                        '-'
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nama Pasangan</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.member.spouse_name ?? '-'
                                        }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Jumlah Tanggungan
                                        Keluarga</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.member.dependents ?? '-'
                                        }}</span>
                                </li>
                            </ul>
                        </div>
                        <div
                            class="flex flex-col gap-8 border-t-2 border-t-stroke xl:border-0 xl:border-l-2 xl:border-l-stroke xl:dark:border-l-gray-700 xl:pl-8 py-6">
                            <h1 class="card-title">Berkas Pendukung</h1>
                            <ul class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                                <li class="flex flex-col gap-2 w-fit">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Foto KTP</span>
                                    <Button :disabled="ktp_photo" variant="gray" @click="openKtpModal">
                                        <EyeIcon />
                                        Lihat
                                    </Button>
                                </li>
                                <li class="flex flex-col gap-2 w-fit">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Foto KK</span>
                                    <Button variant="gray" :disabled="kk_photo" @click="openKkModal()">
                                        <EyeIcon />
                                        Lihat
                                    </Button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-layout py-0! grid xl:grid-cols-2 grid-cols-1">
                        <div class="grid grid-cols-1 gap-8 py-6">
                            <h1 class="card-title">Kontak dan Alamat</h1>
                            <ul class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nomor Telepon</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.phone_number ?? '-'
                                        }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Email</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.email }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Alamat Sesuai KTP</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.member.residential_address ?? '-'
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Alamat Domisili</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.member.domicile_address
                                        ?? '-'
                                    }}</span>
                                </li>
                            </ul>
                        </div>
                        <div
                            class="flex flex-col gap-8 border-t-2 border-t-stroke xl:border-0 xl:border-l-2 xl:border-l-stroke xl:dark:border-l-gray-700 xl:pl-8 py-6">
                            <h1 class="card-title">Ahli Waris</h1>
                            <ul class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nama Ahli Waris</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.member.heirs.name ?? '-'
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Hubungan Keluarga</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.member.heirs.relationship
                                        ?? '-'
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Kontak Ahli Waris</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.member.heirs.contact ??
                                        '-'
                                    }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-layout flex flex-col gap-4">
                    <h1 class="card-title">Akun</h1>
                    <div v-if="user.member.saving_accounts?.length && user.member.saving_accounts?.length"
                        class="flex flex-col gap-4">
                        <div class="grid xl:grid-cols-3 grid-cols-1 gap-4">
                            <div v-for="account in user.member.saving_accounts" class="card-layout flex flex-col gap-12">
                                <h1 class="card-title">{{ account.type }}</h1>
                                <ul class="grid sm:grid-cols-2 grid-cols-1 gap-6">
                                    <li class="flex flex-col gap-2">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Saldo</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            parseCurrencyAmount(account.balance) }}</span>
                                    </li>
                                    <li class="flex flex-col gap-2">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Terakhir
                                            Diperbarui</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            dateParser(account.updated_at) ?? '-' }}</span>
                                    </li>
                                    <li class="flex flex-col gap-2">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Transaksi Terakhir</span>
                                        <span
                                            :class="account.transactions[0]?.type == 'Penyetoran' ? 'text-green-500' : 'text-red-500'"
                                            class="font-medium text-dark-text dark:text-white">{{
                                                account.transactions[0]?.type == 'Penyetoran' ? '+' : '-' }}
                                            {{ parseCurrencyAmount(account.transactions[0]?.amount) ?? '-' }}</span>
                                    </li>
                                    <li class="flex flex-col gap-2">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Tanggal Transaksi</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            dateParser(account.transactions[0]?.transaction_date) ?? '-' }}</span>
                                    </li>
                                </ul>
                                <Button full size="small" variant="light" @click="openMutasiModal(account)">
                                    <InfoCircleIcon />
                                    Mutasi
                                </Button>
                            </div>
                        </div>
                        <div class="grid xl:grid-cols-2 grid-cols-1 gap-4">
                            <div v-for="financing in user.member.financings" class="card-layout flex flex-col gap-12 px-0!">
                                <div class="border-b-2 border-gray-200 dark:border-gray-700 pb-4 px-8">
                                    <h1 class="font-semibold text-dark-text dark:text-white/90">Pembiayaan Murabahah
                                    </h1>
                                </div>
                                <ul class="grid grid-cols-1 gap-6 px-8">
                                    <li class="flex sm:flex-row flex-col justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Objek Pembiayaan</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            financing.product_name }}</span>
                                    </li>
                                    <li class="flex sm:flex-row flex-col justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Harga Pembiayaan</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            parseCurrencyAmount(financing.loan?.total_loan) }}</span>
                                    </li>
                                    <li class="flex sm:flex-row flex-col justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Terakhir
                                            Diperbarui</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            dateParser(financing.updated_at) }}</span>
                                    </li>
                                    <li class="flex flex-col gap-2">
                                        <div class="flex sm:flex-row flex-col justify-between items-center">
                                            <span class="text-sm text-gray-500 dark:text-gray-300">Sisa
                                                Pembiayaan</span>
                                            <span class="font-medium text-dark-text dark:text-white">
                                                {{ parseCurrencyAmount((parseFloat(financing.loan?.remaining_principal)
                                                    || 0) + (parseFloat(financing.loan?.remaining_margin) || 0)) }}
                                            </span>
                                        </div>
                                        <div class="progress-container">
                                            <div class="progress-bar"
                                                :style="{ width: (Math.min(((financing.loan?.tenor ?? 0) > 0
                                                    ? (((financing.loan?.payment_schedules?.length ?? 0) / (financing.loan?.tenor ?? 0)) * 100)
                                                    : 0), 100)) + '%' }">
                                            </div>
                                        </div>
                                    </li>
                                    <li class="flex sm:flex-row flex-col justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Angsuran Per-Bulan</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            parseCurrencyAmount(financing.loan?.monthly_installment) }}</span>
                                    </li>
                                    <li class="flex sm:flex-row flex-col justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Jatuh Tempo
                                            Berikutnya</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            dateParser(financing.next_payment?.due_date) }}</span>
                                    </li>
                                    <li class="flex sm:flex-row flex-col justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Posisi Angsuran</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            financing.loan_payment_paid_count ?? 0 }} dari {{ financing.loan?.tenor ?? 0
                                            }}</span>
                                    </li>
                                </ul>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full px-8">
                                    <Button size="small" full variant="info">
                                        <InfoCircleIcon />
                                        Detail
                                    </Button>
                                    <Button full size="small" variant="light" @click="openRiwayatModal(financing)">
                                        <span class="icon-[tabler--history]" style="width: 24px; height: 24px;"></span>
                                        Riwayat
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-12">
                        <NoArchiveIcon width="120px" height="120px" />
                        <p class="text-dark-text text-sm dark:text-gray-400 pt-4">
                            Tidak ada data akun.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <ModalMutasi ref="mutasiModalRef" modal-id="modal-mutasi" title="Mutasi Rekening Anggota"
            :account="selectedAccount" :transactions="mutasiData" :loading="isLoadingMutasi"
            :key="selectedAccount?.id" />
        <ModalMutasi ref="riwayatModalRef" modal-id="modal-riwayat" title="Riwayat Pembiayaan"
            :financing="selectedFinancing" :schedules="riwayatData" :loading="isLoadingRiwayat"
            :key="selectedFinancing?.id" />
        <ModalDocument ref="ktpModalRef" modal-id="modal-ktp" title="Dokumen KTP Anggota" name="KTP"
            :attachment="ktp_photo" />
        <ModalDocument ref="kkModalRef" modal-id="modal-kk" title="Dokumen KK Anggota" name="KK"
            :attachment="kk_photo" />
    </AdminLayout>
</template>
