<script setup lang="ts">
import { computed } from 'vue'
import { formatCurrency } from '../../../utils/currency'

type SavingData = Record<string, number>
type SavingMeta = Record<string, {
    maturity_date?: string | null
    minimum_target?: number | null
}>

const props = withDefaults(defineProps<{
    savings?: SavingData
    savingMeta?: SavingMeta
}>(), {
    savings: () => ({
        simpanan_pokok: 0,
        simpanan_wajib: 0,
        tabungan_anggota: 0,
        tabungan_berjangka: 0,
        tabungan_ibadah: 0,
    }),
    savingMeta: () => ({
        tabungan_berjangka: {
            maturity_date: null,
        },
        tabungan_ibadah: {
            minimum_target: null,
        },
    }),
})

const labelMap: Record<string, string> = {
    simpanan_pokok: 'Simpanan Pokok',
    simpanan_wajib: 'Simpanan Wajib',
    simpanan_sukarela: 'Tabungan Anggota',
    tabungan_anggota: 'Tabungan Anggota',
    tabungan_berjangka: 'Tabungan Berjangka',
    tabungan_ibadah: 'Tabungan Ibadah',
}

const noteMap: Record<string, string> = {
    simpanan_pokok: 'Dicairkan saat keluar',
    simpanan_wajib: 'Dicairkan saat keluar',
    simpanan_sukarela: 'Dapat ditarik kapan saja',
    tabungan_anggota: 'Dapat ditarik kapan saja',
    tabungan_berjangka: 'Tidak dapat ditarik sebelum jatuh tempo',
    tabungan_ibadah: 'Bisa diambil saat target tabungan tercapai',
}
const accentBorderClass = 'border-emerald-300 dark:border-emerald-600'

const formatDate = (dateValue?: string | null) => {
    if (!dateValue) return null

    const date = new Date(dateValue)
    if (Number.isNaN(date.getTime())) return null

    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    })
}

const getItemNote = (key: string) => {
    if (key === 'tabungan_berjangka') {
        const maturityDate = formatDate(props.savingMeta?.tabungan_berjangka?.maturity_date)
        if (maturityDate) {
            return `Dapat ditarik setelah tanggal ${maturityDate}`
        }
    }

    if (key === 'tabungan_ibadah') {
        const minimumTarget = Number(props.savingMeta?.tabungan_ibadah?.minimum_target ?? 0)
        if (minimumTarget > 0) {
            return `Minimal capai ${formatCurrency(minimumTarget)} agar bisa diambil`
        }
    }

    return noteMap[key] ?? 'Saldo simpanan aktif'
}

const formatLabel = (key: string) => {
    if (labelMap[key]) return labelMap[key]

    return key
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

const orderedSavingKeys = computed(() => {
    const knownOrder = [
        'simpanan_pokok',
        'simpanan_wajib',
        'tabungan_anggota',
        'tabungan_berjangka',
        'tabungan_ibadah',
        'simpanan_sukarela',
    ]

    const available = Object.keys(props.savings ?? {})
    const known = knownOrder.filter((key) => available.includes(key))
    const unknown = available.filter((key) => !knownOrder.includes(key))

    return [...known, ...unknown].filter((key) => key !== 'total_saldo')
})

const savingItems = computed(() => {
    return orderedSavingKeys.value.map((key) => ({
        key,
        label: formatLabel(key),
        note: getItemNote(key),
        amount: props.savings?.[key] ?? 0,
        borderColor: accentBorderClass,
    }))
})
</script>

<template>
    <div class="w-full overflow-x-auto pb-2 [scrollbar-width:thin]">
        <div v-if="savingItems.length === 0" class="rounded-2xl border border-dashed border-gray-300 bg-white px-4 py-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
            Tidak ada simpanan aktif yang bisa ditampilkan.
        </div>
        <div v-else class="flex gap-4 w-max min-w-full pr-1">
            <div
                v-for="item in savingItems"
                :key="item.key"
                class="w-[240px] sm:w-[260px] rounded-2xl border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 p-4 sm:p-5 shadow-sm shrink-0"
                :class="item.borderColor"
            >
                <h3 class="text-sm font-medium font-body text-gray-500 dark:text-gray-300">
                    {{ item.label }}
                </h3>
                <p class="text-2xl font-bold font-head text-gray-900 dark:text-white mt-2">
                    {{ formatCurrency(item.amount) }}
                </p>
                <p class="text-xs text-gray-500 font-body dark:text-gray-400 mt-1">
                    {{ item.note }}
                </p>
            </div>
        </div>
    </div>
</template>
