<script setup lang="ts">
defineProps<{
  modelValue: string | number | null
  label: string
  required?: boolean
  error?: string
}>()

defineEmits(['update:modelValue'])
</script>

<template>
  <div class="relative">
    <select
      :value="modelValue ?? ''"
      @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
      class="peer h-12 w-full rounded-lg border border-gray-300
             bg-transparent px-4 pt-2 pb-2 text-sm
             text-gray-800
             focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
    >
      <option value="" disabled hidden></option>

      <slot />
    </select>

    <label
      class="pointer-events-none absolute left-3 top-1/2 z-10
             -translate-y-1/2 px-1 bg-white text-sm text-gray-400
             transition-all duration-200
             peer-focus:top-0 peer-focus:text-xs
             peer-[&:not([value=''])]:top-0
             peer-[&:not([value=''])]:text-xs"
    >
      {{ label }}
      <span v-if="required" class="text-error-500">*</span>
    </label>

    <p v-if="error" class="mt-1 text-sm text-error-500">
      {{ error }}
    </p>
  </div>
</template>
