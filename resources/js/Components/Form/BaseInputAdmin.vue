<script setup lang="ts">
import { computed, ref } from 'vue'
import ChevronDownIcon from '../../Icons/ChevronDownIcon.vue';
import moneyParser from '../../Composables/moneyParser';

const props = defineProps<{
    modelValue: string | number | File
    label?: string
    type?: string
    required?: boolean
    error?: string
    placeholder?: string,
    max?: string,
    min?: string,
    pattern?: string,
    selectables?: Array<{ value: string | number; text: string }>,
    rows?: string,
    disabled?: boolean,
    isMoney?: boolean,
    accept?: string,
    multiple?: boolean,
}>()

const fileInput = ref(null)
const fileName = ref('')

const inputType = computed(() => {
    return props.type ?? 'text'
})

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    const files = target.files

    if (files) {
        if (props.multiple) {
            // Jika multiple, emit array of files
            emit('update:modelValue', Array.from(files))
            fileName.value = `${files.length} file dipilih`
        } else {
            // Jika single, emit 1 file
            emit('update:modelValue', files[0])
            fileName.value = files[0].name
        }
    }
}

const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <div>
        <label v-if="label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ label }}<span class="text-red-500" v-if="required">*</span>
        </label>

        <!-- Regular Input -->
        <input v-if="!isMoney && (inputType !== 'select' && inputType !== 'textarea' && inputType !== 'radio' && inputType !== 'file')" :type="inputType"
            :value="modelValue" @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            :placeholder="placeholder" :maxlength="max" :minlength="min" :pattern="pattern" :class="['h-11 w-full rounded-lg border bg-transparent font-body px-4 py-2.5 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                error ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
            ]" :disabled="disabled"
            class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />

        <input v-if="isMoney && (inputType !== 'select' && inputType !== 'textarea')" :type="inputType"
            :value="moneyParser(modelValue)"
            @input="$emit('update:modelValue', moneyParser.parse(($event.target as HTMLInputElement).value))"
            :placeholder="placeholder" :maxlength="max" :minlength="min" :pattern="pattern" :class="['h-11 w-full rounded-lg border bg-transparent font-body px-4 py-2.5 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                error ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
            ]" :disabled="disabled"
            class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />

        <!-- Select Input -->
        <div v-else-if="inputType === 'select'" class="relative z-20 bg-transparent">
            <select :value="modelValue" @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
                :class="['h-11 w-full font-body appearance-none rounded-lg border bg-transparent px-4 py-2.5 pr-11 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                    error ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
                ]" class="dark:bg-dark-900 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                :disabled="disabled">
                <option value="" disabled selected>Pilih Opsi</option>
                <option v-for="option in selectables" :key="option.value" :value="option.value">{{ option.text }}
                </option>
            </select>
            <ChevronDownIcon
                class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400" />
        </div>

        <!-- Radio Input -->
        <div v-else-if="inputType === 'radio'" class="flex gap-4 items-center py-2">
            <label v-for="option in selectables" :key="option.value" class="inline-flex items-center">
                <input type="radio" :value="option.value" :checked="modelValue === option.value"
                    @change="$emit('update:modelValue', option.value)" :disabled="disabled"
                    :class="['h-4 w-4 accent-brand-900', error ? 'border-red-500' : 'border-gray-300']" />
                <span class="ml-2 text-gray-700 dark:text-gray-400">{{ option.text }}</span>
            </label>
        </div>

        <!-- Textarea Input -->
        <textarea v-if="inputType === 'textarea'" :value="modelValue"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)" :placeholder="placeholder"
            :rows="rows" :class="['w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                error ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
            ]"
            class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
            :disabled="disabled"></textarea>

        <!-- File Input -->
        <div v-else-if="inputType === 'file'" class="flex">
            <input
                ref="fileInput"
                type="file"
                @change="handleFileChange"
                :accept="accept"
                :multiple="multiple"
                :disabled="disabled"
                class="hidden"
            />
            <button
                @click="$refs.fileInput?.click()"
                type="button"
                :disabled="disabled"
                class="px-4 py-2.5 bg-gray-200 text-gray-700 rounded-l-lg hover:bg-gray-300 disabled:bg-gray-100 disabled:cursor-not-allowed font-medium text-sm"
            >
                Choose file
            </button>
            <div class="flex-1 px-4 py-2.5 border border-l-0 border-gray-300 rounded-r-lg bg-white text-gray-500 text-sm flex items-center">
                {{ fileName || 'No file chosen' }}
            </div>
        </div>

        <p v-if="error" class="text-red-500 text-xs mt-1">{{ error }}</p>
    </div>
</template>
