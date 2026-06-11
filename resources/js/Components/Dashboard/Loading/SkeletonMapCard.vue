<script setup>
defineProps({
    legendItems: {
        type: Number,
        default: 5,
    },
});
</script>

<template>
    <div class="flex flex-col gap-3 rounded-xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-4 w-2/5 rounded"></div>
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-8 w-28 rounded-lg"></div>
        </div>

        <div class="animate-pulse bg-gray-200 dark:bg-gray-700 relative w-full overflow-hidden rounded-xl" style="height: 220px;">
        </div>

        <div class="flex flex-wrap gap-x-4 gap-y-2 pt-1">
            <div
                v-for="n in legendItems"
                :key="n"
                class="flex items-center gap-1.5"
            >
                <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 w-3 rounded-sm"></div>
                <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-2.5 w-16 rounded"></div>
            </div>
        </div>
    </div>
</template>
