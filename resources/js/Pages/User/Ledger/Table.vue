<script setup lang="ts">
import { computed, ref } from 'vue'
import { formatCurrency } from '../../../utils/currency'

interface Transaction {
    [key: string]: any
}

interface Column {
    key: string
    label: string
    formatter?: (value: any) => string
}

const props = defineProps<{
    transactions: Transaction[]
    columns: Column[]
    memberInfo?: {
        nama?: string
        no_anggota?: string
    }
}>()

const selectedTransaction = ref<Transaction | null>(null)

const getNominalValue = (row: Transaction) => {
    const debit = Number(row.debit || 0)
    const kredit = Number(row.kredit || 0)
    return debit - kredit
}

const getNominalClass = (value: number) => {
    if (value > 0) return 'text-emerald-600 dark:text-emerald-400'
    if (value < 0) return 'text-rose-500 dark:text-rose-400'
    return 'text-gray-400 dark:text-gray-500'
}

const formatNominal = (value: number) => {
    if (value > 0) return `+ ${formatCurrency(value)}`
    if (value < 0) return `- ${formatCurrency(Math.abs(value))}`
    return '-'
}

const isDepositType = (type: string) => {
    const normalized = String(type || '').toLowerCase()
    return normalized.includes('penyetoran') || normalized.includes('deposit')
}

const parseTanggal = (tanggal: string) => {
    if (!tanggal) return 0

    const [day, month, year] = String(tanggal).split('/').map(Number)
    if (!day || !month || !year) return 0

    return new Date(year, month - 1, day).getTime()
}

const sortedTransactions = computed(() => {
    return props.transactions
        .map((row, index) => ({ row, index }))
        .sort((a, b) => {
            const dateDiff = parseTanggal(b.row.tanggal) - parseTanggal(a.row.tanggal)
            if (dateDiff !== 0) return dateDiff

            // Keep stable order for same dates.
            return a.index - b.index
        })
        .map((entry) => entry.row)
})

const receiptAttachmentUrl = computed(() => {
    return selectedTransaction.value?.saving_transaction_receipt
        || selectedTransaction.value?.struk_attachment
        || ''
})

const attachmentExtension = computed(() => {
    const url = String(receiptAttachmentUrl.value || '').split('?')[0].toLowerCase()
    return url.split('.').pop() || ''
})

const isImageAttachment = computed(() => {
    return ['png', 'jpg', 'jpeg', 'webp'].includes(attachmentExtension.value)
})

const isPdfAttachment = computed(() => attachmentExtension.value === 'pdf')

const hasStoredAttachment = computed(() => Boolean(receiptAttachmentUrl.value))

const openDetail = (row: Transaction) => {
    selectedTransaction.value = row
    document.body.classList.add('overflow-hidden')
}

const closeDetail = () => {
    selectedTransaction.value = null
    document.body.classList.remove('overflow-hidden')
}
</script>

<template>
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="min-w-full">
            <thead class="font-head bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        class="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-200 whitespace-nowrap"
                    >
                        {{ col.label }}
                    </th>
                </tr>
            </thead>

            <tbody class="font-body bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                <template v-if="sortedTransactions.length === 0">
                    <tr>
                        <td
                            :colspan="columns.length"
                            class="font-head px-6 py-10 text-center text-gray-500 dark:text-gray-400"
                        >
                            Tidak ada data
                        </td>
                    </tr>
                </template>

                <template v-else>
                    <tr
                        v-for="(row, index) in sortedTransactions"
                        :key="row.id ?? index"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="px-6 py-4 text-sm text-gray-700 dark:text-gray-100 whitespace-nowrap"
                        >
                            <span
                                v-if="col.key === 'jenis'"
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
                                :class="isDepositType(row[col.key])
                                    ? 'bg-lime-200 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300'
                                    : 'bg-rose-100 text-rose-500 dark:bg-rose-900/40 dark:text-rose-300'"
                            >
                                {{ row[col.key] }}
                            </span>

                            <span
                                v-else-if="col.key === 'nominal'"
                                class="font-semibold"
                                :class="getNominalClass(getNominalValue(row))"
                            >
                                {{ formatNominal(getNominalValue(row)) }}
                            </span>

                            <button
                                v-else-if="col.key === 'aksi'"
                                type="button"
                                @click="openDetail(row)"
                                class="inline-flex items-center px-3 py-1.5 rounded-md border border-emerald-300 text-emerald-700 text-xs font-medium hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/30 transition-colors"
                            >
                                Lihat Kuitansi
                            </button>

                            <span v-else>{{ col.formatter ? col.formatter(row[col.key]) : row[col.key] }}</span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div
        v-if="selectedTransaction"
        class="fixed inset-0 z-50 bg-black/55 flex items-center justify-center p-4"
        @click.self="closeDetail"
    >
        <div class="w-full max-w-2xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Kuitansi Transaksi</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Riwayat transaksi simpanan anggota</p>
                </div>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    @click="closeDetail"
                >
                    Tutup
                </button>
            </div>

            <div class="overflow-y-auto max-h-[70vh] p-5">
                <div v-if="hasStoredAttachment" class="space-y-3">
                    <img
                        v-if="isImageAttachment"
                        :src="receiptAttachmentUrl"
                        alt="Kuitansi transaksi"
                        class="w-full h-auto rounded-lg border border-gray-200 dark:border-gray-700"
                    >

                    <iframe
                        v-else-if="isPdfAttachment"
                        :src="receiptAttachmentUrl"
                        class="w-full min-h-[60vh] rounded-lg border border-gray-200 dark:border-gray-700"
                    />

                    <div
                        v-else
                        class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-sm text-gray-600 dark:text-gray-300"
                    >
                        Format lampiran tidak didukung untuk preview langsung.
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-sm text-gray-600 dark:text-gray-300"
                >
                    Lampiran kuitansi belum tersedia untuk transaksi ini.
                </div>
            </div>
        </div>
    </div>
</template>
