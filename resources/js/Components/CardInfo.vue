<script setup>
const props = defineProps({
    title: {
        type: String,
    },
    content: {
        type: String,
        default: '0',
    },
    percentage: {
        type: Number,
        default: 0,
    }
})
</script>

<template>
    <div class="card-layout flex flex-col gap-2">
        <h2 class="text-3xl font-semibold mb-2 text-gray-800 dark:text-gray-200">{{ content }}</h2>
        <div class="flex justify-between items-center">
            <p class="text-gray-text dark:text-gray-400">{{ title }}</p>
            <div class="text-sm flex items-center font-body gap-2 text-gray-text">
                <span
                    :class="percentage >= 0 ? 'font-semibold text-green-600 bg-green-50 rounded-2xl px-4 py-1' : 'text-error-600 font-semibold bg-error-50 rounded-2xl px-4 py-1'">{{ percentage }}%</span>
                    dari bulan lalu
            </div>
        </div>
    </div>
</template>
