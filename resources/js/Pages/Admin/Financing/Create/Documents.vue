<script setup>
import { computed } from 'vue'

const props = defineProps({
    form: Object,
})

const documentsList = [
    { key: 'family_card', label: 'Kartu Keluarga' },
    { key: 'income_slip', label: 'Slip Gaji' },
    { key: 'bank_book', label: 'Buku Tabungan' },
    { key: 'down_payment_proof', label: 'Bukti Pembayaran Uang Muka' },
    { key: 'purchase_receipt', label: 'Bukti Pembelian' },
    { key: 'akad_document', label: 'Dokumen Akad' },
    { key: 'collateral_proof', label: 'Bukti Jaminan' },
]

const availableDocuments = computed(() => {
    if (!props.form?.documents) return []
    
    return documentsList.filter(doc => {
        const filePath = props.form.documents[doc.key]
        return filePath && filePath.trim() !== ''
    })
})

const hasDocuments = computed(() => availableDocuments.value.length > 0)
</script>

<template>
    <div class="card-layout flex flex-col h-fit gap-4">
        <h1 class="card-title">Lampiran</h1>

        <div v-if="hasDocuments" class="flex flex-col gap-2">
            <a
                v-for="doc in availableDocuments"
                :key="doc.key"
                :href="props.form.documents[doc.key]"
                target="_blank"
                class="border flex justify-between p-4 rounded-xl items-center font-body hover:bg-gray-50 hover:border-secondary cursor-pointer transition-all group"
            >
                <p class="font-medium text-dark-text group-hover:text-secondary">{{ doc.label }}</p>
                <span class="icon-[tabler--download] text-secondary group-hover:scale-110 transition-transform"></span>
            </a>
        </div>

        <div v-else class="text-center py-8">
            <div class="icon-[tabler--file-off] w-12 h-12 mx-auto mb-2 opacity-50"></div>
            <p class="text-gray-500 text-sm">Tidak ada dokumen yang tersedia.</p>
        </div>
    </div>
</template>