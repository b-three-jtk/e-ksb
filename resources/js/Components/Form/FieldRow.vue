<script setup lang="ts">
defineProps<{
  label: string
  value?: string | number
}>()
</script>

<template>
  <div class="grid grid-cols-[200px_20px_1fr] items-center">
    <span class="font-head text-blue-1000">{{ label }}</span>
    <span class="font-head text-blue-1000 text-center">:</span>
    <span class="font-head text-blue-1000">
      <slot>
        {{ value }}
      </slot>
    </span>
  </div>
</template>
