<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue';
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue';
import Button from '@/Components/Form/Button.vue';
import Info from '@/Components/Form/Info.vue';
import moneyParser from '@/Composables/moneyParser.js';
import dateParser from '@/Composables/dateParser.js';
import Tooltip from '@/Components/Form/Tooltip.vue';
import { ref } from 'vue';

const props = defineProps({
    data: Object,
});

const showPanel = ref(false);
const togglePanel = () => {
    showPanel.value = !showPanel.value;
}

const breadcrumbsItems = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Validasi Pelunasan' },
];

const getStatusClass = () => {
    const baseClass = 'font-semibold rounded-2xl px-4 text-theme-sm py-1'

    switch (props.data.financing.loan.payment_schedules[props.data.total_paid_installments].status) {
        case 'Selesai':
            return `${baseClass} text-green-600 bg-green-50`
        case 'Ditolak dengan alasan':
            return `${baseClass} text-yellow-600 bg-yellow-50`
        default:
            return `${baseClass} text-gray-600 bg-gray-100`
    }
}
console.log(props.data.financing.loan.payment_schedules[props.data.total_paid_installments]);
</script>

<template>
    <AdminLayout title="Validasi Pelunasan">
        <PageBreadcrumb page-title="Validasi Pelunasan" :items="breadcrumbsItems" />
        <div class="flex flex-col">
            <div class="card-layout flex justify-between items-center bg-blue-accent!">
                <h1 class="text-white font-semibold">{{ props.data.financing.product_name }}</h1>
                <div class="flex items-center gap-4">
                    <p class="text-white">No Transaksi: #{{ props.data.financing.transaction_code }}</p>
                    <span :class="getStatusClass()">{{
                        props.data.financing.loan.payment_schedules[props.data.total_paid_installments].status }}</span>
                    <Button variant="primary">Detail</Button>
                </div>
            </div>
            <div class="grid grid-cols-5 gap-4 mt-4">
                <div class="card-layout col-span-3">
                    <h1 class="card-title">Data Pembiayaan</h1>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <li>
                            <Info label="Tanggal Akad" :value="dateParser(props.data.financing.loan.created_at)" />
                        </li>
                        <li>
                            <Info label="Nomor Transaksi" :value="props.data.financing.transaction_code" />
                        </li>
                        <li>
                            <Info label="Objek Pembiayaan" :value="props.data.financing.product_name" />
                        </li>
                        <li>
                            <Info label="Kategori Objek Pembiayaan" :value="props.data.financing.product_type" />
                        </li>
                        <li>
                            <Info label="Informasi Cicilan"
                                :value="props.data.total_paid_installments + ' dari ' + props.data.financing.loan.tenor + ' Bulan'" />
                        </li>
                        <li>
                            <Info label="Metode Pelunasan"
                                :value="props.data.financing.loan.payment_schedules[props.data.total_paid_installments].payment.method" />
                        </li>
                    </ul>
                    <div class="card-layout mt-8">
                        <h1 class="card-title mb-2">Informasi Harga</h1>
                        <table class="min-w-full">
                            <tbody>
                                <tr class="border-t border-gray-100 dark:border-gray-500">
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            Harga Perolehan Objek Pembiayaan
                                        </p>
                                    </td>
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            {{ moneyParser(props.data.financing.cost_price) }}
                                        </p>
                                    </td>
                                </tr>
                                <tr class="border-t border-gray-100 dark:border-gray-500">
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            Margin (Keuntungan)
                                        </p>
                                    </td>
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            {{ moneyParser(props.data.financing.margin) }}
                                        </p>
                                    </td>
                                </tr>
                                <tr class="border-t border-gray-100 dark:border-gray-500">
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            Uang Muka
                                        </p>
                                    </td>
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            {{ moneyParser(props.data.financing.down_payment) }}
                                        </p>
                                    </td>
                                </tr>
                                <tr class="border-t border-gray-100 dark:border-gray-500">
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            Tsaman Naqdy (Harga Jual Tunai)
                                        </p>

                                    </td>
                                    <td class="py-5 px-2 flex items-center gap-1 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            {{ moneyParser(props.data.financing.tsaman_naqdy) }}
                                        </p>
                                        <Tooltip>
                                            <div class="grid grid-cols-2 gap-2">
                                                <span class="font-head">Harga Perolehan - DP</span>
                                                <span class="font-medium text-blue-500">
                                                    {{ moneyParser(props.data.financing.cost_price -
                                                        props.data.financing.down_payment) }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <span class="font-head">Margin 1 Bulan</span>
                                                <span class="font-medium text-blue-500 border-b border-b-gray-300">
                                                    {{ moneyParser(props.data.financing.margin /
                                                        props.data.financing.loan.tenor) }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-1.5">
                                                <span class="font-head"></span>
                                                <span class="font-medium text-blue-500">
                                                    {{ moneyParser(props.data.financing.tsaman_naqdy) }}
                                                </span>
                                            </div>
                                        </Tooltip>
                                    </td>
                                </tr>
                                <tr class="border-t border-gray-100 dark:border-gray-500">
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            Qimah Ismiyyah (Harga Jual Tidak Tunai/Harga Jual Cicilan)
                                        </p>
                                    </td>
                                    <td class="py-5 px-2 flex items-center gap-1 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            {{ moneyParser(props.data.financing.loan.total_loan) }}
                                        </p>
                                        <Tooltip>
                                            <div class="grid grid-cols-2 gap-2">
                                                <span class="font-head">Harga Perolehan</span>
                                                <span class="font-medium text-blue-500">
                                                    {{ moneyParser(props.data.financing.cost_price -
                                                        props.data.financing.down_payment) }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <span class="font-head">Margin</span>
                                                <span class="font-medium text-blue-500 border-b border-b-gray-300">
                                                    {{ moneyParser(props.data.financing.margin) }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-1.5">
                                                <span class="font-head"></span>
                                                <span class="font-medium text-blue-500">
                                                    {{ moneyParser(props.data.financing.loan.total_loan) }}
                                                </span>
                                            </div>
                                        </Tooltip>
                                    </td>
                                </tr>
                                <tr class="border-t border-gray-100 dark:border-gray-500">
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            Jumlah Angsuran Perbulan
                                        </p>
                                    </td>
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            {{ moneyParser(props.data.financing.loan.monthly_installment) }}
                                        </p>
                                    </td>
                                </tr>
                                <tr class="border-t border-gray-100 dark:border-gray-500">
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            Qimah Haliyyah (Harga Jual Saat Ini)
                                        </p>
                                    </td>
                                    <td class="py-5 px-2 flex-wrap flex items-center gap-1">
                                        <p class="text-dark-text dark:text-gray-400">
                                            {{ moneyParser(props.data.qimah_haliyyah) }}
                                        </p>
                                        <Tooltip>
                                            <div class="grid grid-cols-2 gap-2">
                                                <span class="font-head">Tsaman Naqdy</span>
                                                <span class="font-medium text-blue-500">
                                                    {{ moneyParser(props.data.financing.tsaman_naqdy) }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <span class="font-head">Margin {{ props.data.total_paid_installments + 1
                                                    }}
                                                    Bulan</span>
                                                <span class="font-medium text-blue-500 border-b border-b-gray-300">
                                                    {{ moneyParser(props.data.margin_diff_per_month *
                                                        (props.data.total_paid_installments + 1)) }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-1.5">
                                                <span class="font-head"></span>
                                                <span class="font-medium text-blue-500">
                                                    {{ moneyParser(props.data.qimah_haliyyah) }}
                                                </span>
                                            </div>
                                        </Tooltip>
                                    </td>
                                </tr>
                                <tr class="border-t border-gray-100 dark:border-gray-500">
                                    <td class="py-5 px-2 flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            Total Pembayaran yang Telah Dilakukan
                                        </p>
                                    </td>
                                    <td class="py-5 px-2 flex flex-wrap">
                                        <p class="text-dark-text dark:text-gray-400">
                                            {{ moneyParser(props.data.payment_total) }}
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="panel mt-4">
                        <button :class="showPanel ? 'rounded-t-2xl! rounded-b-none!' : 'rounded-2xl'"
                            class="card-layout bg-light-bg! font-bold text-secondary! w-full flex items-center justify-between transition-all duration-500 ease-in-out"
                            aria-label="Detail total pelunasan saat ini" @click.prevent="togglePanel">
                            <h1>Total Pelunasan Saat Ini</h1>
                            <div class="flex">
                                <p>
                                    {{ moneyParser(props.data.repayment_total) }}
                                </p>
                                <span :class="showPanel ? 'icon-[tabler--chevron-up]' : 'icon-[tabler--chevron-down]'"
                                    class="transition-all duration-500 ease-in-out" style="width: 24px; height: 24px;"
                                    aria-hidden="true"></span>
                            </div>
                        </button>
                        <div class="content bg-white dark:bg-gray-800 transition-all duration-500 ease-in-out px-8 pb-6 rounded-b-2xl! rounded-t-none! card-layout"
                            v-if="showPanel">
                            <ul>
                                <li class="flex justify-between border-b border-b-stroke pb-4">
                                    <h1>Qimah Haliyyah</h1>
                                    <p>{{ moneyParser(props.data.qimah_haliyyah) }}</p>
                                </li>
                                <li class="flex justify-between border-b border-b-stroke py-4">
                                    <h1>Pembayaran Telah Dilakukan</h1>
                                    <p>{{ moneyParser(props.data.payment_total) }}</p>
                                </li>
                                <li class="flex justify-between border-b font-semibold border-b-stroke py-4">
                                    <h1>Total Pelunasan (Qimah Haliyyah - Pembayaran Telah Dilakukan)</h1>
                                    <p>{{ moneyParser(props.data.repayment_total) }}</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-layout col-span-2 h-fit flex flex-col gap-6">
                    <h1 class="card-title">Detail Anggota</h1>
                    <ul class="grid grid-cols-1 gap-6">
                        <li class="flex lg:flex-row flex-col gap-2 justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-300">Nomor Anggota</span>
                            <span class="font-medium text-dark-text dark:text-white">{{
                                props.data.financing.user.user_code }}</span>
                        </li>
                        <li class="flex lg:flex-row flex-col gap-2 justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-300">Nama Anggota</span>
                            <span class="font-medium text-dark-text dark:text-white">{{
                                props.data.financing.user.name }}</span>
                        </li>
                        <li class="flex lg:flex-row flex-col gap-2 justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-300">Status Keanggotaan</span>
                            <span class="font-medium text-dark-text dark:text-white">{{
                                props.data.financing.user.status }}</span>
                        </li>
                    </ul>
                </div>
                <div class="flex justify-end gap-4 col-span-3">
                    <Button variant="success">Terima</Button>
                    <Button variant="danger">Tolak</Button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
