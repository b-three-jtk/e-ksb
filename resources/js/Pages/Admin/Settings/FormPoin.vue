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
        <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 dark:divide-slate-700">
            <div class="grid grid-cols-1 gap-2 pr-6">
                <h1 class="card-title pb-4">Poin Simpanan</h1>
                <BaseInputAdmin :model-value="form.saving_point_amount"
                    @update:model-value="form.saving_point_amount = $event" label="Jumlah Simpanan" type="text" min="1"
                    step="1" required :disabled="readonly || isProcessing"
                    placeholder="Masukkan jumlah simpanan yang diperlukan" is-money />
                <BaseInputAdmin :model-value="form.saving_point_reward"
                    @update:model-value="form.saving_point_reward = $event" label="Poin yang Diperoleh" type="number"
                    min="1" step="1" required :disabled="readonly || isProcessing"
                    placeholder="Masukkan jumlah poin yang diberikan" />
                <BaseInputAdmin v-model="form.effective_date" label="Tanggal Berlaku" type="date" required
                    :disabled="readonly || isProcessing" />
            </div>

            <div class="grid grid-cols-1 gap-2 pl-6">
                <h1 class="card-title pb-4">Poin Murabahah</h1>
                <BaseInputAdmin :model-value="form.murabaha_point_amount"
                    @update:model-value="form.murabaha_point_amount = $event" label="Jumlah Margin Dibayarkan"
                    type="text" min="1" step="1" required :disabled="readonly || isProcessing"
                    placeholder="Masukkan jumlah margin yang diperlukan" is-money />
                <BaseInputAdmin :model-value="form.murabaha_point_reward"
                    @update:model-value="form.murabaha_point_reward = $event" label="Poin yang Diperoleh" type="number"
                    min="1" step="1" required :disabled="readonly || isProcessing"
                    placeholder="Masukkan jumlah poin yang diberikan" />
                <BaseInputAdmin v-model="form.murabaha_effective_date" label="Tanggal Berlaku" type="date" required
                    :disabled="readonly || isProcessing" />
            </div>
        </div>

        <div
            class="rounded-xl border border-emerald-200 dark:border-slate-700 bg-emerald-50 dark:bg-slate-800 px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
            Contoh: jika jumlah simpanan ditetapkan Rp100.000 dan poin yang diperoleh 1, maka setiap kelipatan penuh
            Rp100.000 akan mendapatkan 1 poin.
        </div>

        <div v-if="!readonly" class="flex justify-end">
            <Button type="submit" size="medium" variant="secondary" :disabled="isProcessing">
                {{ isProcessing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
            </Button>
        </div>
    </form>
</template>
