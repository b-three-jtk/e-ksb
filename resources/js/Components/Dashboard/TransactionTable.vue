<script setup>
defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="max-w-full mt-4 overflow-x-auto custom-scrollbar">
        <table class="min-w-full">
            <thead class="border-y-2 border-gray-100 dark:border-gray-500 font-medium text-gray-500 px-2 dark:text-gray-400">
                <tr>
                    <td v-for="col in columns" :key="col.key" class="py-5 text-center">
                        {{ col.label }}
                    </td>
                </tr>
            </thead>
            <tbody class="dark:text-gray-400">
                <tr v-for="(row, index) in rows" :key="index" class="border-y-2 border-gray-100 dark:border-gray-500">
                    <td v-for="col in columns" :key="col.key" class="py-5 px-2 items-center text-center">
                        <slot :name="col.key" :item="row">
                            {{ row[col.key] }}
                        </slot>
                    </td>
                </tr>
                <tr v-if="rows.length === 0">
                    <td :colspan="columns.length" class="py-5 text-center text-gray-500">
                        Tidak ada data.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
