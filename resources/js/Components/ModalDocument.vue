<script setup>
import { ref } from 'vue'

const props = defineProps({
    title: String,
    name: String,
    attachment: String,
    modalId: {
        type: String,
        required: true
    }
});

const isOpen = ref(false)

const openModal = () => {
    isOpen.value = true
    document.body.classList.add('overflow-hidden')
}

const closeModal = () => {
    isOpen.value = false
    document.body.classList.remove('overflow-hidden')
}

defineExpose({ openModal, closeModal })
</script>

<template>
    <div v-show="isOpen" @click.self="closeModal()"
        class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center pt-44 pb-22 h-screen">
        <div
            class="bg-white max-h-[80vh] rounded-2xl dark:bg-gray-800"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="`${modalId}-title`">
            <div class="flex justify-between px-4">
                <h1 class="card-title p-4" :id="`${modalId}-title`">{{ title }}</h1>
                <button @click="closeModal()"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                        </svg>
                </button>
            </div>
            <div class="px-4 pb-4 max-h-[70vh] w-5xl overflow-y-auto custom-scrollbar">
                <iframe :src="attachment" class="w-full h-full min-h-150" frameborder="0"></iframe>
            </div>
        </div>
    </div>
</template>
