<script setup>
import { computed } from 'vue'
import Tooltip from '@/Components/Form/Tooltip.vue'
import { isNumber } from 'chart.js/helpers'

const props = defineProps({
    title: {
        type: String,
    },
    content: {
        type: [String, Number],
        default: '0',
    },
    percentage: {
        type: Number,
        default: 0,
    },
    filter: {
        type: String,
        default: 'month',
    },
    description: {
        type: String,
        default: '',
    },
})

const filterText = computed(() => {
    if (props.filter === 'month') return 'dari bulan lalu'
    if (props.filter === 'day') return 'dari kemarin'
    if (props.filter === 'year') return 'dari tahun lalu'
    return 'dari periode lalu'
})
</script>

<template>
    <div class="card-layout flex flex-col gap-2">
        <h2 class="text-2xl font-semibold mb-2 text-gray-800 dark:text-gray-200">{{ content }}</h2>
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-1">
                <p class="text-gray-text dark:text-gray-400">{{ title }}</p>
                <Tooltip v-if="description">
                    <p class="font-semibold">Informasi {{ title }}</p>
                    <p>Nilai ini menunjukkan {{ title.toLowerCase() }} untuk periode yang dipilih.</p>
                    <p>{{ description }}.</p>
                </Tooltip>
            </div>

            <div v-if="percentage != 0" class="text-sm flex items-center font-body gap-2 text-gray-text">
                <span
                    :class="percentage >= 0 ? 'font-semibold text-green-600 bg-green-50 rounded-2xl px-4 py-1' : 'text-error-600 font-semibold bg-error-50 rounded-2xl px-4 py-1'">{{
                    percentage }}%</span>
                {{ filterText }}
            </div>
        </div>
    </div>
</template>
