<template>
    <slot></slot>
</template>

<script setup>
import { ref, onMounted, provide } from 'vue'

const theme = ref('light')

const toggleTheme = () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
    updateTheme()
}

const updateTheme = () => {
    if (theme.value === 'dark') {
        document.documentElement.classList.add('dark')
        localStorage.setItem('theme', 'dark')
    } else {
        document.documentElement.classList.remove('dark')
        localStorage.setItem('theme', 'light')
    }
}

onMounted(() => {
    const savedTheme = localStorage.getItem('theme')
    if (savedTheme) {
        theme.value = savedTheme
    } else {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
        theme.value = prefersDark ? 'dark' : 'light'
    }
    updateTheme()
})

provide('theme', {
    theme,
    toggleTheme
})
</script>
