<script setup>
    const props = defineProps({
        width: String,
        height: String,
    })
</script>

<template>
    <span class="icon-[hugeicons--savings]" :style="{ width: props.width || '24px', height: props.height || '24px' }"></span>
</template>
