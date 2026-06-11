<script setup>
defineProps({
    title: {
        type: Boolean,
        default: true,
    },
    bars: {
        type: Number,
        default: 7,
    },
    legend: {
        type: Number,
        default: 3,
    },
});

// Random-ish heights for bars agar terlihat natural
const barHeights = [40, 65, 50, 80, 60, 90, 55, 70, 45, 75];
</script>

<template>
    <div class="flex flex-col gap-3 rounded-xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
            <div v-if="title" class="animate-pulse bg-gray-200 dark:bg-gray-700 h-4 w-2/5 rounded"></div>
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-8 w-24 rounded-lg"></div>
        </div>

        <div class="mt-1 flex items-end gap-1.5" style="height: 300px;">
            <div
                v-for="(h, i) in barHeights.slice(0, bars)"
                :key="i"
                class="animate-pulse bg-gray-200 dark:bg-gray-700 flex-1 rounded-t"
                :style="{ height: h + '%' }"
            ></div>
        </div>

        <div class="flex gap-1.5">
            <div
                v-for="n in bars"
                :key="n"
                class="animate-pulse bg-gray-200 dark:bg-gray-700 h-2.5 flex-1 rounded"
            ></div>
        </div>

        <div class="mt-1 flex gap-4">
            <div
                v-for="n in legend"
                :key="n"
                class="flex items-center gap-1.5"
            >
                <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 w-3 rounded-sm"></div>
                <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-2.5 w-14 rounded"></div>
            </div>
        </div>
    </div>
</template>
