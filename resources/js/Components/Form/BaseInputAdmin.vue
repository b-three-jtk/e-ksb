<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import ChevronDownIcon from '../../Icons/ChevronDownIcon.vue'

const props = defineProps<{
    modelValue: string | number | File
    label?: string
    type?: string
    required?: boolean
    error?: string
    placeholder?: string
    max?: string
    min?: string
    pattern?: string
    selectables?: Array<{ value: string | number; text: string }>
    rows?: string
    disabled?: boolean
    isMoney?: boolean
    accept?: string
    multiple?: boolean
}>()

const emit = defineEmits(['update:modelValue'])

const fileName = ref('')
const displayValue = ref('')
const isMoneyFocused = ref(false)

const inputType = computed(() => props.type ?? 'text')

const dateValue = computed<Date | null>({
    get() {
        if (!props.modelValue) return null
        const date = new Date(String(props.modelValue))
        return Number.isNaN(date.getTime()) ? null : date
    },
    set(value: Date | null) {
        if (!value) { emit('update:modelValue', ''); return }
        const year = value.getFullYear()
        const month = String(value.getMonth() + 1).padStart(2, '0')
        const day = String(value.getDate()).padStart(2, '0')
        emit('update:modelValue', `${year}-${month}-${day}`)
    },
})

const datePickerInputClass = computed(() => [
  'h-11 w-full rounded-lg border bg-transparent font-body',
  'px-4 py-2.5 text-sm shadow-theme-xs',
  'focus:outline-hidden focus:ring-3',
  props.error
    ? 'border-red-500 focus:ring-red-500/10'
    : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10',
  'dark:bg-gray-900 dark:border-gray-700',
  'text-gray-800 dark:text-white/90',
  'placeholder:text-gray-400 dark:placeholder:text-white/30',
])

const handleFileChange = (event: Event) => {
    const files = (event.target as HTMLInputElement).files
    if (!files) return
    if (props.multiple) {
        emit('update:modelValue', Array.from(files))
        fileName.value = `${files.length} file dipilih`
    } else {
        emit('update:modelValue', files[0])
        fileName.value = files[0].name
    }
}

const handleMoneyFocus = () => {
    isMoneyFocused.value = true
    displayValue.value = props.modelValue ? String(props.modelValue) : ''
}

const handleMoneyBlur = () => {
    isMoneyFocused.value = false
    const num = Number(props.modelValue)
    if (!props.modelValue || num === 0) {
        displayValue.value = ''
        return
    }
    displayValue.value = new Intl.NumberFormat('id-ID').format(num)
}

const handleMoneyInput = (event: Event) => {
    const raw = (event.target as HTMLInputElement).value
    const numeric = raw.replace(/\D/g, '')
    const cleaned = numeric ? String(parseInt(numeric, 10)) : ''
    displayValue.value = cleaned
    emit('update:modelValue', cleaned)
}

watch(() => props.modelValue, (val) => {
    if (isMoneyFocused.value) return
    if (!val || val === '0' || val === '0.00' || Number(val) === 0) {
        displayValue.value = ''
        return
    }
    const num = Number(val)
    displayValue.value = isNaN(num) ? '' : new Intl.NumberFormat('id-ID').format(num)
}, { immediate: true })
</script>

<template>
    <div>
        <label v-if="label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ label }}<span v-if="required" class="text-red-500">*</span>
        </label>

        <!-- Money Input -->
        <div v-if="isMoney" class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 font-medium">Rp</span>
            <input
                type="text"
                inputmode="numeric"
                :value="displayValue"
                @input="handleMoneyInput"
                @focus="handleMoneyFocus"
                @blur="handleMoneyBlur"
                :disabled="disabled"
                :placeholder="placeholder || '0'"
                :class="[
                    'h-11 w-full rounded-lg border bg-transparent font-body pl-9 pr-4 py-2.5 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                    error ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
                ]"
                class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
            />
        </div>

        <!-- Date Picker -->
        <VueDatePicker
            v-else-if="inputType === 'date'"
            v-model="dateValue"
            format="yyyy-MM-dd"
            :time-picker="false"
            :input-class="datePickerInputClass"
            :disabled="disabled"
            :placeholder="placeholder || 'Pilih tanggal'"
            />

        <!-- Select -->
        <div v-else-if="inputType === 'select'" class="relative z-20 bg-transparent">
            <select
                :value="modelValue"
                @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
                :class="['h-11 w-full font-body appearance-none rounded-lg border bg-transparent px-4 py-2.5 pr-11 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                    error ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
                ]"
                class="dark:bg-dark-900 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                :disabled="disabled"
            >
                <option value="" disabled selected>Pilih Opsi</option>
                <option v-for="option in selectables" :key="option.value" :value="option.value">{{ option.text }}</option>
            </select>
            <ChevronDownIcon class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400" />
        </div>

        <!-- Radio -->
        <div v-else-if="inputType === 'radio'" class="flex gap-4 items-center py-2">
            <label v-for="option in selectables" :key="option.value" class="inline-flex items-center">
                <input
                    type="radio"
                    :value="option.value"
                    :checked="modelValue === option.value"
                    @change="$emit('update:modelValue', option.value)"
                    :disabled="disabled"
                    :class="['h-4 w-4 accent-brand-900', error ? 'border-red-500' : 'border-gray-300']"
                />
                <span class="ml-2 text-gray-700 dark:text-gray-400">{{ option.text }}</span>
            </label>
        </div>

        <!-- Textarea -->
        <textarea
            v-else-if="inputType === 'textarea'"
            :value="modelValue as string | number"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            :placeholder="placeholder"
            :rows="rows"
            :class="['w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                error ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
            ]"
            class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
            :disabled="disabled"
        />

        <!-- File -->
        <div v-else-if="inputType === 'file'" class="flex">
            <input ref="fileInput" type="file" @change="handleFileChange" :accept="accept" :multiple="multiple" :disabled="disabled" class="hidden" />
            <button @click="($refs.fileInput as HTMLInputElement)?.click()" type="button" :disabled="disabled"
                class="px-4 py-2.5 bg-gray-200 text-gray-700 rounded-l-lg hover:bg-gray-300 disabled:bg-gray-100 disabled:cursor-not-allowed font-medium text-sm">
                Choose file
            </button>
            <div class="flex-1 px-4 py-2.5 border border-l-0 border-gray-300 rounded-r-lg bg-white text-gray-500 text-sm flex items-center">
                {{ fileName || 'No file chosen' }}
            </div>
        </div>

        <!-- Regular Input (default) -->
        <input
            v-else
            :type="inputType"
            :value="modelValue"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            :placeholder="placeholder"
            :maxlength="max"
            :minlength="min"
            :pattern="pattern"
            :disabled="disabled"
            :class="['h-11 w-full rounded-lg border bg-transparent font-body px-4 py-2.5 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                error ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
            ]"
            class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
        />

        <p v-if="error" class="text-red-500 text-xs mt-1">{{ error }}</p>
    </div>
</template>
