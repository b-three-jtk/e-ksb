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
    <form class="space-y-6" @submit.prevent="emit('submit')">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <BaseInputAdmin
                :model-value="form.murabahah_margin_percentage"
                @update:model-value="form.murabahah_margin_percentage = $event"
                label="Persentase Margin"
                type="number"
                min="0"
                max="100"
                step="0.01"
                required
                :disabled="readonly || isProcessing"
                placeholder="Masukkan persentase margin koperasi"
            />
            <BaseInputAdmin
                v-model="form.effective_date"
                label="Tanggal Berlaku"
                type="date"
                required
                :disabled="readonly || isProcessing"
            />
        </div>

        <div v-if="!readonly" class="flex justify-end">
            <Button type="submit" size="medium" variant="secondary" :disabled="isProcessing">
                {{ isProcessing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
            </Button>
        </div>
    </form>
</template>
