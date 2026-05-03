<script setup>
import Info from '@/Components/Form/Info.vue'
import moneyParser from '@/Composables/moneyParser'
import { computed } from 'vue'

const props = defineProps({
    data: Object,
})

const totalIncome = computed(() => {
    return props.data.member.incomes.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
})

const totalExpense = computed(() => {
    return props.data.member.expenses.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
})

const netIncome = computed(() => {
    return totalIncome.value - totalExpense.value
})
</script>

<template>
    <div class="gap-6 flex flex-col">
        <div>
            <h1 class="card-title">Informasi Pekerjaan</h1>
            <div class="grid grid-cols-2 gap-6 mt-8">
                <Info label="Jabatan" :value="data.member.job_title" />
                <Info label="Nama Perusahaan atau Bisnis" :value="data.member.company_or_business_name" />
                <Info label="Bidang Pekerjaan" :value="data.member.business_field" />
                <Info label="Lama Bekerja (Tahun)" :value="data.member.tenure_year" />
                <Info label="Kontak Perusahaan" :value="data.member.workplace_contact" />
                <Info label="Alamat Perusahaan" :value="data.member.workplace_address" />
            </div>
        </div>
        <div class="card-layout">
            <h1 class="card-title">Data Penghasilan</h1>
            <table class="w-full text-sm mt-8 text-gray-500 dark:text-gray-400">
                <thead class="text-gray-400 border-y dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="py-4 text-left pl-6">Sumber Penghasilan</th>
                        <th class="py-4 text-right pl-6">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody v-if="data.member.incomes.length > 0">
                    <tr v-for="(item, index) in data.member.incomes" :key="index"
                        class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">{{ item.financial_type }}</td>
                        <td class="py-4 text-right pl-6">{{ moneyParser(item.amount) }}</td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="3" class="py-4 text-center">Tidak ada data penghasilan</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-layout">
            <h1 class="card-title">Data Penghasilan</h1>
            <table class="w-full text-sm text-center mt-8 text-gray-500 dark:text-gray-400">
                <thead class="text-gray-400 border-y dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="py-4 text-left pl-6">Sumber Penghasilan</th>
                        <th class="py-4 text-right pl-6">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody v-if="data.member.expenses.length > 0">
                    <tr v-for="(item, index) in data.member.expenses" :key="index"
                        class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">{{ item.financial_type }}</td>
                        <td class="py-4 text-right pl-6">{{ moneyParser(item.amount) }}</td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="3" class="py-4 text-center">Tidak ada data penghasilan</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="bg-light-bg flex justify-between items-center text-primary border rounded-2xl px-10 py-8">
            <p class="text-lg font-semibold">Sisa Penghasilan Bulanan</p>
            <p class="text-lg font-semibold">{{ moneyParser(netIncome) }}</p>
        </div>
    </div>
</template>
