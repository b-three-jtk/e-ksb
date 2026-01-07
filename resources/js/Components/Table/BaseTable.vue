<script setup lang="ts">
import { Icon } from '@iconify/vue'

type Column = {
    key: string
    label: string
    sortable?: boolean
    align?: 'left' | 'center' | 'right'
}

defineProps<{
    columns: Column[]
    data: any[]
    pagination?: {
        current_page: number
        per_page: number
    }
    sortBy?: string
    sortDir?: 'asc' | 'desc'
}>()

const emit = defineEmits<{
    (e: 'sort', field: string): void
}>()

const toggleSort = (field: string) => {
    emit('sort', field)
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Header -->
            <thead class="font-head bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        @click="col.sortable && toggleSort(col.key)"
                        class="px-6 py-3 text-sm font-medium
                               text-gray-700 dark:text-gray-200"
                        :class="[
                            col.align === 'center' && 'text-center',
                            col.align === 'right' && 'text-right',
                            !col.align && 'text-left',
                            col.sortable && 'cursor-pointer select-none'
                        ]"
                    >
                        <div class="flex items-center gap-1">
                            {{ col.label }}

                            <template v-if="col.sortable">
                                <Icon
                                    v-if="sortBy === col.key && sortDir === 'asc'"
                                    icon="tabler:chevron-down"
                                    class="w-4 h-4"
                                />
                                <Icon
                                    v-else-if="sortBy === col.key && sortDir === 'desc'"
                                    icon="tabler:chevron-up"
                                    class="w-4 h-4"
                                />
                                <Icon
                                    v-else
                                    icon="tabler:chevrons-up-down"
                                    class="w-4 h-4 opacity-40"
                                />
                            </template>
                        </div>
                    </th>
                </tr>
            </thead>

            <!-- Body -->
            <tbody class="font-body bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr
                    v-for="(row, index) in data"
                    :key="row.id ?? index"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    <td
                        v-for="col in columns"
                        :key="col.key"
                        class="px-6 py-4 text-sm text-gray-800 dark:text-gray-100"
                        :class="[
                            col.align === 'center' && 'text-center',
                            col.align === 'right' && 'text-right'
                        ]"
                    >
                        <div
                            :class="[
                                col.align === 'center' && 'flex justify-center',
                                col.align === 'right' && 'flex justify-end'
                            ]"
                        >
                            <!-- Slot -->
                            <slot
                                :name="`cell-${col.key}`"
                                :row="row"
                                :index="index"
                            >
                                {{ row[col.key] }}
                            </slot>
                        </div>
                    </td>
                </tr>


                <!-- Ini Buat Kalau Tidak Ada Data -->
                <tr v-if="data.length === 0">
                    <td
                        :colspan="columns.length"
                        class="font-head px-6 py-8 text-center text-gray-500 dark:text-gray-400"
                    >
                        Tidak ada data
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>