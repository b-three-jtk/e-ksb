<script setup>
import Info from '@/Components/Form/Info.vue'
import moneyParser from '@/Composables/moneyParser'
import { computed } from 'vue'

const props = defineProps({
    data: Object,
})

const incomes = computed(() => [
    { label: 'Gaji Pokok & Tunjangan', model: 'jml_gaji_pokok' },
    { label: 'Penghasilan Usaha', model: 'jml_penghasilan_usaha' },
    { label: 'Penghasilan Pasangan', model: 'jml_penghasilan_pasangan' },
    { label: 'Penghasilan Lainnya', model: 'jml_penghasilan_lainnya' },
])

const expenses = computed(() => [
    { label: 'Biaya Hidup Keluarga', model: 'jml_biaya_hidup_keluarga' },
    { label: 'Biaya Pendidikan', model: 'jml_biaya_pendidikan' },
    { label: 'Jumlah Cicilan Lainnya', model: 'jml_cicilan' },
    { label: 'Jumlah Biaya Lainnya', model: 'jml_biaya_lainnya' },
])

const totalIncome = computed(() => {
    return incomes.value.reduce((total, item) => total + (Number(props.data.anggota[item.model]) || 0), 0)
})

const totalExpense = computed(() => {
    return expenses.value.reduce((total, item) => total + (Number(props.data.anggota[item.model]) || 0), 0)
})

const netIncome = computed(() => totalIncome.value - totalExpense.value)

</script>

<template>
    <div class="gap-6 flex flex-col">
        <div>
            <h1 class="card-title">Informasi Pekerjaan</h1>
            <div class="grid grid-cols-2 gap-6 mt-8">
                <Info label="Status Pekerjaan" :value="data.anggota.status_pekerjaan" />
                <Info label="Jabatan" :value="data.anggota.jabatan_pekerjaan" />
                <Info label="Nama Perusahaan atau Bisnis" :value="data.anggota.nama_perusahaan" />
                <Info label="Bidang Pekerjaan" :value="data.anggota.bidang_usaha" />
                <Info label="Lama Bekerja (Tahun)" :value="data.anggota.lama_bekerja" />
                <Info label="Kontak Perusahaan" :value="data.anggota.no_telp_kantor" />
                <Info label="Alamat Perusahaan" :value="data.anggota.alamat_tempat_bekerja" />
            </div>
        </div>
        <div class="card-layout">
            <h1 class="card-title">Data Penghasilan</h1>
            <table class="w-full text-sm mt-8 text-gray-500 dark:text-gray-400">
                <thead class="text-gray-400 border-y dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="py-4 text-left pl-6">Sumber Penghasilan</th>
                        <th class="py-4 text-right pr-6">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in incomes" :key="item.model"
                        class="bg-white border-b text-dark-text dark:text-gray-300 dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">{{ item.label }}</td>
                        <td class="py-4 text-right pr-6">{{ moneyParser(data.anggota[item.model]) }}</td>
                    </tr>
                    <tr class="font-semibold text-dark-text dark:text-gray-200">
                        <td class="pt-4 text-left pl-6">Total Penghasilan Bulanan</td>
                        <td class="pt-4 text-right pr-6">{{ moneyParser(totalIncome) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-layout">
            <h1 class="card-title">Data Pengeluaran</h1>
            <table class="w-full text-sm text-center mt-8 text-gray-500 dark:text-gray-400">
                <thead class="text-gray-400 border-y dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="py-4 text-left pl-6">Jenis Pengeluaran</th>
                        <th class="py-4 text-right pr-6">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in expenses" :key="item.model"
                        class="bg-white border-b text-dark-text dark:text-gray-300 dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">{{ item.label }}</td>
                        <td class="py-4 text-right pr-6">{{ moneyParser(data.anggota[item.model]) }}</td>
                    </tr>
                    <tr class="font-semibold text-dark-text dark:text-gray-200">
                        <td class="py-4 text-left pl-6">Total Pengeluaran Bulanan</td>
                        <td class="py-4 text-right pr-6">{{ moneyParser(totalExpense) }}</td>
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
