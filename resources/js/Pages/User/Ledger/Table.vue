<script setup lang="ts">
import { computed } from 'vue'
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
}>()

// Group transactions by month
const groupedTransactions = computed(() => {
    const groups: { [key: string]: Transaction[] } = {}
    
    props.transactions.forEach((transaction) => {
        const date = transaction.tanggal
        if (!date) return
        
        const [day, month, year] = date.split('/')
        const monthYear = `${year}-${month}`
        
        if (!groups[monthYear]) {
            groups[monthYear] = []
        }
        groups[monthYear].push(transaction)
    })
    
    return groups
})

const getMonthLabel = (monthYear: string) => {
    const [year, month] = monthYear.split('-')
    const date = new Date(parseInt(year), parseInt(month) - 1, 1)
    return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
}

const sortedMonthKeys = computed(() => {
    return Object.keys(groupedTransactions.value).sort((a, b) => b.localeCompare(a))
})
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="font-head bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        class="px-6 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-200"
                    >
                        {{ col.label }}
                    </th>
                </tr>
            </thead>

            <tbody class="font-body bg-white dark:bg-gray-800">
                <template v-if="transactions.length === 0">
                    <tr>
                        <td
                            :colspan="columns.length"
                            class="font-head px-6 py-8 text-center text-gray-500 dark:text-gray-400"
                        >
                            Tidak ada data
                        </td>
                    </tr>
                </template>

                <template v-else>
                    <template v-for="monthKey in sortedMonthKeys" :key="monthKey">
                        <tr class="bg-blue-900 dark:bg-blue-900">
                            <td
                                :colspan="columns.length"
                                class="px-6 py-3 text-left text-sm font-semibold text-white"
                            >
                                {{ getMonthLabel(monthKey) }}
                            </td>
                        </tr>

                        <tr
                            v-for="(row, index) in groupedTransactions[monthKey]"
                            :key="`${monthKey}-${index}`"
                            class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="px-6 py-4 text-sm text-gray-800 dark:text-gray-100"
                            >
                                <span v-if="col.key === 'debit' && row[col.key] > 0" class="text-green-600 dark:text-green-400 font-semibold">
                                    {{ formatCurrency(row[col.key]) }}
                                </span>
                                <span v-else-if="col.key === 'debit'" class="text-gray-400">-</span>
                                <span v-else-if="col.key === 'kredit' && row[col.key] > 0" class="text-red-600 dark:text-red-400 font-semibold">
                                    {{ formatCurrency(row[col.key]) }}
                                </span>
                                <span v-else-if="col.key === 'kredit'" class="text-gray-400">-</span>
                                <span v-else-if="col.key === 'saldo'" class="font-semibold text-blue-600 dark:text-blue-400">
                                    {{ formatCurrency(row[col.key]) }}
                                </span>
                                <span v-else>{{ col.formatter ? col.formatter(row[col.key]) : row[col.key] }}</span>
                            </td>
                        </tr>
                    </template>
                </template>
            </tbody>
        </table>
    </div>
</template>
