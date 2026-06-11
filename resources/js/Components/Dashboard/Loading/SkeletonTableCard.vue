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
        <div class="flex items-center justify-between">
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-4 w-2/5 rounded"></div>
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-8 w-20 rounded-lg"></div>
        </div>

        <div class="flex gap-3 border-b border-gray-100 pb-2 dark:border-gray-800">
            <div v-if="showAvatar" class="w-8 shrink-0"></div>
            <div
                v-for="c in columns"
                :key="'h' + c"
                class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 flex-1 rounded"
                :class="colWidths[(c - 1) % colWidths.length]"
            ></div>
        </div>

        <div
            v-for="r in rows"
            :key="r"
            class="flex items-center gap-3 py-1"
        >
            <div v-if="showAvatar" class="animate-pulse bg-gray-200 dark:bg-gray-700 h-8 w-8 shrink-0 rounded-full"></div>

            <template v-for="c in columns" :key="'c' + c">
                <div v-if="c === 1" class="flex flex-1 flex-col gap-1.5">
                    <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 w-4/5 rounded"></div>
                    <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-2.5 w-3/5 rounded"></div>
                </div>
                <div
                    v-else-if="c === columns && showBadge"
                    class="animate-pulse bg-gray-200 dark:bg-gray-700 h-6 w-16 rounded-full"
                ></div>
                <div
                    v-else
                    class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 flex-1 rounded"
                    :class="colWidths[(c - 1) % colWidths.length]"
                ></div>
            </template>
        </div>

        <div class="mt-1 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
            <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-3 w-24 rounded"></div>
            <div class="flex gap-1.5">
                <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-7 w-7 rounded-lg"></div>
                <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-7 w-7 rounded-lg"></div>
                <div class="animate-pulse bg-gray-200 dark:bg-gray-700 h-7 w-7 rounded-lg"></div>
            </div>
        </div>
    </div>
</template>
