<script setup>
const props = defineProps({
    label: {
        type: String,
        default: '',
    },
    modelValue: {
        type: [String, Number],
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    disabled: {
        type: Boolean,
        default: true,
    },
    placeholder: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <div class="flex flex-col gap-2">
        <label v-if="label" class="text-sm font-medium text-gray-600 dark:text-gray-100">{{ label }}</label>
        <input
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-4 text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:bg-gray-800 dark:text-gray-300"
        />
    </div>
</template>
