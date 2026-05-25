<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import Info from '@/Components/Form/Info.vue';
import dateParser from '@/Composables/dateParser.js';
import moneyParser from '@/Composables/moneyParser.js';
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue';
import Button from '@/Components/Form/Button.vue';
import Struk from '@/Components/Savings/Struk.vue'
import { Icon } from '@iconify/vue'
import Tooltip from '@/Components/Form/Tooltip.vue';
import { useForm, usePage } from '@inertiajs/vue3'

const showStruk = ref(false)
const dataStruk = ref(null)
import { ref } from 'vue'
import { toast } from 'vue3-toastify'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'

const props = defineProps({
    data: Object,
});

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Pengelolaan Pembiayaan', link: '/admin/financings' },
    { name: 'Detail Pembiayaan', link: `/admin/financings/${props.data.financing.id}` },
    { name: 'Permohonan Pelunasan' },
];

const showModal = () => {
    document.getElementById('modal').classList.remove('hidden');
};
const hideModal = () => {
    document.getElementById('modal').classList.add('hidden');
};

const form = useForm({
    method: '',
    total_paid: props.data.repayment_total || '',
    tsaman_naqdy: props.data.tsaman_naqdy || '',
    qimah_ismiyyah: props.data.qimah_ismiyyah || '',
    qimah_haliyyah: props.data.qimah_haliyyah || '',
    installment_id: props.data.financing.installment.id || '',
    principal_paid: props.data.principal_paid || '',
    margin_paid: props.data.margin_paid || '',
});

const showPanel = ref(false);
const togglePanel = () => {
    showPanel.value = !showPanel.value;
}

const submitForm = () => {
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
                            <Info label="Nomor Anggota KSPPS" :value="props.data.financing.member.user.user_code" />
                        </li>
                        <li>
                            <Info label="Nama Lengkap" :value="props.data.financing.member.user.name" />
                        </li>
                        <li>
                            <Info label="Tanggal Akad" :value="dateParser(props.data.financing.akad_date)" />
                        </li>
                        <li>
                            <Info label="Nomor Transaksi" :value="props.data.financing.financing_transaction_code" />
                        </li>
                        <li>
                            <Info label="Objek Pembiayaan" :value="props.data.financing.financing_item.name" />
                        </li>
                        <li>
                            <Info label="Kategori Objek Pembiayaan" :value="props.data.financing.financing_item.product_type.product_type_name" />
                        </li>
                        <li>
                            <Info label="Informasi Cicilan"
                                :value="props.data.total_paid_installments + ' dari ' + props.data.financing.installment.tenor + ' Bulan'" />
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
                                        {{ moneyParser(props.data.financing.margin_amount) }}
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
                                        {{ moneyParser(props.data.tsaman_naqdy) }}
                                    </p>
                                    <Tooltip>
                                        <div class="grid grid-cols-2 gap-2">
                                            <span class="font-head">Harga Perolehan </span>
                                            <span class="font-medium text-blue-500">
                                                {{ moneyParser(props.data.financing.cost_price) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <span class="font-head">Uang Muka </span>
                                            <span class="font-medium text-blue-500">
                                                {{ moneyParser(props.data.financing.down_payment) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <span class="font-head">Margin 1 Bulan</span>
                                            <span class="font-medium text-blue-500 border-b border-b-gray-300">
                                                {{ moneyParser(props.data.margin_per_month) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <span class="font-head"></span>
                                            <span class="font-medium text-blue-500">
                                                {{ moneyParser(props.data.tsaman_naqdy) }}
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
                                        {{ moneyParser(props.data.qimah_ismiyyah) }}
                                    </p>
                                    <Tooltip>
                                        <div class="grid grid-cols-2 gap-2">
                                            <span class="font-head">Harga Perolehan </span>
                                            <span class="font-medium text-blue-500">
                                                {{ moneyParser(props.data.financing.cost_price) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <span class="font-head">Uang Muka </span>
                                            <span class="font-medium text-blue-500">
                                                {{ moneyParser(props.data.financing.down_payment) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <span class="font-head">Margin</span>
                                            <span class="font-medium text-blue-500 border-b border-b-gray-300">
                                                {{ moneyParser(props.data.financing.margin_amount) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <span class="font-head"></span>
                                            <span class="font-medium text-blue-500">
                                                {{ moneyParser(props.data.qimah_ismiyyah) }}
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
                                        {{ moneyParser(props.data.installment_per_month) }}
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
                                                {{ moneyParser(props.data.tsaman_naqdy) }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <span class="font-head">Margin {{ props.data.total_paid_installments }}
                                                Bulan</span>
                                            <span class="font-medium text-blue-500 border-b border-b-gray-300">
                                                {{ moneyParser(props.data.margin_berjalan) }}
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
                                        {{ moneyParser(props.data.total_paid_amount) }}
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
                                <p>{{ moneyParser(props.data.total_paid_amount) }}</p>
                            </li>
                            <li class="flex justify-between font-semibold border-b-stroke py-4">
                                <h1>Total Pelunasan (Qimah Haliyyah - Pembayaran Telah Dilakukan)</h1>
                                <p>{{ moneyParser(props.data.repayment_total) }}</p>
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
                    <div class="self-end mt-4">
                        <Button @click="showModal" variant="secondary">Kirim</Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div id="modal" @click.self="hideModal()"
            class="fixed inset-0 bg-black/50 items-center justify-center hidden z-40" :class="{ 'flex': document.getElementById('modal')?.classList.contains('hidden') === false }">
            <div class="bg-accent dark:bg-gray-800 rounded-lg w-125">
                <h1 class="card-title text-white! p-8">Persetujuan Pengguna</h1>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
                    <p class="text-justify font-body">
                        Dengan ini saya menyatakan bahwa saya mengajukan <span class="font-bold">pelunasan sebelum jatuh
                            tempo secara sukarela</span> .
                        Saya
                        memahami bahwa <span class="font-bold">perhitungan pelunasan dilakukan berdasarkan ketentuan
                            syariah yang berlaku</span>. Saya
                        juga
                        menyetujui untuk <span class="font-bold">dikenakan biaya riil dan biaya administrasi</span>
                        sesuai dengan ketentuan yang
                        ditetapkan.<br><br>

                        Saya menyatakan telah membaca, memahami, dan menyetujui seluruh ketentuan di atas tanpa paksaan
                        dari
                        pihak mana pun.
                    </p>
                    <div class="flex justify-between items-center mt-6">
                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input v-model="form.isAgreed" type="checkbox"
                                class="mt-1 h-4 w-4 text-secondary rounded accent-secondary focus:ring-secondary" />
                            <span class="text-secondary dark:text-gray-300">
                                Saya menyetujui persyaratan di atas
                            </span>
                        </label>
                        <Button @click="submitForm" variant="light">Kirim</Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Struk Popup Modal -->
        <Transition name="modal">
            <div
                v-if="showStruk && dataStruk"
                class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50"
                @click.self="showStruk = false"
            >
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden w-full max-w-sm">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Transaksi Berhasil</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Berikut struk pelunasan pembiayaan</p>
                        </div>
                        <button
                            @click="showStruk = false"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                        >
                            <Icon icon="mdi:close" width="22" />
                        </button>
                    </div>
                    <div class="overflow-y-auto max-h-[70vh] p-5 flex justify-center">
                        <Struk mode="repayment" :transaksi="dataStruk" />
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
