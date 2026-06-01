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
  locked?: boolean
  multiline?: boolean
  rows?: number
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
const isMultiline = computed(() => !!props.multiline)
const isLocked = computed(() => !!props.locked)

const fieldClass = computed(() => {
  const baseClass = `peer w-full rounded-lg border px-4 text-sm text-gray-800 dark:text-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 ${isPasswordField.value ? 'pr-12' : ''} [&::-ms-reveal]:hidden`

  if (isMultiline.value) {
    return `${baseClass} min-h-28 pt-4 pb-3 resize-y`
  }

  return `${baseClass} h-12 pt-2 pb-2`
})

const lockedClass = computed(() => {
  if (!isLocked.value) return ''

  return 'border-gray-300 bg-gray-50 text-gray-600 placeholder:text-gray-400 cursor-not-allowed'
})
</script>

<template>
  <div>
    <div :class="isMultiline ? 'relative' : 'relative h-12'">
      <textarea
        v-if="isMultiline"
        v-bind="$attrs"
        :value="modelValue"
        @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
        placeholder=" "
        :required="required"
        :disabled="disabled"
        :aria-invalid="!!error"
        :rows="rows ?? 3"
        autocomplete="off"
        :class="[fieldClass, lockedClass, disabled ? 'disabled:opacity-100' : '']"
      />
      <input
          v-else
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
          :class="[fieldClass, lockedClass, disabled ? 'disabled:opacity-100' : '']"
      />

      <label
      :class="[
        'pointer-events-none absolute left-3 z-10 px-1 bg-white dark:bg-gray-800 text-sm transition-all duration-200',
        isLocked ? 'text-gray-400' : 'text-gray-400',
        isMultiline
          ? 'top-3 peer-focus:top-0 peer-focus:text-xs peer-not-placeholder-shown:top-0 peer-not-placeholder-shown:text-xs'
          : 'top-1/2 -translate-y-1/2 peer-focus:top-0 peer-focus:text-xs peer-not-placeholder-shown:top-0 peer-not-placeholder-shown:text-xs',
      ]"
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

