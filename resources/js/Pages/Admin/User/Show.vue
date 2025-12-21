<template>
    <AdminLayout>
        <div class="flex flex-col px-20 gap-4">
            <div class=""></div>
            <div class="card-layout flex justify-between items-center">
                <div class="flex gap-6">
                    <img class="rounded-full w-20" src="/public/images/user/owner.jpg"></img>
                    <div class="flex flex-col justify-center gap-1">
                        <h1 class="card-title">User Name</h1>
                        <p class="text-gray-500">
                            User Status
                        </p>
                    </div>
                </div>
                <button
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current fill-white dark:fill-gray-800"
                        width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path
                            d="M4.16667 15.8333H5.35417L13.5 7.6875L12.3125 6.5L4.16667 14.6458V15.8333ZM2.5 17.5V13.9583L13.5 2.97917C13.6667 2.82639 13.8508 2.70833 14.0525 2.625C14.2542 2.54167 14.4658 2.5 14.6875 2.5C14.9092 2.5 15.1244 2.54167 15.3333 2.625C15.5422 2.70833 15.7228 2.83333 15.875 3L17.0208 4.16667C17.1875 4.31944 17.3092 4.5 17.3858 4.70833C17.4625 4.91667 17.5006 5.125 17.5 5.33333C17.5 5.55556 17.4619 5.7675 17.3858 5.96917C17.3097 6.17083 17.1881 6.35472 17.0208 6.52083L6.04167 17.5H2.5ZM12.8958 7.10417L12.3125 6.5L13.5 7.6875L12.8958 7.10417Z"
                            fill="#1D2939" />
                    </svg>
                    Edit
                </button>
            </div>
            <div class="card-layout grid gap-5">
                <div class="card-layout py-0! grid grid-cols-2">
                    <div class="grid grid-cols-1 gap-8 py-6">
                        <h1 class="card-title">Identitas</h1>
                        <ul class="grid grid-cols-2 gap-6">
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">NIK</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.nik }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Jenis Kelamin</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.gender }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Tanggal Lahir</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.birth_date }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Pendidikan Terakhir</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.last_education
                                }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Unit Kerja</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.work_unit }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Nama Lembaga</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.institution }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Status Pernikahan</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.marital_status
                                }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Nama Pasangan</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.spouse_name }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Jumlah Tanggungan Keluarga</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.dependents }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="flex flex-col gap-8 border-0 border-l-2 border-l-stroke pl-8 py-6">
                        <h1 class="card-title">Berkas Pendukung</h1>
                        <ul class="grid grid-cols-2 gap-6">
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Foto KTP</span>
                                <button
                                    class="items-center flex justify-center gap-2 rounded-xl border border-gray-300 bg-muted mr-auto px-12 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                    <span class="icon-[tabler--eye]" style="width: 24px; height: 24px;"></span>
                                    Lihat
                                </button>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Foto KK</span>
                                <button
                                    class="items-center flex justify-center gap-2 rounded-xl border border-gray-300 bg-muted mr-auto px-12 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                    <span class="icon-[tabler--eye]" style="width: 24px; height: 24px;"></span>
                                    Lihat
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-layout py-0! grid grid-cols-2">
                    <div class="grid grid-cols-1 gap-8 py-6">
                        <h1 class="card-title">Kontak dan Alamat</h1>
                        <ul class="grid grid-cols-2 gap-6">
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Nomor Telepon</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.phone_number }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Email</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.email }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Alamat Sesuai KTP</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.address }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Alamat Domisili</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ user.residential_address
                                }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="flex flex-col gap-8 border-0 border-l-2 border-l-stroke pl-8 py-6">
                        <h1 class="card-title">Ahli Waris</h1>
                        <ul class="grid grid-cols-2 gap-6">
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Nama Ahli Waris</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ heirs[0].name }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Hubungan Keluarga</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ heirs[0].relationship
                                }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Kontak Ahli Waris</span>
                                <span class="font-medium text-dark-text dark:text-white">{{ heirs[0].contact }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-layout">
                <div class="grid grid-cols-3 gap-4">
                    <div class="card-layout flex flex-col gap-12">
                        <h1>Simpanan Pokok</h1>
                        <ul class="grid grid-cols-2 gap-6">
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Saldo</span>
                                <span class="font-medium text-dark-text dark:text-white">Rp{{
                                    saving_accounts.pokok.balance }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Terakhir Diperbarui</span>
                                <span class="font-medium text-dark-text dark:text-white">{{
                                    saving_accounts.pokok.last_updated }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Transaksi Terakhir</span>
                                <span class="font-medium text-dark-text dark:text-white">{{
                                    saving_accounts.pokok.last_transaction }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Tanggal Transaksi</span>
                                <span class="font-medium text-dark-text dark:text-white">{{
                                    saving_accounts.pokok.transaction_date }}</span>
                            </li>
                        </ul>
                        <button
                            class="flex justify-center items-center gap-2 rounded-xl border border-gray-300 bg-gray-50 px-5 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                            <span class="icon-[material-symbols--info-outline-rounded]"
                                style="width: 24px; height: 24px;"></span>
                            Mutasi
                        </button>
                    </div>
                    <div class="card-layout flex flex-col gap-12">
                        <h1>Simpanan Wajib</h1>
                        <ul class="grid grid-cols-2 gap-6">
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Saldo</span>
                                <span class="font-medium text-dark-text dark:text-white">Rp{{
                                    saving_accounts.wajib.balance }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Terakhir Diperbarui</span>
                                <span class="font-medium text-dark-text dark:text-white">{{
                                    saving_accounts.wajib.last_updated }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Transaksi Terakhir</span>
                                <span class="font-medium text-dark-text dark:text-white">{{
                                    saving_accounts.wajib.last_transaction }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Tanggal Transaksi</span>
                                <span class="font-medium text-dark-text dark:text-white">{{
                                    saving_accounts.wajib.transaction_date }}</span>
                            </li>
                        </ul>
                        <button
                            class="flex justify-center items-center gap-2 rounded-xl border border-gray-300 bg-gray-50 px-5 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                            <span class="icon-[material-symbols--info-outline-rounded]"
                                style="width: 24px; height: 24px;"></span>
                            Mutasi
                        </button>
                    </div>
                    <div class="card-layout flex flex-col gap-12">
                        <h1>Simpanan Sukarela</h1>
                        <ul class="grid grid-cols-2 gap-6">
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Saldo</span>
                                <span class="font-medium text-dark-text dark:text-white">Rp{{
                                    saving_accounts.sukarela.balance }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Terakhir Diperbarui</span>
                                <span class="font-medium text-dark-text dark:text-white">{{
                                    saving_accounts.sukarela.last_updated }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Transaksi Terakhir</span>
                                <span class="font-medium text-dark-text dark:text-white">{{
                                    saving_accounts.sukarela.last_transaction }}</span>
                            </li>
                            <li class="flex flex-col gap-2">
                                <span class="text-sm text-gray-500 dark:text-gray-300">Tanggal Transaksi</span>
                                <span class="font-medium text-dark-text dark:text-white">{{
                                    saving_accounts.sukarela.transaction_date }}</span>
                            </li>
                        </ul>
                        <button
                            class="flex justify-center items-center gap-2 rounded-xl border border-gray-300 bg-gray-50 px-5 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                            <span class="icon-[material-symbols--info-outline-rounded]"
                                style="width: 24px; height: 24px;"></span>
                            Mutasi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue';

const props = defineProps({
    user: {
        nik: String,
        gender: String,
        birth_date: String,
        last_education: String,
        work_unit: String,
        institution: String,
        marital_status: String,
        spouse_name: String,
        dependents: Number,
        phone_number: String,
        email: String,
        address: String,
        residential_address: String,
    },
    heirs: {
        name: String,
        relationship: String,
        contact: String,
    },
    saving_accounts: {
        pokok: {
            balance: Number,
            last_updated: String,
            last_transaction: String,
            transaction_date: String,
        },
        wajib: {
            balance: Number,
            last_updated: String,
            last_transaction: String,
            transaction_date: String,
        },
        sukarela: {
            balance: Number,
            last_updated: String,
            last_transaction: String,
            transaction_date: String,
        },
    },
});
</script>
