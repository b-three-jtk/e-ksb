<script setup>
import { Icon } from '@iconify/vue'

const props = defineProps({
    width: String,
    height: String,
})
</script>

<template>
    <Icon
        icon="material-symbols:book-outline"
        :width="props.width || '24'"
        :height="props.height || '24'"
    />
</template>