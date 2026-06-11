<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

defineProps<{
    links: {
        url: string | null
        label: string
        active: boolean
    }[]
    total: number
}>()
</script>

<template>
    <div
        v-if="total > 0"
        class="p-6 flex justify-center gap-1 flex-wrap text-sm"
    >
        <template v-for="link in links" :key="link.label">
            <!-- Disabled -->
            <span
                v-if="!link.url"
                class="px-3 py-1 text-gray-400"
                v-html="link.label"
            />

            <!-- Active / Normal -->
            <Link
                v-else
                :href="link.url"
                preserve-scroll
                preserve-state
                class="px-3 py-1 border rounded
                       border-gray-300 text-gray-700
                       dark:text-gray-300
                       dark:border-gray-600"
                :class="{
                    'bg-primary text-white border-secondary': link.active,
                    'hover:bg-gray-100 dark:hover:bg-gray-700': !link.active
                }"
                v-html="link.label"
            />
        </template>
    </div>
</template>
