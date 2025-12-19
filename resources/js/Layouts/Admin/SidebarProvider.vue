<template>
    <slot></slot>
</template>

<script setup>
import { useSidebarProvider } from '@/Composables/useSidebar'

// Initialize sidebar provider
useSidebarProvider()
</script>
