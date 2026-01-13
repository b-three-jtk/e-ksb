<script setup lang="ts">
defineProps<{
  label: string
  value?: string | number
}>()
</script>

<template>
  <div class="grid grid-cols-[200px_20px_1fr] items-center">
    <span class="font-head text-blue-1000 dark:text-white">{{ label }}</span>
    <span class="font-head text-blue-1000 dark:text-white text-center">:</span>
    <span class="font-head text-blue-1000 dark:text-white">
      <slot>
        {{ value }}
      </slot>
    </span>
  </div>
</template>
