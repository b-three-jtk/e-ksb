<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({
    titleIncluded: {
        type: Boolean,
        default: true,
    },
})
</script>

<template>
    <Link href="/" class="flex items-center space-x-3 rtl:space-x-reverse">
    <img class="max-h-12" src="/public/images/logo/logo-icon.svg" alt="Logo">
    <span v-if="titleIncluded" class="self-center text-2xl text-dark-text font-semibold whitespace-nowrap dark:text-white">KS<span
            class="text-accent">B</span></span>
    </Link>
</template>
