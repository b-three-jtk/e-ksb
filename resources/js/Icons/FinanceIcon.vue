<script setup>
    const props = defineProps({
        width: String,
        height: String,
    })
</script>

<template>
    <span class="icon-[healthicons--finance-dept-outline]" :style="{ width: props.width || '24px', height: props.height || '24px' }"></span>
</template>
