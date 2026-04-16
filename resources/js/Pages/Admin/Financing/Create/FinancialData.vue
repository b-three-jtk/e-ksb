<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Button from '@/Components/Form/Button.vue'
import moneyParser from '@/Composables/moneyParser'
import { computed } from 'vue'

const props = defineProps({
    form: Object,
    marriageStatuses: Array,
    income_types: Array,
    expense_types: Array,
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
                <BaseInputAdmin v-model="form.marital_status" label="Status Perkawinan" type="select"
                    :selectables="marriageStatuses.map(unit => ({ value: unit, text: unit }))" />
                <BaseInputAdmin v-model="form.dependents" label="Jumlah Tanggungan Keluarga" type="number" />
            </div>

            <!-- Penghasilan -->
            <div class="grid grid-cols-5 gap-4 items-end p-4 border-b border-gray-200">
                <div class="col-span-2">
                    <BaseInputAdmin v-model="form.income_type" label="Data Penghasilan" type="select"
                        :selectables="income_types.map(unit => ({ value: unit, text: unit }))" />
                </div>
                <div class="col-span-2 flex gap-4">
                    <BaseInputAdmin v-model="form.income_amount" isMoney placeholder="Jumlah" required />
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
                                    <Button size="small" variant="light" @click="$emit('removeIncome', index)">-</Button>
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
                        :selectables="expense_types.map(unit => ({ value: unit, text: unit }))" />
                </div>
                <div class="col-span-2 flex gap-4">
                    <BaseInputAdmin v-model="form.expense_amount" isMoney placeholder="Jumlah" required />
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
                                    <Button size="small" variant="light" @click="$emit('removeExpense', index)">-</Button>
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
                <BaseInputAdmin type="file" label="Penghasilan (Slip Gaji)" v-model="form.income_slip"
                    accept=".pdf,.jpg,.jpeg,.png" required />
                <BaseInputAdmin type="file" label="Foto Kartu Keluarga" v-model="form.family_card"
                    accept=".jpg,.jpeg,.png" required />
                <BaseInputAdmin type="file" label="Foto Buku Tabungan/Rekening Koran 3 Bulan Terakhir"
                    v-model="form.bank_book" accept=".pdf,.jpg,.jpeg,.png" required />
            </div>
        </div>
    </section>
</template>
