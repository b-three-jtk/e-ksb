<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        default: null,
    },
    type: {
        type: String,
        default: 'submit',
    },
    variant: {
        type: String,
        default: 'primary',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
    },
    full: {
        type: Boolean,
        default: false,
    }
})

const getStyles = () => {
    switch (props.variant) {
        case 'primary':
            return 'bg-primary text-white hover:bg-brand-800';
        case 'secondary':
            return 'bg-secondary text-white hover:bg-secondary/80';
        case 'danger':
            return 'bg-error-500 text-white hover:bg-error-400';
        case 'accent':
            return 'bg-accent text-white hover:bg-accent/80';
        case 'warning':
            return 'bg-yellow-500 text-white hover:bg-yellow-600';
        case 'info':
            return 'bg-blue-500 text-white hover:bg-blue-600';
        case 'light':
            return 'bg-white text-black hover:bg-gray-100 border border-gray-300 dark:bg-gray-800 dark:text-gray-300';
        case 'success':
            return 'bg-success-500 text-white hover:bg-success-400';
        case 'gray':
            return 'bg-gray-400 text-white hover:bg-gray-300';
        case 'transparent':
            return 'bg-white text-black hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300';
        case 'outline':
            return 'bg-white text-black border border-gray-300 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300';
        default:
            return 'bg-primary text-white hover:bg-brand-800';
    }
}

const getSizeClasses = () => {
    switch (props.size) {
        case 'small':
            return 'text-sm px-4 py-2';
        case 'large':
            return 'text-lg px-10 py-3';
        case 'medium':
            return 'text-base px-5 py-2.5'
        default:
            return 'text-base px-8 py-2.5';
    }
}

const emit = defineEmits(['click'])
</script>

<template>
    <Link v-if="href" @click="emit('click', $event)" v-on="$attrs"
        :class="[
            getStyles(),
            getSizeClasses(),
            full ? 'w-full' : 'w-fit',
            'rounded-lg flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed font-semibold'
        ]"
        disabled="disabled"
        :href="href">
        <slot></slot>
    </Link>
    <button
        v-else
        :type="type"
        :class="[
            getStyles(),
            getSizeClasses(),
            full ? 'w-full' : 'w-fit',
            'rounded-lg flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed font-semibold'
        ]"
        :disabled="disabled"
        v-on="$attrs"
        @click="emit('click', $event)"
    >
        <slot></slot>
    </button>
</template>
