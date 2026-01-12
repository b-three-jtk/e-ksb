<script setup>
import Base from '@/Layouts/Admin/Base.vue'
import ThemeProvider from '@/Layouts/Admin/ThemeProvider.vue'
import SidebarProvider from '@/Layouts/Admin/SidebarProvider.vue'
import { Head } from '@inertiajs/vue3'

defineProps({
    data: Object,
    title: String
})
</script>

<template>
    <ThemeProvider>
        <Head>
            <title>{{ title }} - Koperasi Syariah Warga Polban</title>
        </Head>
        <SidebarProvider>
            <Base>
                <slot></slot>
            </Base>
        </SidebarProvider>
    </ThemeProvider>
</template>
