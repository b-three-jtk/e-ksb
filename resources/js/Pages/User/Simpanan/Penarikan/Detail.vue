<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Base from '../../../../Layouts/Base.vue';
import StepIndicator from '../../../../Components/StepIndicator.vue';
import BaseInput from '../../../../Components/Form/BaseInput.vue';
import BaseSelect from '../../../../Components/Form/BaseSelect.vue';

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
    maxBalance: {
        type: Number,
        required: true
    },
    savedAccounts: {
        type: Array,
        default: () => []
    },
    previousData: {
        type: Object,
        default: () => ({})
    }
});

const steps = [
    { key: 'informasi', label: 'Informasi' },
    { key: 'detail', label: 'Detail Penarikan' },
    { key: 'pernyataan', label: 'Pernyataan' }
];

const currentStep = 1;

const form = useForm({
    amount: props.previousData.amount || '',
    description: props.previousData.description || '',
    method: props.previousData.method || 'Tunai',
    bank_name: props.previousData.bank_name || '',
    account_number: props.previousData.account_number || '',
    account_name: props.previousData.account_name || ''
});

const displayAmount = ref(props.previousData.amount ? formatNumber(props.previousData.amount) : '');
const validationErrors = ref({});
const selectedAccountOption = ref('');

const showBankFields = computed(() => form.method === 'Non-Tunai');

const accountOptions = computed(() => {
    const options = [{ value: 'new', label: 'Tambah Rekening Baru' }];
    props.savedAccounts.forEach(account => {
        options.push({
            value: account.account_number,
            label: `${account.bank_name} - ${account.account_number} (${account.account_name})`
        });
    });
    return options;
});

