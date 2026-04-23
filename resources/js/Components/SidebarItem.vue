<template>
    <button @click="$emit('click')"
        class="w-full text-left flex items-center gap-2 px-4 py-2 text-sm border-l-2 transition-colors" :class="active
            ? 'border-secondary text-secondary bg-light-bg font-medium'
            : 'border-transparent text-gray-500 hover:text-gray-900 hover:bg-gray-50'">
        {{ label }}
    </button>
</template>

<script setup>
defineProps({ label: String, active: Boolean })
defineEmits(['click'])
</script>
