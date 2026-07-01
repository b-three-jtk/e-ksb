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
                v-model="form.tanggal_awal_periode"
                label="Tanggal Awal Periode"
                type="date"
                required
                :disabled="readonly || isProcessing"
            />
            <BaseInputAdmin
                v-model="form.tanggal_akhir_periode"
                label="Tanggal Akhir Periode"
                type="date"
                required
                :disabled="readonly || isProcessing"
            />
            <BaseInputAdmin
                v-model="form.status_tutup_buku"
                label="Status Tutup Buku"
                type="select"
                required
                :disabled="readonly || isProcessing"
            />
            <BaseInputAdmin
                v-model="form.period_effective_date"
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
