<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'withdrawal',
    },
    inline: {
        type: Boolean,
        default: false,
    },
    data: {
        type: Object,
        default: () => ({
            memberName: '',
            memberNumber: '',
            savingType: '',
            method: 'Tunai',
            amount: 0,
            balance: 0,
            date: new Date().toISOString().split('T')[0],
            bankName: '',
            accountName: '',
            accountNumber: '',
            tenorMonths: '',
            targetAmount: 0,
            officerName: '',
        }),
    },
    loading: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['confirm', 'close'])

const agreed = ref(false)
const isDeposit = computed(() => props.type === 'deposit')

const titleText = computed(() =>
    isDeposit.value ? 'Konfirmasi Penyetoran' : 'Konfirmasi Penarikan'
)

const subtitleText = computed(() =>
    isDeposit.value
        ? 'Pastikan data sudah benar sebelum posting'
        : 'Verifikasi data penarikan simpanan'
)

const agreementText = computed(() =>
    isDeposit.value
        ? 'Saya menyatakan data di atas sudah benar dan siap diposting ke rekening simpanan anggota. Transaksi tidak dapat dibatalkan.'
        : 'Saya menyatakan data di atas sudah benar dan siap melakukan penarikan simpanan. Transaksi tidak dapat dibatalkan.'
)

const buttonClass = computed(() =>
    isDeposit.value
        ? 'bg-blue-600 text-white hover:bg-blue-700'
        : 'bg-green-600 text-white hover:bg-green-700'
)

const finalBalance = computed(() => {
  const balance = Number(props.data.balance ?? 0)
  const amount = Number(props.data.amount ?? 0)

  return isDeposit.value
    ? balance + amount
    : balance - amount
})

watch(() => props.isOpen, (isOpen) => {
    if (!isOpen) {
        agreed.value = false
    }
})

function formatRp(val) {
    return Number(val || 0).toLocaleString('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    })
}

function formatDate(dateStr) {
    if (!dateStr) return '-'

    const date = new Date(dateStr)
    if (Number.isNaN(date.getTime())) return dateStr

    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

function initials(name = '') {
    return String(name)
        .split(' ')
        .slice(0, 2)
        .map((word) => word[0]?.toUpperCase() || '')
        .join('')
}

function confirm() {
    if (!agreed.value || props.loading) return
    emit('confirm')
    agreed.value = false
}

function close() {
    if (props.loading) return
    emit('close')
    agreed.value = false
}
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="isOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50"
                @click.self="close">
                <div class="bg-white dark:bg-gray-800 rounded-xl max-w-md w-full shadow-xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ titleText }}</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ subtitleText }}</p>
                    </div>

                    <div class="p-5 space-y-4">

                        <!-- Anggota -->
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Anggota</div>
                            <div class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ data.memberName }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ data.memberNumber }}
                            </div>
                        </div>

                        <!-- Detail -->
                        <table class="w-full text-sm border-b border-gray-100 dark:border-gray-700 pb-2">
                            <tbody>
                                <tr>
                                    <td class="py-2 text-gray-500">Jenis Simpanan</td>
                                    <td class="text-right font-medium">{{ data.savingType }}</td>
                                </tr>

                                <tr v-if="data.tenorMonths">
                                    <td class="py-2 text-gray-500">Jangka Waktu</td>
                                    <td class="text-right">{{ data.tenorMonths }} bulan</td>
                                </tr>

                                <tr v-if="data.targetAmount">
                                    <td class="py-2 text-gray-500">Target</td>
                                    <td class="text-right">{{ formatRp(data.targetAmount) }}</td>
                                </tr>

                                <tr>
                                    <td class="py-2 text-gray-500">Metode</td>
                                    <td class="text-right">{{ data.method }}</td>
                                </tr>

                                <tr>
                                    <td class="py-2 text-gray-500">
                                        {{ isDeposit ? 'Tanggal Setor' : 'Tanggal Penarikan' }}
                                    </td>
                                    <td class="text-right">{{ formatDate(data.date) }}</td>
                                </tr>

                                <tr v-if="data.officerName">
                                    <td class="py-2 text-gray-500">Oleh</td>
                                    <td class="text-right">{{ data.officerName }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Nominal -->
                        <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                            <div class="text-sm text-gray-600 mb-1">
                                {{ isDeposit ? 'Nominal Setoran' : 'Nominal Penarikan' }}
                            </div>

                            <div
                                class="text-3xl font-bold"
                                :class="isDeposit ? 'text-green-600' : 'text-green-600'"
                            >
                                {{ formatRp(data.amount) }}
                            </div>

                            <div class="text-xs text-gray-500 mt-2">
                                <template v-if="data.balance">
                                    {{
                                    isDeposit
                                        ? 'Saldo Setelah Penyetoran'
                                        : 'Saldo Setelah Penarikan'
                                    }}:
                                    {{ formatRp(finalBalance) }}
                                </template>

                                <template v-else>
                                    Saldo Awal: {{ formatRp(data.balance) }}
                                </template>
                            </div>
                        </div>

                        <!-- Untuk enarikan non-tunai) -->
                        <div v-if="!isDeposit && data.method === 'Non-Tunai'"
                            class="border-b border-gray-100 dark:border-gray-700 pb-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">Rekening Tujuan</div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded text-sm space-y-1">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Bank:</span> 
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ data.bankName }}</span>
                                </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Atas Nama:</span> 
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ data.accountName }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">No. Rekening:</span> 
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ data.accountNumber }}</span>
                                </div>
                            </div>
                        </div>

                        <label
                            class="flex items-start gap-3 cursor-pointer p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <input v-model="agreed" type="checkbox" class="mt-0.5 accent-primary" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ agreementText }}</span>
                        </label>
                    </div>

                    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button @click="close" :disabled="loading"
                            class="px-5 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            Batal
                        </button>
                        <button @click="confirm" :disabled="!agreed || loading"
                            class="px-6 py-2 text-sm text-white bg-primary hover:bg-secondary font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            {{ loading ? 'Memproses...' : 'Posting Sekarang' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
