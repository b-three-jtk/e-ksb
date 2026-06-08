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
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="skel h-4 w-2/5 rounded"></div>
            <div class="skel h-8 w-28 rounded-lg"></div>
        </div>

        <!-- Map area -->
        <div class="skel relative w-full overflow-hidden rounded-xl" style="height: 220px;">
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap gap-x-4 gap-y-2 pt-1">
            <div
                v-for="n in legendItems"
                :key="n"
                class="flex items-center gap-1.5"
            >
                <div class="skel h-3 w-3 rounded-sm"></div>
                <div class="skel h-2.5 w-16 rounded"></div>
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
