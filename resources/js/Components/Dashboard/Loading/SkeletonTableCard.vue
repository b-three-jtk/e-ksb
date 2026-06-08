<script setup>
defineProps({
    rows: {
        type: Number,
        default: 5,
    },
    columns: {
        type: Number,
        default: 4,
    },
    showAvatar: {
        type: Boolean,
        default: true,
    },
    showBadge: {
        type: Boolean,
        default: true,
    },
});

// Lebar kolom bervariasi biar lebih natural
const colWidths = ['w-2/5', 'w-1/4', 'w-1/5', 'w-1/6', 'w-1/3'];
</script>

<template>
    <div class="flex flex-col gap-3 rounded-xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="skel h-4 w-2/5 rounded"></div>
            <div class="skel h-8 w-20 rounded-lg"></div>
        </div>

        <!-- Table header -->
        <div class="flex gap-3 border-b border-gray-100 pb-2 dark:border-gray-800">
            <div v-if="showAvatar" class="w-8 shrink-0"></div>
            <div
                v-for="c in columns"
                :key="'h' + c"
                class="skel h-3 flex-1 rounded"
                :class="colWidths[(c - 1) % colWidths.length]"
            ></div>
        </div>

        <!-- Rows -->
        <div
            v-for="r in rows"
            :key="r"
            class="flex items-center gap-3 py-1"
        >
            <!-- Avatar circle -->
            <div v-if="showAvatar" class="skel h-8 w-8 shrink-0 rounded-full"></div>

            <!-- Cells -->
            <template v-for="c in columns" :key="'c' + c">
                <!-- Kolom pertama: 2 baris (nama + sub) -->
                <div v-if="c === 1" class="flex flex-1 flex-col gap-1.5">
                    <div class="skel h-3 w-4/5 rounded"></div>
                    <div class="skel h-2.5 w-3/5 rounded"></div>
                </div>
                <!-- Kolom terakhir: badge -->
                <div
                    v-else-if="c === columns && showBadge"
                    class="skel h-6 w-16 rounded-full"
                ></div>
                <!-- Kolom biasa -->
                <div
                    v-else
                    class="skel h-3 flex-1 rounded"
                    :class="colWidths[(c - 1) % colWidths.length]"
                ></div>
            </template>
        </div>

        <!-- Footer / pagination placeholder -->
        <div class="mt-1 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
            <div class="skel h-3 w-24 rounded"></div>
            <div class="flex gap-1.5">
                <div class="skel h-7 w-7 rounded-lg"></div>
                <div class="skel h-7 w-7 rounded-lg"></div>
                <div class="skel h-7 w-7 rounded-lg"></div>
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
