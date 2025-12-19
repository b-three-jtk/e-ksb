<script setup>
import Base from '@/Layouts/Admin/Base.vue'
import ThemeProvider from '@/Layouts/Admin/ThemeProvider.vue'
import SidebarProvider from '@/Layouts/Admin/SidebarProvider.vue'

defineProps({
    data: Object
})
</script>

<template>
    <ThemeProvider>
        <SidebarProvider>
            <Base>
                <slot></slot>
            </Base>
        </SidebarProvider>
    </ThemeProvider>
</template>
