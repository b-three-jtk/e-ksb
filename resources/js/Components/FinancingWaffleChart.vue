<script setup>
import { computed } from 'vue'

const props = defineProps({
    // Dummy data: { launched: 50, underReview: 30, declined: 20 }
    data: {
        type: Object,
        default: () => ({
            launched: 50,
            underReview: 30,
            declined: 20
        })
    },
    totalAmount: {
        type: [String, Number],
        default: 'Rp190.000.000'
    }
})

// Status colors mapping
const statusColors = {
    launched: 'bg-green-500',
    underReview: 'bg-yellow-400',
    declined: 'bg-red-500'
}

const statusLabels = {
    launched: 'Lancar',
    underReview: 'Kurang Lancar',
    declined: 'Macet'
}

// Generate waffle grid (12x10 = 120 cells, setiap cell = 1%)
const waffleGrid = computed(() => {
    const grid = []
    const total = Object.values(props.data).reduce((a, b) => a + b, 0)

    let launchedCount = Math.round((props.data.launched / total) * 120)
    let underReviewCount = Math.round((props.data.underReview / total) * 120)
    let declinedCount = Math.round((props.data.declined / total) * 120)

    // Adjust untuk total 120 cells
    const totalCount = launchedCount + underReviewCount + declinedCount
    if (totalCount < 120) {
        launchedCount += 120 - totalCount
    }

    // Create grid
    for (let i = 0; i < launchedCount; i++) {
        grid.push('launched')
    }
    for (let i = 0; i < underReviewCount; i++) {
        grid.push('underReview')
    }
    for (let i = 0; i < declinedCount; i++) {
        grid.push('declined')
    }

    return grid.slice(0, 120)
})

// Calculate percentages
const percentages = computed(() => {
    const total = Object.values(props.data).reduce((a, b) => a + b, 0)
    return {
        launched: Math.round((props.data.launched / total) * 100),
        underReview: Math.round((props.data.underReview / total) * 100),
        declined: Math.round((props.data.declined / total) * 100)
    }
})
</script>

<template>
    <!-- Waffle Chart Grid -->
    <div class="mt-6 flex justify-center">
        <div class="grid gap-1" style="grid-template-columns: repeat(12, minmax(0, 1fr));">
            <div v-for="(status, index) in waffleGrid" :key="index"
                :class="[statusColors[status], 'w-6 h-6 rounded-sm hover:shadow-lg transition-shadow']"
                :title="`${statusLabels[status]}`" />
        </div>
    </div>

    <!-- Legend -->
    <div class="mt-6 flex justify-center gap-6">
        <div v-for="(status, key) in { launched: 'launched', underReview: 'underReview', declined: 'declined' }"
            :key="key" class="flex items-center gap-2">
            <div :class="[statusColors[key], 'w-4 h-4 rounded-sm']" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ statusLabels[key] }}</span>
            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ percentages[key] }}%</span>
        </div>
    </div>
</template>
