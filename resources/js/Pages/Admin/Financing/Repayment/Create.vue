<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import Info from '@/Components/Form/Info.vue';
import dateParser from '@/Composables/dateParser.js';
import moneyParser from '@/Composables/moneyParser.js';
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue';
import Button from '@/Components/Form/Button.vue';
import Tooltip from '@/Components/Form/Tooltip.vue';
import { useForm } from '@inertiajs/vue3'

import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import Swal from 'sweetalert2'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'

const showPanel = ref(false)
const methodError = ref('')

const togglePanel = () => {
    showPanel.value = !showPanel.value
}

const props = defineProps({
    data: Object || null,
});

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Pengelolaan Pembiayaan', link: '/admin/financings' },
    { name: 'Detail Pembiayaan', link: `/admin/financings/${props.data?.financing?.id}` },
    { name: 'Permohonan Pelunasan' },
];

const form = useForm({
    method: '',
    installment_id: props.data?.financing?.installment.id || '',
});

const submitForm = () => {
    Swal.fire({
        title: 'Apakah Anda yakin ingin mengajukan pelunasan sebelum jatuh tempo?',
        text: "Pastikan semua informasi yang Anda masukkan sudah benar.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, ajukan pelunasan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.post('/admin/financings/repayment', {
                forceFormData: true,
                onSuccess: () => {
                    toast("Permohonan berhasil dikirim!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    });
                },

                onError: (errors) => {
                    toast(("Gagal mengirim permohonan" + errors.message), {
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
</script>

<template>
    <AdminLayout title="Permohonan Pelunasan Sebelum Jatuh Tempo">
        <PageBreadcrumb :page-title="'Permohonan Pelunasan Sebelum Jatuh Tempo'" :items="breadcrumbItems" />
        <div>
            <div class="card-layout px-0!">
                <div class="border-b border-b-stroke px-8 pb-4">
                    <h1 class="card-title">Permohonan Pelunasan Sebelum Jatuh Tempo</h1>
                    <p class="text-gray-400 font-body">Isi detail permohonan pelunasan anda</p>
                </div>
                <div class="card-layout mx-8 mt-8">
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <li>
                            <Info label="Nomor Anggota KSPPS" :value="props.data?.financing?.member.user.user_code" />
                        </li>
                        <li>
                            <Info label="Nama Lengkap" :value="props.data?.financing?.member.user.name" />
                        </li>
                        <li>
                            <Info label="Tanggal Akad" :value="dateParser(props.data?.financing?.akad_date)" />
                        </li>
                        <li>
                            <Info label="Nomor Transaksi" :value="props.data?.financing?.financing_transaction_code" />
                        </li>
                        <li>
                            <Info label="Objek Pembiayaan" :value="props.data?.financing?.financing_item.name" />
                        </li>
                        <li>
                            <Info label="Kategori Objek Pembiayaan"
                                :value="props.data?.financing?.financing_item.product_type.product_type_name" />
                        </li>
                        <li>
                            <Info label="Informasi Cicilan"
                                :value="props.data?.total_paid_installments + ' dari ' + props.data?.financing?.installment.tenor + ' Bulan'" />
                        </li>
                    </ul>
                </div>
                <div class="card-layout mx-8 mt-8">
                    <h1 class="card-title mb-2">Informasi Pembiayaan</h1>
                    <table class="min-w-full">
                        <tbody>
                            <tr class="border-t border-gray-100 dark:border-gray-500">
                                <td class="py-5 px-2 flex-wrap">
                                    <p class="text-dark-text dark:text-gray-400">
                                        Harga Pokok
                                    </p>
                                </td>
                                <td class="py-5 px-2 flex-wrap">
                                    <p class="text-dark-text dark:text-gray-400">
                                        {{ moneyParser(props.data?.financing?.cost_price) }}
                                    </p>
                                </td>
                            </tr>
                            <tr class="border-t border-gray-100 dark:border-gray-500">
                                <td class="py-5 px-2 flex-wrap">
                                    <p class="text-dark-text dark:text-gray-400">
                                        Margin Keuntungan
                                    </p>
                                </td>
                                <td class="py-5 px-2 flex-wrap">
                                    <p class="text-dark-text dark:text-gray-400">
                                        {{ moneyParser(props.data?.financing?.margin_amount) }}
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
                                        {{ moneyParser(props.data?.financing?.down_payment) }}
                                    </p>
                                </td>
                            </tr>
                            <tr class="border-t border-gray-100 dark:border-gray-500">
                                <td class="py-5 px-2 flex-wrap">
                                    <p class="text-dark-text dark:text-gray-400">
                                        Qimah Ismiyyah (Harga Jual Tidak Tunai/Harga Jual Angsuran)
                                    </p>
                                </td>
                                <td class="py-5 px-2 flex items-center gap-1 flex-wrap">
                                    <p class="text-dark-text dark:text-gray-400">
                                        {{ moneyParser(props.data?.qimah_ismiyyah) }}
                                    </p>
                                    <Tooltip>
                                        <div class="grid grid-cols-2 gap-2">
                                            <span class="font-head">Harga Perolehan </span>
                                            <span class="font-medium text-blue-500">
                                                {{ moneyParser(props.data?.financing?.cost_price) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <span class="font-head">Uang Muka </span>
                                            <span class="font-medium text-blue-500">
                                                {{ moneyParser(props.data?.financing?.down_payment) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <span class="font-head">Margin</span>
                                            <span class="font-medium text-blue-500 border-b border-b-gray-300">
                                                {{ moneyParser(props.data?.financing?.margin_amount) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <span class="font-head"></span>
                                            <span class="font-medium text-blue-500">
                                                {{ moneyParser(props.data?.qimah_ismiyyah) }}
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
                                        {{ moneyParser(props.data?.installment_per_month) }}
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
                                        {{ moneyParser(props.data?.qimah_haliyyah) }}
                                    </p>
                                    <Tooltip>
                                        <div class="grid grid-cols-2 gap-2">
                                            <span class="font-head">Tsaman Naqdy</span>
                                            <span class="font-medium text-blue-500">
                                                {{ moneyParser(props.data?.tsaman_naqdy) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <span class="font-head">Margin {{ props.data?.total_paid_installments }}
                                                Bulan</span>
                                            <span class="font-medium text-blue-500 border-b border-b-gray-300">
                                                {{ moneyParser(props.data?.margin_berjalan) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <span class="font-head"></span>
                                            <span class="font-medium text-blue-500">
                                                {{ moneyParser(props.data?.qimah_haliyyah) }}
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
                                        {{ moneyParser(props.data?.total_paid_amount) }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="panel mx-8 mt-4">
                    <button :class="showPanel ? 'rounded-t-2xl! rounded-b-none!' : 'rounded-2xl'"
                        class="card-layout bg-light-bg! font-bold text-secondary! w-full flex items-center justify-between transition-all duration-500 ease-in-out"
                        aria-label="Detail total pelunasan saat ini" @click.prevent="togglePanel">
                        <h1>Total Pelunasan Saat Ini</h1>
                        <div class="flex">
                            <p>
                                {{ moneyParser(props.data?.repayment_total) }}
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
                                <p>{{ moneyParser(props.data?.qimah_haliyyah) }}</p>
                            </li>
                            <li class="flex justify-between border-b border-b-stroke py-4">
                                <h1>Pembayaran Telah Dilakukan</h1>
                                <p>{{ moneyParser(props.data?.total_paid_amount) }}</p>
                            </li>
                            <li class="flex justify-between font-semibold border-b-stroke py-4">
                                <h1>Total Pelunasan (Qimah Haliyyah - Pembayaran Telah Dilakukan)</h1>
                                <p>{{ moneyParser(props.data?.repayment_total) }}</p>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="flex flex-col px-8 pt-6">
                    <BaseInputAdmin v-model="form.method" label="Metode Pelunasan" type="radio" required :selectables="[
                        { value: 'Tunai', text: 'Tunai' },
                        { value: 'Non-Tunai', text: 'Non-Tunai' }
                    ]">
                    </BaseInputAdmin>
                    <p v-if="methodError" class="text-red-500 text-xs mt-2">
                        {{ methodError }}
                    </p>
                    <div class="self-end mt-4">
                        <Button @click="submitForm" variant="secondary">Kirim</Button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
