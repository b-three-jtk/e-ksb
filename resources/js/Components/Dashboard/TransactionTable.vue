<script setup>
import EyeIcon from '@/Icons/EyeIcon.vue';

defineProps({
    data: Object,
    role: String,
    selectedTransactionFilter: String,
})

const emit = defineEmits(['update:selectedTransactionFilter']);
</script>

<template>
    <div class="flex justify-between">
        <h1 class="card-title">Transaksi Terbaru</h1>
        <div class="relative z-20 bg-transparent">
            <select :value="selectedTransactionFilter" @input="$emit('update:selectedTransactionFilter', $event.target.value)"
                class="h-11 w-full font-body appearance-none rounded-lg border px-4 bg-white pr-11 text-sm shadow-theme-xs focus:outline-hidden dark:bg-dark-900 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="all">Semua</option>
                <option value="simpanan">Simpanan</option>
                <option value="pembiayaan">Pembiayaan</option>
            </select>
            <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400"
                viewBox="0 0 20 20" fill="none">
                <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </div>
    </div>
    <div class="max-w-full mt-4 overflow-x-auto custom-scrollbar">
        <table class="min-w-full">
            <thead
                class="border-y-2 border-gray-100 dark:border-gray-500 font-medium text-gray-500 px-2 dark:text-gray-400">
                <tr class="">
                    <td class="py-5 text-center">No. Transaksi</td>
                    <td class="py-5 text-center">Anggota</td>
                    <td class="py-5 text-center">Produk</td>
                    <td v-if="role === 'Dewan Pengawas Syariah'" class="py-5 text-center">Akad</td>
                    <td class="py-5 text-center">Tanggal</td>
                    <td class="py-5 text-center">Aksi</td>
                </tr>
            </thead>
            <tbody>
                <tr v-for="data in data.recent_transactions" class="border-y-2 border-gray-100 dark:border-gray-500">
                    <td class="py-5 text-center">{{ data.transaction_code }}</td>
                    <td class="py-5 text-center">{{ data.user_name }}</td>
                    <td class="py-5 text-center">{{ data.product }}</td>
                    <td class="py-5 text-center">{{ data.created_at }}</td>
                    <td class="py-5 text-center" v-if="role === 'Dewan Pengawas Syariah'">{{ data.akad
                    }}</td>
                    <td class="py-5 text-center">
                        <a
                            :href="data.product === 'Simpanan' ? `/admin/savings/show/${data.id}` : `/admin/financings/show/${data.id}`">
                            <EyeIcon
                                class="w-5 h-5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" />
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>