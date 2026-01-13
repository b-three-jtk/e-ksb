<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Base from '../../../../Layouts/Base.vue';
import StepIndicator from '../../../../Components/StepIndicator.vue';
import FieldRow from '../../../../Components/Form/FieldRow.vue';

const route = (name, params) => {
    const routes = {
        'user.simpanan.withdraw.detail': '/user/simpanan/penarikan/detail',
        'user.simpanan.withdraw.statement': '/user/simpanan/penarikan/pernyataan',
        'user.simpanan.withdraw.submit': '/user/simpanan/penarikan/submit'
    };
    return routes[name] || '/';
};

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    savingAccount: {
        type: Object,
        required: true
    },
    withdrawalDate: {
        type: String,
        required: true
    }
});

const steps = [
    { key: 'informasi', label: 'Informasi' },
    { key: 'detail', label: 'Detail Penarikan' },
    { key: 'pernyataan', label: 'Pernyataan' }
];

const currentStep = 0;

const rupiah = (value) => 
    'Rp ' + new Intl.NumberFormat('id-ID').format(value ?? 0);

const handleNext = () => {
    router.get(route('user.simpanan.withdraw.detail'), {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <Base>
        <div class="font-head min-h-screen bg-white dark:bg-gray-900 transition-colors py-8">
            <div class="max-w-5xl mx-auto px-6">
                <StepIndicator :steps="steps" :currentStep="currentStep" />

                <div class="bg-white dark:bg-gray-800 rounded-xl border-2 border-gray-300 dark:border-gray-600 p-0 overflow-hidden">
                    <div class="px-8 py-6 border-b-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
                        <h2 class="text-lg font-bold font-head text-gray-800 dark:text-gray-100 mb-1">
                            INFORMASI SALDO DAN ANGGOTA
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-body">
                            Periksa kembali ringkasan informasi keanggotaan dan saldo anda
                        </p>
                    </div>

                    <div class="px-8 py-6">
                        <div class="space-y-8">
                            <FieldRow 
                                label="Nama Anggota" 
                                :value="user.name"
                            />

                            <FieldRow 
                                label="Nomor Anggota" 
                                :value="user.member_number"
                            />

                            <FieldRow 
                                label="Saldo Simpanan Sukarela" 
                                :value="rupiah(savingAccount.balance)"
                            />

                            <FieldRow 
                                label="Tanggal Pengajuan Penarikan" 
                                :value="withdrawalDate"
                            />
                        </div>
                    </div>
                    
                    <div class="px-8 py-6 flex justify-end bg-white dark:bg-gray-800">
                        <button
                            @click="handleNext"
                            class="bg-blue-900 hover:bg-blue-800 font-head text-white px-8 py-2 rounded-lg font-semibold transition-colors"
                        >
                            Lanjut
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Base>
</template>
