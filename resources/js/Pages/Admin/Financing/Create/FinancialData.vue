<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import moneyParser from '@/Composables/moneyParser'
import { computed } from 'vue'

const props = defineProps({
    form: Object,
    data: Object,
    errors: Object,
})

const emit = defineEmits(['validate-field'])

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

const totalIncome = computed(() =>
    incomes.value.reduce((total, item) => total + (Number(props.form.member[item.model]) || 0), 0)
)

const totalExpense = computed(() =>
    expenses.value.reduce((total, item) => total + (Number(props.form.member[item.model]) || 0), 0)
)

const netIncome = computed(() => totalIncome.value - totalExpense.value)

const onFieldChange = (field) => emit('validate-field', field)
</script>

<template>
    <section>
        <div class="border-b border-gray-200 px-8 pb-4">
            <h1 class="card-title">Status & Tanggungan Keluarga</h1>
        </div>

        <div class="flex flex-col">
            <!-- Data Pekerjaan -->
            <div class="border-b border-gray-200 grid grid-cols-2 gap-4 p-4">
                <BaseInputAdmin
                    v-model="form.member.employment_status"
                    required
                    label="Status Pekerjaan"
                    placeholder="Masukkan status pekerjaan, contoh: Karyawan Swasta"
                    @input="onFieldChange('employment_status')"
                />
                <BaseInputAdmin
                    v-model="form.member.job_title"
                    required
                    label="Jabatan"
                    placeholder="Masukkan jabatan"
                    :error="errors?.job_title"
                    @input="onFieldChange('job_title')"
                />
                <BaseInputAdmin
                    v-model="form.member.company_or_business_name"
                    required
                    label="Nama Perusahaan atau Bisnis"
                    placeholder="Masukkan nama perusahaan atau bisnis"
                    :error="errors?.company_or_business_name"
                    @input="onFieldChange('company_or_business_name')"
                />
                <BaseInputAdmin
                    v-model="form.member.business_field"
                    required
                    label="Bidang Pekerjaan"
                    placeholder="Masukkan bidang pekerjaan"
                    :error="errors?.business_field"
                    @input="onFieldChange('business_field')"
                />
                <BaseInputAdmin
                    v-model="form.member.tenure_year"
                    required
                    label="Lama Bekerja (Tahun)"
                    type="number"
                    placeholder="Masukkan lama bekerja"
                    :error="errors?.tenure_year"
                    @input="onFieldChange('tenure_year')"
                />
                <BaseInputAdmin
                    v-model="form.member.workplace_contact"
                    max="13"
                    required
                    label="Kontak Perusahaan"
                    placeholder="Masukkan kontak perusahaan"
                    :error="errors?.workplace_contact"
                    @input="onFieldChange('workplace_contact')"
                />
                <BaseInputAdmin
                    rows="3"
                    v-model="form.member.workplace_address"
                    required
                    label="Alamat Perusahaan"
                    type="textarea"
                    placeholder="Masukkan alamat perusahaan"
                    :error="errors?.workplace_address"
                    @input="onFieldChange('workplace_address')"
                />
            </div>

            <!-- Tabel Penghasilan -->
            <div class="p-4 border-b border-gray-200">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-gray-400 border-y">
                        <tr>
                            <th class="py-4 text-left pl-6">Jenis Penghasilan</th>
                            <th class="py-4 text-right pr-6">Jumlah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="income in incomes" :key="income.model"
                            class="bg-white border-b text-dark-text">
                            <td class="py-2 text-left pl-6">{{ income.label }}</td>
                            <td class="py-2 text-right pr-6">
                                <BaseInputAdmin
                                    v-model="form.member[income.model]"
                                    isMoney
                                    input-class="text-right"
                                />
                            </td>
                        </tr>
                        <tr class="font-semibold text-dark-text">
                            <td class="pt-4 text-left pl-6">Total Penghasilan Bulanan</td>
                            <td class="pt-4 text-right pr-6">{{ moneyParser(totalIncome) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tabel Pengeluaran -->
            <div class="p-4 border-b border-gray-200">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-gray-400 border-y">
                        <tr>
                            <th class="py-4 text-left pl-6">Jenis Pengeluaran</th>
                            <th class="py-4 text-right pr-6">Jumlah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="expense in expenses" :key="expense.model"
                            class="bg-transparent border-b text-dark-text">
                            <td class="py-2 text-left pl-6">{{ expense.label }}</td>
                            <td class="py-2 text-right pr-6">
                                <BaseInputAdmin
                                    v-model="form.member[expense.model]"
                                    isMoney
                                    input-class="text-right"
                                />
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

            <!-- File uploads -->
            <div class="grid px-6 pt-6 pb-4 gap-4">
                <div>
                    <BaseInputAdmin
                        type="file"
                        label="Penghasilan (Slip Gaji)"
                        v-model="form.income_slip_file"
                        accept=".jpg,.jpeg,.png"
                        required
                        :error="errors?.income_slip_file"
                        @change="onFieldChange('income_slip_file')"
                    />
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <p>Format: JPG, JPEG, PNG</p>
                        <p>Max. 2 MB per file</p>
                    </div>
                </div>
                <div>
                    <BaseInputAdmin
                        type="file"
                        label="Foto Buku Tabungan/Rekening Koran 3 Bulan Terakhir"
                        v-model="form.bank_book_file"
                        accept=".jpg,.jpeg,.png"
                        required
                        :error="errors?.bank_book_file"
                        @change="onFieldChange('bank_book_file')"
                    />
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <p>Format: JPG, JPEG, PNG</p>
                        <p>Max. 2 MB per file</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
