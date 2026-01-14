<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';
import ThemeProvider from './Admin/ThemeProvider.vue';
import Navbar from './Navbar.vue'
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String,
    }
});

const page = usePage();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast(flash.success, {
                type: 'success',
                position: 'bottom-right',
                transition: 'slide',
                autoClose: 5000,
            });
        }
        if (flash?.error) {
            toast(flash.error, {
                type: 'error',
                position: 'bottom-right',
                transition: 'slide',
                autoClose: 5000,
            });
        }
    },
    { deep: true }
);
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
