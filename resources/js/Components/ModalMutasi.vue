<script setup>
import { ref, computed } from 'vue'
import dateParser from '@/Composables/dateParser.js'
import parseCurrencyAmount from '@/Composables/moneyParser.js'

const props = defineProps({
    title: String,
    modalId: { type: String, required: true },
    account: Object,
    financing: Object,
    transactions: Array,
    schedules: Array,
    loading: Boolean,
});

const isOpen = ref(false)

const openModal = () => {
    isOpen.value = true
    document.body.classList.add('overflow-hidden')
}

const closeModal = () => {
    isOpen.value = false
    document.body.classList.remove('overflow-hidden')
}

const displayData = computed(() => {
    return props.transactions || props.schedules || []
})

const isMutasi = computed(() => {
    return props.transactions && props.transactions.length > 0
})

defineExpose({ openModal, closeModal })
</script>

<template>
    <div v-show="isOpen" @click.self="closeModal()"
        class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center pt-44 pb-22 h-screen">
        <div class="bg-white max-h-[80vh] rounded-2xl dark:bg-gray-800 w-full max-w-2xl mx-4"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="`${modalId}-title`">
            <div class="flex justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h1 class="card-title" :id="`${modalId}-title`">{{ title }}</h1>
                <button @click="closeModal()"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
            </div>

            <!-- Mutasi (Transactions) -->
            <ul v-else-if="isMutasi && displayData.length" class="px-6 pb-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                <li v-for="transaction in displayData" :key="transaction.id"
                    class="flex justify-between items-center py-4 border-b border-gray-200 dark:border-gray-700 last:border-0">
                    <div class="flex flex-col gap-1">
                        <span class="font-medium text-dark-text dark:text-white">
                            {{ transaction.saving_description }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ dateParser(transaction.transaction_date) }}
                        </span>
                    </div>
                    <span :class="transaction.transaction_type === 'Penyetoran' ? 'text-green-500' : 'text-red-500'"
                        class="font-semibold">
                        {{ transaction.transaction_type === 'Penyetoran' ? '+' : '-' }}
                        {{ parseCurrencyAmount(transaction.saving_amount) }}
                    </span>
                </li>
            </ul>

            <!-- Riwayat (Payment Schedules) -->
            <ul v-else-if="!isMutasi && displayData.length" class="px-6 pb-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
                <li v-for="schedule in displayData" :key="schedule.id"
                    class="flex justify-between items-center py-4 border-b border-gray-200 dark:border-gray-700 last:border-0">
                    <div class="flex flex-col gap-1 flex-1">
                        <span class="font-medium text-dark-text dark:text-white">
                            Angsuran #{{ schedule.installment_no }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Jatuh Tempo: {{ dateParser(schedule.due_date) }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-1 items-end">
                        <span class="font-semibold text-dark-text dark:text-white">
                            {{ parseCurrencyAmount(schedule.amount) }}
                        </span>
                        <span :class="schedule.status === 'Dibayar' ? 'text-green-500' : schedule.status === 'Terlambat' ? 'text-red-500' : 'text-yellow-500'"
                            class="text-xs font-medium">
                            {{ schedule.status }}
                        </span>
                    </div>
                </li>
            </ul>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400">Tidak ada data tersedia</p>
            </div>
        </div>
    </div>
</template>
