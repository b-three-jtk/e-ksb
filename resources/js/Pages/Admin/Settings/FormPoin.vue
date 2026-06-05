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
})

defineEmits(['submit'])
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <BaseInputAdmin
                :model-value="form.saving_point_amount"
                @update:model-value="form.saving_point_amount = $event"
                label="Jumlah Simpanan"
                type="text"
                min="1"
                step="1"
                required
                :is-disabled="isProcessing"
                placeholder="Masukkan jumlah simpanan yang diperlukan"
                is-money
            />
            <BaseInputAdmin
                :model-value="form.saving_point_reward"
                @update:model-value="form.saving_point_reward = $event"
                label="Poin yang Diperoleh"
                type="number"
                min="1"
                step="1"
                required
                :is-disabled="isProcessing"
                placeholder="Masukkan jumlah poin yang diberikan"
            />
        </div>

        <div class="max-w-md">
            <BaseInputAdmin
                :model-value="form.effective_date"
                @update:model-value="form.effective_date = $event"
                label="Tanggal Berlaku"
                type="date"
                required
                :is-disabled="isProcessing"
            />
        </div>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-slate-600">
            Contoh: jika jumlah simpanan ditetapkan Rp100.000 dan poin yang diperoleh 1, maka setiap kelipatan penuh Rp100.000 akan mendapatkan 1 poin.
        </div>

        <div class="flex justify-end">
            <Button type="submit" size="medium" variant="success" :disabled="isProcessing">
                {{ isProcessing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
            </Button>
        </div>
    </form>
</template>
