<script setup>
import Info from '@/Components/Form/Info.vue'
import moneyParser from '@/Composables/moneyParser'
import { computed } from 'vue'

const props = defineProps({
    data: Object,
})

const incomes = computed(() => [
    { label: 'Gaji Pokok & Tunjangan', model: 'gaji_pokok_amount' },
    { label: 'Penghasilan Usaha', model: 'penghasilan_usaha_amount' },
    { label: 'Penghasilan Pasangan', model: 'penghasilan_pasangan_amount' },
    { label: 'Penghasilan Lainnya', model: 'penghasilan_lainnya_amount' },
])

const expenses = computed(() => [
    { label: 'Biaya Hidup Keluarga', model: 'biaya_hidup_keluarga_amount' },
    { label: 'Biaya Pendidikan', model: 'biaya_pendidikan_amount' },
    { label: 'Jumlah Cicilan Lainnya', model: 'jumlah_cicilan_amount' },
    { label: 'Jumlah Biaya Lainnya', model: 'jumlah_biaya_lainnya_amount' },
])

const totalIncome = computed(() => {
    return incomes.value.reduce((total, item) => total + (Number(props.data.member[item.model]) || 0), 0)
})

const totalExpense = computed(() => {
    return expenses.value.reduce((total, item) => total + (Number(props.data.member[item.model]) || 0), 0)
})

const netIncome = computed(() => totalIncome.value - totalExpense.value)

</script>

<template>
    <div class="gap-6 flex flex-col">
        <div>
            <h1 class="card-title">Informasi Pekerjaan</h1>
            <div class="grid grid-cols-2 gap-6 mt-8">
                <Info label="Status Pekerjaan" :value="data.member.employment_status" />
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
                <tbody>
                    <tr v-for="item in incomes" :key="item.model"
                        class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">{{ item.label }}</td>
                        <td class="py-4 text-right pl-6">{{ moneyParser(data.member[item.model]) }}</td>
                    </tr>
                    <tr class="font-semibold text-dark-text">
                        <td class="pt-4 text-left pl-6">Total Penghasilan Bulanan</td>
                        <td class="pt-4 text-right">{{ moneyParser(totalIncome) }}</td>
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
                <tbody>
                    <tr v-for="item in expenses" :key="item.model"
                        class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">{{ item.label }}</td>
                        <td class="py-4 text-right pl-6">{{ moneyParser(data.member[item.model]) }}</td>
                    </tr>
                    <tr class="font-semibold text-dark-text">
                        <td class="py-4 text-left pl-6">Total Pengeluaran Bulanan</td>
                        <td class="py-4 text-right">{{ moneyParser(totalExpense) }}</td>
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