function formatNumber(value) {
    if (!value) return '';
    const num = value.toString().replace(/\D/g, '');
    return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function handleAmountInput(event) {
    const input = event.target.value.replace(/\D/g, '');
    form.amount = input;
    displayAmount.value = formatNumber(input);
    if (validationErrors.value.amount) {
        delete validationErrors.value.amount;
    }
}

const handleBack = () => {
    router.get(route('user.simpanan.withdraw.info'));
};

const validateForm = () => {
    const errors = {};

    if (!form.amount || parseFloat(form.amount) <= 0) {
        errors.amount = 'Jumlah yang ingin ditarik harus lebih dari 0';
    } else if (parseFloat(form.amount) > props.maxBalance) {
        errors.amount = 'Jumlah penarikan melebihi saldo yang tersedia';
    }

    if (!form.description) {
        errors.description = 'Keterangan penarikan harus diisi';
    }

    if (form.method === 'Non-Tunai') {
        if (!form.bank_name) {
            errors.bank_name = 'Nama bank harus dipilih';
        }
        if (!form.account_number) {
            errors.account_number = 'Nomor rekening harus diisi';
        }
        if (!form.account_name) {
            errors.account_name = 'Atas nama rekening harus diisi';
        }
    }

    validationErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const handleNext = () => {
    if (!validateForm()) {
        return;
    }

    form.post(route('user.simpanan.withdraw.statement'), {
        onError: (errors) => {
            validationErrors.value = errors;
        }
    });
};

// Watch for account selection changes
watch(selectedAccountOption, (newValue) => {
    if (newValue && newValue !== 'new') {
        const selectedAccount = props.savedAccounts.find(acc => acc.account_number === newValue);
        if (selectedAccount) {
            form.bank_name = selectedAccount.bank_name;
            form.account_number = selectedAccount.account_number;
            form.account_name = selectedAccount.account_name;
        }
    } else if (newValue === 'new') {
        form.bank_name = '';
        form.account_number = '';
        form.account_name = '';
    }
});

watch(() => form.method, (newMethod) => {
    if (newMethod === 'Tunai') {
        form.bank_name = '';
        form.account_number = '';
        form.account_name = '';
        selectedAccountOption.value = '';
    } else if (newMethod === 'Non-Tunai' && props.savedAccounts.length > 0) {
        selectedAccountOption.value = props.savedAccounts[0].account_number;
    } else {
        selectedAccountOption.value = 'new';
    }
});

// Watch for manual changes to account fields
watch(() => form.account_number, (newValue) => {
    if (validationErrors.value.account_number) {
        delete validationErrors.value.account_number;
    }
});

watch(() => form.bank_name, (newValue) => {
    if (validationErrors.value.bank_name) {
        delete validationErrors.value.bank_name;
    }
});

watch(() => form.account_name, (newValue) => {
    if (validationErrors.value.account_name) {
        delete validationErrors.value.account_name;
    }
});

watch(() => form.description, (newValue) => {
    if (validationErrors.value.description) {
        delete validationErrors.value.description;
    }
});
</script>

<template>
    <Base>
        <div class="font-head min-h-screen bg-white dark:bg-gray-900 transition-colors py-8">
            <div class="max-w-5xl mx-auto px-6">
                <StepIndicator :steps="steps" :currentStep="currentStep" />

                <div class="bg-white dark:bg-gray-800 rounded-xl border-2 border-gray-300 dark:border-gray-600 p-0 overflow-hidden">
                    <div class="px-8 py-6 border-b-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800">
                        <h2 class="text-lg font-bold font-head text-gray-800 dark:text-gray-100 mb-1">
                             DETAIL PENARIKAN
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-body">
                            Tentukan jumlah yang ingin ditarik, alasan penarikan, serta metode pencairan (tunai atau transfer bank)
                        </p>
                    </div>

                    <div class="space-y-6 px-8 py-6">
                        <div class="flex items-start gap-3">
                            <span class="text-gray-700 dark:text-gray-300 font-medium pt-3">Rp</span>
                            <div class="relative flex-1">
                                <input
                                    :value="displayAmount"
                                    @input="handleAmountInput"
                                    type="text"
                                    placeholder=" "
                                    required
                                    autocomplete="off"
                                    :class="[
                                        'peer h-12 w-full rounded-lg border bg-transparent px-4 pt-2 pb-2 text-sm text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-brand-500/20',
                                        validationErrors.amount ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-brand-500'
                                    ]"
                                />
                                <label
                                    class="pointer-events-none absolute left-3 top-1/2 z-10
                                           -translate-y-1/2 px-1
                                           bg-white dark:bg-gray-800 text-sm text-gray-400
                                           transition-all duration-200
                                           peer-focus:top-0 peer-focus:text-xs
                                           peer-not-placeholder-shown:top-0
                                           peer-not-placeholder-shown:text-xs"
                                >
                                    Jumlah yang Ingin Ditarik
                                    <span class="text-error-500">*</span>
                                </label>
                                <p v-if="validationErrors.amount" class="mt-1 text-sm text-red-500">
                                    {{ validationErrors.amount }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <BaseInput 
                                v-model="form.description"
                                label="Keterangan Penarikan"
                                type="text"
                                :required="true"
                                :error="validationErrors.description"
                            />
                        </div>

                        <div class="flex flex-col gap-3">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Metode Penarikan
                            </label>
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        v-model="form.method"
                                        value="Tunai"
                                        class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-gray-700 dark:text-gray-300">Tunai</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        v-model="form.method"
                                        value="Non-Tunai"
                                        class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-gray-700 dark:text-gray-300">Non-Tunai</span>
                                </label>
                            </div>
                        </div>

                        <transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="opacity-0 -translate-y-2"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-2"
                        >
                            <div v-if="showBankFields" class="space-y-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <!-- Account Selection -->
                                <div v-if="savedAccounts.length > 0" class="flex flex-col gap-3">
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Pilih Rekening
                                    </label>
                                    <select
                                        v-model="selectedAccountOption"
                                        class="peer h-12 w-full rounded-lg border border-gray-300
                                                bg-white dark:bg-gray-800 px-4 pt-2 pb-2 text-sm
                                                text-gray-800 dark:text-gray-200
                                                focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                                    >
                                        <option v-for="option in accountOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>

                                <BaseSelect
                                    v-model="form.bank_name"
                                    label="Nama Bank"
                                    :required="true"
                                    :error="validationErrors.bank_name"
                                    :disabled="selectedAccountOption && selectedAccountOption !== 'new'"
                                >
                                    <option value="BCA">BCA</option>
                                    <option value="BNI">BNI</option>
                                    <option value="BRI">BRI</option>
                                    <option value="Mandiri">Mandiri</option>
                                    <option value="BTN">BTN</option>
                                    <option value="CIMB Niaga">CIMB Niaga</option>
                                    <option value="Permata">Permata</option>
                                    <option value="Danamon">Danamon</option>
                                    <option value="BSI">BSI</option>
                                    <option value="BJB">BJB</option>
                                </BaseSelect>

                                <BaseInput 
                                    v-model="form.account_number"
                                    label="Nomor Rekening"
                                    type="text"
                                    :required="true"
                                    :error="validationErrors.account_number"
                                    :disabled="selectedAccountOption && selectedAccountOption !== 'new'"
                                />

                                <BaseInput 
                                    v-model="form.account_name"
                                    label="Atas Nama Rekening"
                                    type="text"
                                    :required="true"
                                    :error="validationErrors.account_name"
                                    :disabled="selectedAccountOption && selectedAccountOption !== 'new'"
                                />
                            </div>
                        </transition>
                    </div>

                    <div class="flex justify-between mt-8 pt-6 px-8 py-6">
                        <button
                            @click="handleBack"
                            class="border border-blue-600 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 px-8 py-2 rounded-lg font-semibold font-head transition-colors"
                        >
                            Kembali
                        </button>
                        <button
                            @click="handleNext"
                            class="bg-blue-900 hover:bg-blue-700 text-white px-8 py-2 rounded-lg font-semibold font-head transition-colors"
                        >
                            Lanjut
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Base>
</template>
