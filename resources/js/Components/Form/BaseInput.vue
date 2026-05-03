<script setup lang="ts">
import { ref, computed } from 'vue'

defineOptions({
  inheritAttrs: false,
})

const props = defineProps<{
  modelValue: string
  label: string
  type?: string
  required?: boolean
  max?: number
  error?: string
  disabled?: boolean
}>()

defineEmits(['update:modelValue'])

const showPassword = ref(false)

const inputType = computed(() => {
  if (props.type === 'password' && showPassword.value) {
    return 'text'
  }
  return props.type ?? 'text'
})

const isPasswordField = computed(() => props.type === 'password')
</script>

<template>
  <div>
    <div class="relative h-12">
      <input
      v-bind="$attrs"
      :type="inputType"
      :value="modelValue"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      placeholder=" "
      :required="required"
      :disabled="disabled"
      :aria-invalid="!!error"
      :maxlength="max"
      autocomplete="off"
      :class="`peer h-12 w-full rounded-lg border border-gray-300
              bg-transparent px-4 pt-2 pb-2 text-sm
              text-gray-800 dark:text-gray-200
              focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20
              ${isPasswordField ? 'pr-12' : ''}
              [&::-ms-reveal]:hidden`"
      />

      <label
      class="pointer-events-none absolute left-3 top-1/2 z-10
            -translate-y-1/2 px-1
            bg-white dark:bg-gray-800 text-sm text-gray-400
            transition-all duration-200
            peer-focus:top-0 peer-focus:text-xs
            peer-not-placeholder-shown:top-0
            peer-not-placeholder-shown:text-xs"
      >
        {{ label }}
        <span v-if="required" class="text-error-500">*</span>
      </label>

      <!-- Icon Password -->
      <button
        v-if="isPasswordField"
        type="button"
        @click="showPassword = !showPassword"
        class="absolute right-3 top-1/2 -translate-y-1/2 z-30
              text-gray-400 hover:text-gray-600 dark:hover:text-gray-300
              focus:outline-none transition-colors"
        :disabled="disabled"
      >
        <span v-if="!showPassword" class="icon-[mdi--eye-outline] w-5 h-5"></span>
        <span v-else class="icon-[mdi--eye-off-outline] w-5 h-5"></span>

      </button>
    </div>

    <p v-if="error" class="mt-1 text-sm text-error-500">
      {{ error }}
    </p>
  </div>
</template>

