<script setup>
import { Icon } from '@iconify/vue'
</script>

<template>
    <span class="relative group cursor-pointer">
        <Icon icon="mdi:information-outline" class="text-gray-500 hover:text-blue-500" width="18" height="18" />
        <div class="absolute left-full top-1/2 -translate-y-1/3 -ml-40 mt-16
            w-72 bg-white dark:bg-gray-800
            border border-gray-200 dark:border-gray-700
            rounded-lg shadow-lg
            opacity-0 group-hover:opacity-100
            pointer-events-none
            transition-opacity duration-200
            z-50">
            <div class="p-4 space-y-2 text-sm flex-wrap text-gray-700 dark:text-gray-200">
                <slot></slot>
            </div>
        </div>
    </span>
</template>
