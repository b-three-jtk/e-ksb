<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Button from '@/Components/Form/Button.vue'
import moneyParser from '@/Composables/moneyParser'
import { computed } from 'vue'

const props = defineProps({
    form: Object,
    data: Object,
})

const emit = defineEmits(['addIncome', 'removeIncome', 'addExpense', 'removeExpense', 'update:form'])

const totalIncome = computed(() => {
    return props.form.member.incomes.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
})

const totalExpense = computed(() => {
    return props.form.member.expenses.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0)
})

const netIncome = computed(() => {
    return totalIncome.value - totalExpense.value
})
</script>

<template>
    <section>
        <div class="border-b border-gray-200 px-8 pb-4">
            <h1 class="card-title">Status & Tanggungan Keluarga</h1>
        </div>

        <div class="flex flex-col">
            <div class="border-b border-gray-200 grid grid-cols-2 gap-4 p-4">
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
                <div class="col-span-2">
                    <BaseInputAdmin v-model="form.income_type" label="Data Penghasilan" type="select"
                        :selectables="data.incomes.map(unit => ({ value: unit, text: unit }))" />
                </div>
                <div class="col-span-2 flex gap-4">
                    <BaseInputAdmin v-model="form.income_amount" placeholder="Jumlah" required />
                    <Button size="small" variant="primary" @click="$emit('addIncome')">Tambah</Button>
                </div>

                <!-- Tabel Penghasilan -->
                <div class="col-span-5">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-gray-400 border-y dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="py-4 text-left pl-6">Jenis Penghasilan</th>
                                <th class="py-4 text-right pr-6">Jumlah (Rp)</th>
                                <th class="py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody v-if="form.member.incomes.length > 0">
                            <tr v-for="(item, index) in form.member.incomes" :key="index"
                                class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                                <td class="py-2 text-left pl-6">{{ item.financial_type }}</td>
                                <td class="py-2 text-right pr-6">{{ moneyParser(item.amount) }}</td>
                                <td class="py-2 text-center flex justify-center">
                                    <Button size="small" variant="light"
                                        @click="$emit('removeIncome', index)">-</Button>
                                </td>
                            </tr>
                            <tr class="font-semibold text-dark-text">
                                <td class="pt-4 text-left pl-6">Total Penghasilan Bulanan</td>
                                <td colspan="2" class="pt-4 text-right pr-6">{{ moneyParser(totalIncome) }}</td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                                <td colspan="3" class="py-4 text-center text-gray-400">Belum ada data penghasilan</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pengeluaran -->
            <div class="grid grid-cols-5 gap-4 items-end p-4 border-b border-gray-200">
                <div class="col-span-2">
                    <BaseInputAdmin v-model="form.expense_type" label="Data Pengeluaran" type="select"
                        :selectables="data.expenses.map(unit => ({ value: unit, text: unit }))" />
                </div>
                <div class="col-span-2 flex gap-4">
                    <BaseInputAdmin v-model="form.expense_amount" placeholder="Jumlah" required />
                    <Button size="small" variant="primary" @click="$emit('addExpense')">Tambah</Button>
                </div>

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
                        <tbody v-if="form.member.expenses.length > 0">
                            <tr v-for="(item, index) in form.member.expenses" :key="index"
                                class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                                <td class="py-2 text-left pl-6">{{ item.financial_type }}</td>
                                <td class="py-2 text-right pr-6">{{ moneyParser(item.amount) }}</td>
                                <td class="py-2 text-center flex justify-center">
                                    <Button size="small" variant="light"
                                        @click="$emit('removeExpense', index)">-</Button>
                                </td>
                            </tr>
                            <tr class="font-semibold text-dark-text">
                                <td class="py-4 text-left pl-6">Total Pengeluaran Bulanan</td>
                                <td colspan="2" class="py-4 text-right pr-6">{{ moneyParser(totalExpense) }}</td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                                <td colspan="3" class="py-4 text-center text-gray-400">Belum ada data pengeluaran</td>
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
