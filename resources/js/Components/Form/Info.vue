<script setup>
const props = defineProps({
    label: {
        type: String,
        default: '',
    },
    value: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <div class="flex flex-col gap-2">
        <span class="text-gray-500 dark:text-gray-300">{{ props.label }}</span>
        <span class="font-semibold text-dark-text dark:text-white">{{ props.value }}</span>
    </div>
</template>
