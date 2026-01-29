<script setup>
import { ref } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    subTitle: { type: String, required: false },
    ariaTitle: { type: String, required: true }
});

const showPanel = ref(false);
const togglePanel = (event) => {
    showPanel.value = !showPanel.value;
}

// Generate unique IDs for ARIA attributes (stable across renders)
const uniqueId = Math.random().toString(36).substr(2, 9);
const buttonId = `accordion-button-${uniqueId}`;
const contentId = `accordion-content-${uniqueId}`;
</script>

<template>
    <div class="panel">
        <button 
            :id="buttonId"
            :aria-controls="contentId"
            :aria-expanded="showPanel"
            :class="showPanel ? 'rounded-t-2xl border-b border-stroke dark:border-gray-600' : 'rounded-2xl'" 
            class="bg-white dark:bg-gray-800 shadow-md px-6 py-4 w-full flex items-center justify-between transition-all duration-400" 
            @click.prevent="togglePanel">
                <div class="flex flex-col">
                    <h1 class="font-semibold text-lg dark:text-gray-300">{{ title }}</h1>
                    <p class="text-gray-400 text-sm text-left" v-if="subTitle">{{ subTitle }}</p>
                </div>
            <span :class="showPanel ? 'icon-[tabler--chevron-up]' : 'icon-[tabler--chevron-down]'" class="transition-all duration-300" style="width: 24px; height: 24px;" :aria-label="ariaTitle"></span>
        </button>
        <div 
            :id="contentId"
            :aria-labelledby="buttonId"
            role="region"
            class="content bg-white dark:bg-gray-800 transition-all duration-400 px-8 pb-6 rounded-b-2xl shadow-md" 
            v-if="showPanel">
            <slot></slot>
        </div>
    </div>
</template>
