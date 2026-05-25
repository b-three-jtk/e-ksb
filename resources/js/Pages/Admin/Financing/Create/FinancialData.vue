<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Button from '@/Components/Form/Button.vue'
import moneyParser from '@/Composables/moneyParser'
import { computed } from 'vue'

const props = defineProps({
    form: Object,
    data: Object,
})

const emit = defineEmits('update:form')

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
    return incomes.value.reduce((total, item) => total + (Number(props.form.member[item.model]) || 0), 0)
})

const totalExpense = computed(() => {
    return expenses.value.reduce((total, item) => total + (Number(props.form.member[item.model]) || 0), 0)
})

const netIncome = computed(() => totalIncome.value - totalExpense.value)
</script>

<template>
    <section>
        <div class="border-b border-gray-200 px-8 pb-4">
            <h1 class="card-title">Status & Tanggungan Keluarga</h1>
        </div>

        <div class="flex flex-col">
            <div class="border-b border-gray-200 grid grid-cols-2 gap-4 p-4">
                <BaseInputAdmin v-model="form.member.employment_status" required label="Status Pekerjaan" placeholder="Masukkan status pekerjaan, contoh: Karyawan Swasta" />
                <BaseInputAdmin v-model="form.member.job_title" required label="Jabatan" placeholder="Masukkan jabatan" />
                <BaseInputAdmin v-model="form.member.company_or_business_name" required label="Nama Perusahaan atau Bisnis"
                    placeholder="Masukkan nama perusahaan atau bisnis" />
                <BaseInputAdmin v-model="form.member.business_field" required label="Bidang Pekerjaan"
                    placeholder="Masukkan bidang pekerjaan" />
                <BaseInputAdmin v-model="form.member.tenure_year" required label="Lama Bekerja (Tahun)" type="number"
                    placeholder="Masukkan lama bekerja" />
                <BaseInputAdmin v-model="form.member.workplace_contact" max="13" required label="Kontak Perusahaan"
                    placeholder="Masukkan kontak perusahaan" />
                <BaseInputAdmin rows="3" v-model="form.member.workplace_address" required label="Alamat Perusahaan"
                    type="textarea" placeholder="Masukkan alamat perusahaan" />
            </div>

            <!-- Penghasilan -->
            <div class="grid grid-cols-5 gap-4 items-end p-4 border-b border-gray-200">
                <!-- Tabel Penghasilan -->
                <div class="col-span-5">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-gray-400 border-y dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="py-4 text-left pl-6">Jenis Penghasilan</th>
                                <th class="py-4 text-right pr-6">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="income in incomes" :key="income.model"
                                class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                                <td class="py-2 text-left pl-6">{{ income.label }}</td>
                                <td class="py-2 text-right pr-6">
                                    <BaseInputAdmin v-model="form.member[income.model]" type="number" placeholder="0"
                                        input-class="text-right" />
                                </td>
                            </tr>
                            <tr class="font-semibold text-dark-text">
                                <td class="pt-4 text-left pl-6">Total Penghasilan Bulanan</td>
                                <td class="pt-4 text-right pr-6">{{ moneyParser(totalIncome) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pengeluaran -->
            <div class="grid grid-cols-5 gap-4 items-end p-4 border-b border-gray-200">

                <!-- Tabel Pengeluaran -->
                <div class="col-span-5">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-gray-400 border-y dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="py-4 text-left pl-6">Jenis Pengeluaran</th>
                                <th class="py-4 text-right pr-6">Jumlah (Rp)</th>
                                <th class="py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="expense in expenses" :key="expense.model"
                                class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                                <td class="py-2 text-left pl-6">{{ expense.label }}</td>
                                <td class="py-2 text-right pr-6">
                                    <BaseInputAdmin v-model="form.member[expense.model]" type="number" placeholder="0"
                                        input-class="text-right" />
                                </td>
                            </tr>
                            <tr class="font-semibold text-dark-text">
                                <td class="py-4 text-left pl-6">Total Pengeluaran Bulanan</td>
                                <td class="py-4 text-right pr-6">{{ moneyParser(totalExpense) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="font-semibold flex justify-between text-primary bg-light-bg rounded-xl border mt-2">
                        <div class="py-4 text-left pl-6">Sisa Penghasilan Bulanan</div>
                        <div class="pt-4 text-right pr-6">{{ moneyParser(netIncome) }}</div>
                    </div>
                </div>
            </div>

            <!-- File uploads -->
            <div class="grid px-6 pt-6 gap-4">
                <BaseInputAdmin type="file" label="Penghasilan (Slip Gaji)" v-model="form.income_slip_file"
                    accept=".pdf,.jpg,.jpeg,.png" required />
                <div class="flex justify-between text-xs text-gray-400">
                    <p>Format: JPG, JPEG, PNG, PDF</p>
                    <p>Max. 5 MB per file</p>
                </div>
                <BaseInputAdmin type="file" label="Foto Buku Tabungan/Rekening Koran 3 Bulan Terakhir"
                    v-model="form.bank_book_file" accept=".pdf,.jpg,.jpeg,.png" required />
                <div class="flex justify-between text-xs text-gray-400">
                    <p>Format: JPG, JPEG, PNG, PDF</p>
                    <p>Max. 5 MB per file</p>
                </div>
            </div>
        </div>
    </section>
</template>
