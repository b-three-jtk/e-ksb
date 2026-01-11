<script setup lang="ts">
import { Icon } from '@iconify/vue'

type SelectFilter = {
    key: string
    label: string
    options: any[]
    optionLabel: string
    optionValue: string
}

const props = defineProps<{
    perPage?: number
    search?: string
    filters?: Record<string, any>
    perPageOptions?: number[]
    selects?: SelectFilter[]
    searchTooltip?: string[]
    showBorder?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:perPage', value: number): void
    (e: 'update:search', value: string): void
    (e: 'update:filters', value: Record<string, any>): void
}>()

const updateFilter = (key: string, value: any) => {
    emit('update:filters', {
        ...(props.filters ?? {}),
        [key]: value
    })
}

const onPerPageChange = (e: Event) => {
    const target = e.target as HTMLSelectElement
    emit('update:perPage', Number(target.value))
}

const onSearchInput = (e: Event) => {
    const target = e.target as HTMLInputElement
    emit('update:search', target.value)
}
</script>

<template>
    <div
    class="px-6 py-4
            flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            :class="{ 'border-b border-gray-200 dark:border-gray-700': props.showBorder !== false }"
    >
        <!-- Left -->
        <div v-if="props.perPage !== undefined" class="flex items-center gap-3">
            <span class="text-sm text-gray-500">Tampilkan</span>

            <select
                :value="props.perPage"
                @change="onPerPageChange"
                class="border rounded-md px-3 py-2 text-sm
                       bg-white text-gray-900
                       dark:bg-gray-700 dark:border-gray-600 dark:text-white
                       focus:ring-2 focus:ring-blue-500"
            >
                <option
                    v-for="n in props.perPageOptions ?? [10,25,50,100]"
                    :key="n"
                    :value="n"
                >
                    {{ n }}
                </option>
            </select>

            <span class="text-sm text-gray-500">data per halaman</span>
        </div>

        <!-- Right -->
        <div class="flex items-center gap-3">
            <!-- Search with optional Tooltip -->
            <div v-if="props.search !== undefined" class="flex items-center gap-2">
                <div class="relative">
                    <Icon
                        icon="ic:baseline-search"
                        class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
                    />

                    <input
                        :value="props.search"
                        @input="onSearchInput"
                        type="text"
                        placeholder="Search"
                        class="pl-10 pr-4 py-2 w-64 text-sm
                               border rounded-lg
                               bg-white text-gray-900
                               dark:bg-gray-700 dark:border-gray-600 dark:text-white
                               dark:placeholder-gray-400
                               focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div v-if="props.searchTooltip && props.searchTooltip.length > 0" class="relative group/tooltip">
                    <span class="icon-[mdi--information-variant-circle-outline] w-5 h-5 cursor-help text-slate-800 dark:text-slate-100"></span>
                    <div class="absolute right-0 top-full mt-2 hidden group-hover/tooltip:block
                                bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg py-3 px-4 z-50 w-max shadow-lg">
                        <div class="font-semibold mb-1">Cari berdasarkan:</div>
                        <div class="space-y-1">
                            <div v-for="(item, index) in props.searchTooltip" :key="index">• {{ item }}</div>
                        </div>
                        <div class="absolute bottom-full right-4 border-4 border-transparent border-b-gray-900 dark:border-b-gray-700"></div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Selects -->
            <select
                v-for="select in props.selects"
                :key="select.key"
                :value="props.filters?.[select.key] ?? ''"
                @change="updateFilter(select.key, ($event.target as HTMLSelectElement).value)"
                class="border rounded-md px-3 py-2 text-sm
                       bg-white text-gray-900
                       dark:bg-gray-700 dark:border-gray-600 dark:text-white
                       focus:ring-2 focus:ring-blue-500"
            >
                <option value="">
                    {{ select.label }}
                </option>

                <option
                    v-for="opt in select.options"
                    :key="opt[select.optionValue]"
                    :value="opt[select.optionValue]"
                >
                    {{ opt[select.optionLabel] }}
                </option>
            </select>
        </div>
    </div>
</template>