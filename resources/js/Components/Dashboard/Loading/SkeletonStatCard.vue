<script setup>
defineProps({
    count: {
        type: Number,
        default: 4,
    },
});
</script>

<template>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div
            v-for="n in count"
            :key="n"
            class="flex flex-col gap-2 rounded-xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
        >
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-8 w-8 rounded-lg"></div>
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 w-3/5 rounded"></div>
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-7 w-4/5 rounded"></div>
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-2.5 w-2/5 rounded"></div>
        </div>
    </div>
</template>
