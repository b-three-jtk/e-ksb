<script setup>
const props = defineProps({
    form: Object,
})

const documentsList = [
    { key: 'family_card', label: 'Kartu Keluarga' },
    { key: 'income_slip', label: 'Slip Gaji' },
    { key: 'bank_book', label: 'Buku Tabungan' },
    { key: 'down_payment_proof', label: 'Bukti Pembayaran Uang Muka' },
    { key: 'procurement_proof', label: 'Bukti Pengadaan' },
    { key: 'akad_document', label: 'Dokumen Akad' },
    { key: 'collateral_proof', label: 'Bukti Jaminan' },
]
</script>

<template>
    <div class="card-layout flex flex-col h-fit gap-4">
        <h1 class="card-title">Lampiran</h1>

        <!-- Document list -->
        <div v-if="hasDocuments" class="flex flex-col gap-2">
            <a
                v-for="doc in documentsList"
                v-show="props.form?.documents?.[doc.key]"
                :href="props.form?.documents?.[doc.key]"
                target="_blank"
                rel="noopener noreferrer"
                @click.prevent="handleDownload(props.form?.documents?.[doc.key], doc.label)"
                class="border flex justify-between p-4 rounded-xl items-center font-body hover:bg-gray-50 hover:border-secondary cursor-pointer transition-all"
            >
                <p class="font-medium">{{ doc.label }}</p>
                <span class="icon-[tabler--download] text-green-500"></span>
            </a>
        </div>

        <!-- Null state -->
        <div v-else class="text-center py-8">
            <p class="text-gray-500">Tidak ada dokumen yang tersedia.</p>
        </div>
    </div>
</template>
