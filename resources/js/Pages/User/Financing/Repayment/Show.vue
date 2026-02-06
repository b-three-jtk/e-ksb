<script setup>
import Base from '@/Layouts/Base.vue';
import Info from '@/Components/Form/Info.vue';
import dateParser from '@/Composables/dateParser.js';
import moneyParser from '@/Composables/moneyParser.js';
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue';
import Button from '@/Components/Form/Button.vue';
import { toast } from 'vue3-toastify';
import Tooltip from '@/Components/Form/Tooltip.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    data: Object,
});

const showModal = () => {
    document.getElementById('modal').classList.remove('hidden');
};
const hideModal = () => {
    document.getElementById('modal').classList.add('hidden');
};

const form = useForm({
    isAgreed: false,
    loan_id: props.data.financing.loan.id,
    method: '',
    installment_number: props.data.total_paid_installments + 1,
    repayment_total: props.data.repayment_total,
    principal_paid: props.data.financing.loan.remaining_principal,
    margin_paid: props.data.repayment_total - props.data.financing.loan.remaining_principal,
})

const showPanel = ref(false);
const togglePanel = (event) => {
    showPanel.value = !showPanel.value;
}

const submitForm = () => {
    // Validasi checkbox persetujuan
    if (form.isAgreed !== true) {
        toast("Anda harus menyetujui persyaratan terlebih dahulu!", {
            "type": "error",
            "position": "bottom-right",
            "transition": "slide",
        })
        return;
    }

    // Validasi metode pelunasan
    if (!form.method) {
        toast("Pilih metode pelunasan terlebih dahulu!", {
            "type": "error",
            "position": "bottom-right",
            "transition": "slide",
        })
        return;
    }
    form.post('/user/financing/repayment/submit', {
        forceFormData: true,
        onSuccess: () => {
            toast("Permohonan berhasil dikirim!", {
                "type": "success",
                "position": "bottom-right",
                "transition": "slide",
                "dangerouslyHTMLString": true
            }).then(() => {
                window.location.href = route('user.userDashboard')
            })
        },

        onError: (errors) => {
            toast(("Gagal mengirim permohonan:" + Object.values(errors).flat().join(' ')), {
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
    <Base title="Permohonan Pelunasan Sebelum Jatuh Tempo">
        <div class="pt-30 md:pb-20 px-0 md:px-72">
            <div class="card-layout px-0!">
                <div class="border-b border-b-stroke px-8 pb-4">
                    <h1 class="card-title">Permohonan Pelunasan Sebelum Jatuh Tempo</h1>
                    <p class="text-gray-400 font-body">Isi detail permohonan pelunasan anda</p>
                </div>
                <div class="card-layout mx-8 mt-8">
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <li>
                            <Info label="Nomor Anggota KSPPS" :value="props.data.financing.user.member_number" />
                        </li>
                        <li>
                            <Info label="Nama Lengkap" :value="props.data.financing.user.name" />
                        </li>
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
                                            <span class="font-head">Margin {{ props.data.total_paid_installments + 1 }}
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
                <div class="panel mx-8 mt-4">
                    <button :class="showPanel ? 'rounded-t-2xl! rounded-b-none!' : 'rounded-2xl'"
                        class="card-layout bg-light-bg! font-bold text-secondary! w-full flex items-center justify-between transition-all duration-500 ease-in-out"
                        :aria-label="ariaTitle" @click.prevent="togglePanel">
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
                                <h1>Qimah Haliyah</h1>
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
                <div class="flex flex-col px-8 pt-6">
                    <BaseInputAdmin v-model="form.method" label="Metode Pelunasan" type="radio" required :selectables="[
                        { value: 'Tunai', text: 'Tunai' },
                        { value: 'Non-Tunai', text: 'Non-Tunai' }
                    ]">
                    </BaseInputAdmin>
                    <!-- <div class="space-y-6 w-1/2 mt-4" v-if="form.method === 'Non-Tunai'">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                Bukti Pembayaran
                            </label>
                            <input type="file"
                                class="focus:border-ring-brand-300 font-body w-full h-11 w-fit overflow-hidden rounded-lg border border-gray-300 bg-transparent text-sm text-gray-500 shadow-theme-xs transition-colors file:mr-5 file:border-collapse file:cursor-pointer file:rounded-l-lg file:border-0 file:border-r file:border-solid file:border-gray-200 file:bg-gray-200 file:py-3 file:pl-3.5 file:pr-3 file:text-sm file:text-gray-700 placeholder:text-gray-400 hover:file:bg-gray-100 focus:outline-hidden focus:file:ring-brand-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:text-white/90 dark:file:border-gray-800 dark:file:bg-white/[0.03] dark:file:text-gray-400 dark:placeholder:text-gray-400" />
                        </div>
                    </div> -->
                    <div class="self-end mt-4">
                        <Button @click="showModal" variant="secondary">Kirim</Button>
                    </div>
                </div>
            </div>
        </div>
        <div id="modal" @click.self="hideModal()"
            class="fixed inset-0 bg-black/50 flex items-center justify-center hidden">
            <div class="bg-blue-accent dark:bg-gray-800 rounded-lg w-125">
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
    </Base>
</template>
