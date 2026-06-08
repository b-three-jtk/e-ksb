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
        <!-- Header: title + filter button placeholder -->
        <div class="flex items-center justify-between">
            <div v-if="title" class="skel h-4 w-2/5 rounded"></div>
            <div class="skel h-8 w-24 rounded-lg"></div>
        </div>

        <!-- Chart area -->
        <div class="mt-1 flex items-end gap-1.5" style="height: 300px;">
            <div
                v-for="(h, i) in barHeights.slice(0, bars)"
                :key="i"
                class="skel flex-1 rounded-t"
                :style="{ height: h + '%' }"
            ></div>
        </div>

        <!-- X-axis labels -->
        <div class="flex gap-1.5">
            <div
                v-for="n in bars"
                :key="n"
                class="skel h-2.5 flex-1 rounded"
            ></div>
        </div>

        <!-- Legend -->
        <div class="mt-1 flex gap-4">
            <div
                v-for="n in legend"
                :key="n"
                class="flex items-center gap-1.5"
            >
                <div class="skel h-3 w-3 rounded-sm"></div>
                <div class="skel h-2.5 w-14 rounded"></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes shimmer {
    0%   { background-position: -600px 0; }
    100% { background-position:  600px 0; }
}

.skel {
    background: linear-gradient(
        90deg,
        #e8e8e8 25%,
        #f5f5f5 50%,
        #e8e8e8 75%
    );
    background-size: 600px 100%;
    animation: shimmer 1.4s infinite linear;
}

:global(.dark) .skel {
    background: linear-gradient(
        90deg,
        #1f2937 25%,
        #374151 50%,
        #1f2937 75%
    );
    background-size: 600px 100%;
    animation: shimmer 1.4s infinite linear;
}
</style>
