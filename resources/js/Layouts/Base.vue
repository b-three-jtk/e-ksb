<script setup>
import ThemeProvider from './Admin/ThemeProvider.vue';
import Navbar from './Navbar.vue'
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String,
    }
});
</script>

<template>
    <ThemeProvider>
        <Head>
            <title>{{ title }} - Koperasi Syariah Warga Polban</title>
        </Head>
        <Navbar />
        <slot></slot>
    </ThemeProvider>
</template>
