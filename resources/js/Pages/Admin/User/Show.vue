<template>
    <AdminLayout title="Detail Anggota">
        <div class="flex flex-col px-20">
            <PageBreadcrumb :page-title="'Detail Anggota'" />
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
                            <h1 class="card-title">{{ user.name }}</h1>
                            <p class="text-gray-500">
                                {{ user.role.name }} - {{ user.member_number }}
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
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.gender ?? '-'
                                        }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Tanggal Lahir</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.birth_date ?? '-'
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Pendidikan Terakhir</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.last_education ??
                                        '-'
                                        }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Unit Kerja</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.work_unit.name
                                        }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nama Lembaga</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.institution
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Status Pernikahan</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.marital_status ??
                                        '-'
                                        }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Nama Pasangan</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.spouse_name ?? '-'
                                    }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Jumlah Tanggungan
                                        Keluarga</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.dependents ?? '-'
                                    }}</span>
                                </li>
                            </ul>
                        </div>
                        <div
                            class="flex flex-col gap-8 border-t-2 border-t-stroke xl:border-0 xl:border-l-2 xl:border-l-stroke xl:dark:border-l-gray-700 xl:pl-8 py-6">
                            <h1 class="card-title">Berkas Pendukung</h1>
                            <ul class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Foto KTP</span>
                                    <button
                                        class="items-center flex justify-center gap-2 rounded-xl border border-gray-300 bg-muted mr-auto px-12 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                        <span class="icon-[tabler--eye]" style="width: 24px; height: 24px;"></span>
                                        Lihat
                                    </button>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Foto KK</span>
                                    <button
                                        class="items-center flex justify-center gap-2 rounded-xl border border-gray-300 bg-muted mr-auto px-12 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                        <span class="icon-[tabler--eye]" style="width: 24px; height: 24px;"></span>
                                        Lihat
                                    </button>
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
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.address ?? '-'
                                        }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Alamat Domisili</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.residential_address
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
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.heirs.name ?? '-'
                                        }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Hubungan Keluarga</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.heirs.relationship
                                        ?? '-'
                                        }}</span>
                                </li>
                                <li class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Kontak Ahli Waris</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ user.heirs.contact ??
                                        '-'
                                        }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-layout flex flex-col gap-4">
                    <h1 class="card-title">Akun</h1>
                    <div v-if="user.saving_accounts?.length && user.saving_accounts?.length"
                        class="flex flex-col gap-4">
                        <div class="grid xl:grid-cols-3 grid-cols-1 gap-4">
                            <div v-for="account in user.saving_accounts" class="card-layout flex flex-col gap-12">
                                <h1>{{ account.type }}</h1>
                                <ul class="grid sm:grid-cols-2 grid-cols-1 gap-6">
                                    <li class="flex flex-col gap-2">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Saldo</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            parseCurrencyAmount(account.balance) ?? '0' }}</span>
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
                                <button
                                    class="flex justify-center items-center gap-2 rounded-xl border border-gray-300 bg-gray-50 px-5 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                    <span class="icon-[material-symbols--info-outline-rounded]"
                                        style="width: 24px; height: 24px;"></span>
                                    Mutasi
                                </button>
                            </div>
                        </div>
                        <div class="grid xl:grid-cols-2 grid-cols-1">
                            <div v-for="financing in user.financings" class="card-layout flex flex-col gap-12 px-0!">
                                <div class="border-b-2 border-gray-200 dark:border-gray-700 pb-4 px-8">
                                    <h1 class="font-semibold text-dark-text dark:text-white/90">Pembiayaan Murabahah
                                    </h1>
                                </div>
                                <ul class="grid grid-cols-1 gap-6 px-8">
                                    <li class="flex sm:flex-row flex-col justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Objek Pembiayaan</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            financing.product_type }}</span>
                                    </li>
                                    <li class="flex sm:flex-row flex-col justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Harga Pembiayaan</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            parseCurrencyAmount(financing.loan.total_price) }}</span>
                                    </li>
                                    <li class="flex sm:flex-row flex-col justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Terakhir
                                            Diperbarui</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            dateParser(financing.created_at) }}</span>
                                    </li>
                                    <li class="flex flex-col gap-2">
                                        <div class="flex sm:flex-row flex-col justify-between items-center">
                                            <span class="text-sm text-gray-500 dark:text-gray-300">Sisa
                                                Pembiayaan</span>
                                            <span class="font-medium text-dark-text dark:text-white">{{
                                                parseCurrencyAmount(
                                                    Math.max(
                                                        0,
                                                        financing.loan.total_price -
                                                        financing.loan.payments.length * financing.loan.amount_ins
                                                    )) }}</span>
                                        </div>
                                        <div class="progress-container">
                                            <div class="progress-bar"
                                                :style="{ width: (Math.min(financing.loan.tenor ? (financing.loan.payments.length / financing.loan.tenor * 100) : 0, 100)) + '%' }">
                                            </div>
                                        </div>
                                    </li>
                                    <li class="flex sm:flex-row flex-col justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Angsuran Per-Bulan</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            parseCurrencyAmount(financing.loan.amount_ins) }}</span>
                                    </li>
                                    <li class="flex sm:flex-row flex-col justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Jatuh Tempo
                                            Berikutnya</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            dateParser(financing.created_at) }}</span>
                                    </li>
                                    <li class="flex sm:flex-row flex-col justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-300">Posisi Angsuran</span>
                                        <span class="font-medium text-dark-text dark:text-white">{{
                                            financing.loan.payments.length }} dari {{ financing.loan.tenor }}</span>
                                    </li>
                                </ul>
                                <div class="flex flex-col sm:flex-row gap-4 w-full px-8">
                                    <button
                                        class="flex w-full justify-center items-center gap-2 rounded-xl border border-gray-300 bg-gray-50 px-5 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                        <span class="icon-[material-symbols--info-outline-rounded]"
                                            style="width: 24px; height: 24px;"></span>
                                        Detail
                                    </button>
                                    <button
                                        class="flex w-full justify-center items-center gap-2 rounded-xl border border-gray-300 bg-gray-50 px-5 py-2.5 text-theme-sm font-medium text-dark-text shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                                        <span class="icon-[material-symbols--info-outline-rounded]"
                                            style="width: 24px; height: 24px;"></span>
                                        Riwayat
                                    </button>
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
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue';
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue';
import dateParser from '@/Composables/dateParser.js';
import parseCurrencyAmount from '@/Composables/moneyParser.js';
import NoArchiveIcon from '@/Icons/NoArchiveIcon.vue';
import UserIcon from '@/Icons/UserIcon.vue';

const props = defineProps({
    user: { type: Object, required: true },
});

</script>
