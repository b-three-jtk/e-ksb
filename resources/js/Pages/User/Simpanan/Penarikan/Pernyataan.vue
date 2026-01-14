<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';
import Swal from 'sweetalert2';
import Base from '../../../../Layouts/Base.vue';
import StepIndicator from '../../../../Components/StepIndicator.vue';

const route = (name, params) => {
    const routes = {
        'user.simpanan.withdraw.info': '/user/simpanan/penarikan',
        'user.simpanan.withdraw.detail': '/user/simpanan/penarikan/detail',
        'user.simpanan.withdraw.statement': '/user/simpanan/penarikan/pernyataan',
        'user.simpanan.withdraw.submit': '/user/simpanan/penarikan/submit'
    };
    return routes[name] || '/';
};

const props = defineProps({
    withdrawalData: {
        type: Object,
        required: true
    }
});

const steps = [
    { key: 'informasi', label: 'Informasi' },
    { key: 'detail', label: 'Detail Penarikan' },
    { key: 'pernyataan', label: 'Pernyataan' }
];

const currentStep = 2;

const agreed = ref(false);

const form = useForm({
    amount: props.withdrawalData.amount,
    description: props.withdrawalData.description,
    method: props.withdrawalData.method,
    bank_name: props.withdrawalData.bank_name,
    account_number: props.withdrawalData.account_number,
    account_name: props.withdrawalData.account_name,
    agreed: false
});

const isSubmitDisabled = computed(() => !agreed.value || form.processing);

const handleBack = () => {
    router.get(route('user.simpanan.withdraw.detail'));
};

const handleSubmit = () => {
    if (!agreed.value) {
        toast('Anda harus menyetujui pernyataan sebelum mengirim', {
            type: 'warning',
            position: 'bottom-right',
            transition: 'slide',
            autoClose: 3000,
        });
        return;
    }

    Swal.fire({
        title: 'Konfirmasi Penarikan',
        html: `Apakah Anda yakin ingin melakukan penarikan simpanan sebesar <strong>Rp ${formatNumber(props.withdrawalData.amount)}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Ajukan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#1e3a8a',
        cancelButtonColor: '#6b7280',
    }).then((result) => {
        if (result.isConfirmed) {
            form.agreed = agreed.value;
            
            form.post(route('user.simpanan.withdraw.submit'), {
                onSuccess: () => {
                    toast('Permohonan penarikan simpanan berhasil diajukan dan sedang dalam peninjauan admin', {
                        type: 'success',
                        position: 'bottom-right',
                        transition: 'slide',
                        autoClose: 5000,
                    });
                },
                onError: (errors) => {
                    console.error('Submission error:', errors);
                    let errorMessage = 'Terjadi kesalahan saat mengirim permohonan. Silakan coba lagi.';
                    
                    if (errors.error) {
                        errorMessage = errors.error;
                    } else if (errors.amount) {
                        errorMessage = errors.amount;
                    }
                    
                    toast(errorMessage, {
                        type: 'error',
                        position: 'bottom-right',
                        transition: 'slide',
                        autoClose: 5000,
                    });
                }
            });
        }
    });
};

function formatNumber(value) {
    if (!value) return '0';
    const num = value.toString().replace(/\D/g, '');
    return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
</script>

<template>
    <Base>
        <div class="font-head min-h-screen bg-white dark:bg-gray-900 transition-colors py-8">
            <div class="max-w-5xl mx-auto px-6">
                <StepIndicator :steps="steps" :currentStep="currentStep" />

                <div class="bg-white dark:bg-gray-800 rounded-xl border-2 border-gray-300 dark:border-gray-600 p-0 overflow-hidden">
                    <div class="px-8 py-6 border-b-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
                        <h2 class="text-lg font-bold font-head text-gray-800 dark:text-gray-100 mb-1">
                            PERNYATAAN PENARIKAN SIMPANAN SUKARELA
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-body">
                            Baca dan setujui pernyataan bahwa penarikan dilakukan secara sadar, tanpa paksaan, serta data yang diisi benar dan saya bertanggung jawab penuh.
                        </p>
                    </div>

                    <div class="bg-blue-50 dark:bg-gray-700/50 rounded-xl px-8 py-6 mb-6">
                        <p class="text-gray-700 dark:text-gray-300 font-medium mb-4">
                            Dengan ini saya menyatakan bahwa,
                        </p>
                        <ol class="space-y-3 text-gray-700 dark:text-gray-300 list-decimal list-inside">
                            <li>Penarikan simpanan sukarela ini atas kehendak sendiri tanpa paksaan.</li>
                            <li>Data yang saya isi benar dan saya bertanggung jawab penuh.</li>
                            <li>Saya memahami bahwa simpanan sukarela yang ditarik tidak dapat dikembalikan ke saldo sebelumnya.</li>
                            <li>Dana akan dicairkan paling lambat 3-7 hari kerja setelah pengajuan disetujui.</li>
                        </ol>
                    </div>

                    <div class="flex items-start gap-3 mb-6 px-8 py-6">
                        <input
                            type="checkbox"
                            id="agreement"
                            v-model="agreed"
                            class="mt-1 w-5 h-5 accent-green-600 rounded cursor-pointer"
                        />
                        <label 
                            for="agreement" 
                            class="text-gray-700 dark:text-gray-300 cursor-pointer select-none"
                        >
                            Saya setuju dengan pernyataan di atas dan bersedia menerima dana sesuai metode yang dipilih
                        </label>
                    </div>

                    <div class="flex justify-between mt-8 pt-6 px-8 py-6">
                        <button
                            @click="handleBack"
                            :disabled="form.processing"
                            class="border border-blue-600 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 px-8 py-2 rounded-lg font-semibold font-head transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Kembali
                        </button>
                        <button
                            @click="handleSubmit"
                            :disabled="isSubmitDisabled"
                            class="bg-blue-900 hover:bg-blue-700 text-white px-8 py-2 rounded-lg font-semibold font-head transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Mengirim...</span>
                            <span v-else>Kirim</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Base>
</template>
