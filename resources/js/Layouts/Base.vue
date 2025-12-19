<script setup>
    import ThemeProvider from './Admin/ThemeProvider.vue';
import Navbar from './Navbar.vue'
</script>

<template>
    <ThemeProvider>
        <Navbar />
        <slot></slot>
    </ThemeProvider>
</template>
