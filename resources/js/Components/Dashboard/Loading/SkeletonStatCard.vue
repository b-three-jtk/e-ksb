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
            <!-- Icon placeholder -->
            <div class="skel h-8 w-8 rounded-lg"></div>
            <!-- Label -->
            <div class="skel h-3 w-3/5 rounded"></div>
            <!-- Value -->
            <div class="skel h-7 w-4/5 rounded"></div>
            <!-- Sublabel / badge -->
            <div class="skel h-2.5 w-2/5 rounded"></div>
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
