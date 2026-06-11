<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Button from '@/Components/Form/Button.vue'

defineProps({
    form: {
        type: Object,
        required: true,
    },
    isProcessing: {
        type: Boolean,
        default: false,
    },
    readonly: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['submit'])
</script>

<template>
    <form @submit.prevent="emit('submit')">
        <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 dark:divide-slate-700">
            <div class="p-6 md:p-8 space-y-6">
                <h4 class="text-lg font-semibold text-slate-800">Simpanan Pokok</h4>
                <BaseInputAdmin
                    :model-value="form.saving_pokok_amount"
                    @update:model-value="form.saving_pokok_amount = $event"
                    label="Nominal"
                    type="text"
                    min="1"
                    step="1"
                    required
                    :disabled="readonly || isProcessing"
                    placeholder="Masukkan nominal simpanan pokok"
                    is-money
                />
                <BaseInputAdmin
                    v-model="form.saving_pokok_effective_date"
                    label="Tanggal Berlaku"
                    type="date"
                    required
                    :disabled="readonly || isProcessing"
                />
            </div>

            <div class="p-6 md:p-8 space-y-6">
                <h4 class="text-lg font-semibold text-slate-800">Simpanan Wajib</h4>
                <BaseInputAdmin
                    :model-value="form.saving_wajib_amount"
                    @update:model-value="form.saving_wajib_amount = $event"
                    label="Nominal"
                    type="text"
                    min="1"
                    step="1"
                    required
                    :disabled="readonly || isProcessing"
                    placeholder="Masukkan nominal simpanan wajib"
                    is-money
                />
                <BaseInputAdmin
                    v-model="form.saving_wajib_effective_date"
                    label="Tanggal Berlaku"
                    type="date"
                    required
                    :disabled="readonly || isProcessing"
                />
            </div>
        </div>

        <div v-if="!readonly" class="px-6 pb-6 md:px-8 md:pb-8 flex justify-end">
            <Button type="submit" size="medium" variant="secondary" :disabled="isProcessing">
                {{ isProcessing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
            </Button>
        </div>
    </form>
</template>
