<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Info from '@/Components/Form/Info.vue'
import parseCurrencyAmount from '@/Composables/moneyParser.js'
import { computed, ref, watch } from 'vue'

const props = defineProps({
    form: Object,
})

const tenor = ref(12)

const totalPrice = computed(() => {
    const costPrice = parseFloat(props.form.financing.cost_price) || 0
    const marginAmount = parseFloat(props.form.financing.margin_amount) || 0
    const downPayment = parseFloat(props.form.financing.down_payment) || 0
    return (costPrice + marginAmount - downPayment) || 0
})

const monthlyInstallment = computed(() => {
    return totalPrice.value / tenor.value || 0
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
    return incomes.value.reduce((total, item) => total + (Number(props.form.member[item.model]) || 0), 0)
})

const totalExpense = computed(() => {
    return expenses.value.reduce((total, item) => total + (Number(props.form.member[item.model]) || 0), 0)
})

const monthlyIncome = computed(() => {
    return (totalIncome.value - totalExpense.value) || 0
})

const remainingIncome = computed(() => {
    return monthlyIncome.value - monthlyInstallment.value || 0
})

const firstDueDate = computed(() => {
    const baseDate = props.form.financing.akad_date ? new Date(props.form.financing.akad_date) : new Date()
    baseDate.setMonth(baseDate.getMonth() + 1)

    return baseDate.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    })
})

const lastDueDate = computed(() => {
    const baseDate = props.form.financing.akad_date ? new Date(props.form.financing.akad_date) : new Date()
    baseDate.setMonth(baseDate.getMonth() + tenor.value)

    return baseDate.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    })
})

watch([tenor, monthlyInstallment, monthlyIncome], () => {
    props.form.financing.tenor = tenor.value
    props.form.monthly_installment = monthlyInstallment.value
    props.form.monthly_income = monthlyIncome.value
}, { immediate: true })

const paymentMethod = {
    Cicilan: 'Cicilan',
    Tunai: 'Tunai',
    Tangguh: 'Tangguh'
}
</script>

<template>
    <section>
        <div class="border-b border-gray-200 px-8 pb-4">
            <h1 class="card-title">Finalisasi Pembiayaan Murabahah</h1>
        </div>

        <!-- Detail Objek Pembiayaan -->
        <section class="px-8 py-4">
            <h1 class="card-title text-lg!">Detail Objek Pembiayaan</h1>
            <div class="card-layout grid grid-cols-2 gap-4 mt-2">
                <Info label="Nama Barang" :value="form.financing.name" />
                <Info label="Kualitas" :value="form.financing.condition" />
                <Info label="Kuantitas" :value="form.financing.qty" />
                <Info label="Detail Spesifikasi" :value="form.financing.specification" />
            </div>
        </section>

        <!-- Rincian Harga Murabahah -->
        <section class="px-8 py-4">
            <h1 class="card-title text-lg!">Rincian Harga Murabahah</h1>
            <div class="border rounded-2xl overflow-hidden mt-2">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-dark-text border-y dark:bg-gray-700 dark:text-gray-400">
                        <tr class="border-b">
                            <th class="text-left pl-6 py-4">Komponen</th>
                            <th class="text-right pr-6 py-4">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Harga Perolehan Barang</td>
                            <td class="text-right pr-6 py-4">{{ parseCurrencyAmount(form.financing.cost_price) }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Margin</td>
                            <td class="text-right pr-6 py-4">{{ parseCurrencyAmount(form.financing.margin_amount) }}
                            </td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Uang Muka</td>
                            <td class="text-right pr-6 py-4">{{ parseCurrencyAmount(form.financing.down_payment) }}</td>
                        </tr>
                        <tr class="border-b bg-light-bg text-primary">
                            <td class="text-left pl-6 py-4 font-semibold">Total Harga Murabahah</td>
                            <td class="text-right pr-6 py-4 font-semibold">{{ parseCurrencyAmount(totalPrice) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grid grid-cols-2 gap-4 px-8 py-4">
            <BaseInputAdmin required v-model="form.financing.payment_method" label="Metode Pembayaran" type="select"
                :selectables="Object.values(paymentMethod).map(value => ({ value, text: value }))" />
            <BaseInputAdmin v-model="form.financing.akad_date" label="Tanggal Akad" type="date" />
        </div>

        <section v-if="form.financing.payment_method === 'Cicilan'" class="px-8 py-4">
            <h1 class="card-title text-lg!">Simulasi Cicilan</h1>
            <div class="bg-white border rounded-2xl p-6 mt-4">
                <!-- Tenor Slider -->
                <div class="mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <label class="text-sm font-medium text-gray-700">Jangka Waktu Cicilan</label>
                        <span class="text-lg font-semibold text-primary">{{ tenor }} Bulan</span>
                    </div>
                    <input v-model.number="tenor" type="range" min="3" max="60" step="1"
                        class="w-full h-2 rounded-lg appearance-none cursor-pointer"
                        :style="{
                            background: `linear-gradient(to right, #007943 0%, #007943 ${((tenor - 3) / (60 - 3)) * 100}%, #e5e7eb ${((tenor - 3) / (60 - 3)) * 100}%, #e5e7eb 100%)`
                        }" />
                    <div class="flex justify-between text-xs text-gray-500 mt-2">
                        <span>3</span>
                        <span>60</span>
                    </div>
                </div>

                <!-- Cicilan Info Grid -->
                <div class="grid grid-cols-2 gap-6">
                    <!-- Jumlah Pembiayaan -->
                    <div>
                        <p class="text-xs text-gray-500 mb-2">Jumlah Pembiayaan</p>
                        <p class="text-lg font-semibold text-dark-text">{{ parseCurrencyAmount(totalPrice) }}</p>
                    </div>

                    <!-- Perkiraan Cicilan -->
                    <div>
                        <p class="text-xs text-gray-500 mb-2">Perkiraan Cicilan</p>
                        <p class="text-lg font-semibold text-dark-text">{{ parseCurrencyAmount(monthlyInstallment)
                            }}<span class="text-sm text-gray-500">/bulan</span></p>
                    </div>
                </div>

                <!-- Sisa Penghasilan -->
                <div class="mt-6 pt-6 border-t">
                    <p class="text-xs text-gray-500 mb-2">Sisa Penghasilan Bulanan (setelah cicilan)</p>
                    <p class="text-lg font-semibold" :class="remainingIncome >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ parseCurrencyAmount(remainingIncome) }}
                    </p>
                </div>
            </div>
        </section>

        <section v-if="form.financing.payment_method === 'Cicilan'" class="px-8 py-4">
            <h1 class="card-title text-lg!">Skema Angsuran</h1>
            <div class="border rounded-2xl overflow-hidden mt-2">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-dark-text border-y dark:bg-gray-700 dark:text-gray-400">
                        <tr class="border-b">
                            <th class="text-left pl-6 py-4">Keterangan</th>
                            <th class="text-right pr-6 py-4">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Tenor / Jangka Waktu</td>
                            <td class="text-right pr-6 py-4">{{ tenor }} Bulan</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Angsuran/bulan</td>
                            <td class="text-right pr-6 py-4">{{ parseCurrencyAmount(monthlyInstallment) }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Jatuh Tempo Pertama</td>
                            <td class="text-right pr-6 py-4">{{ firstDueDate }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Jatuh Tempo Terakhir</td>
                            <td class="text-right pr-6 py-4">{{ lastDueDate }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Akad Document Upload -->
        <div class="px-8 pb-8 grid grid-cols-2 items-end gap-4">
            <a href="/docs/AkadMurabahah.docx" target="_blank"
                class="border border-gray-300 flex justify-between rounded-lg p-4">
                <div class="text-sm text-primary hover:underline">
                    Unduh Dokumen Akad Murabahah
                </div>
                <span class="icon-[tabler--download] text-green-500"></span>
            </a>
            <div class="flex flex-col gap-2">
                <BaseInputAdmin type="file" label="Upload Dokumen Akad Murabahah Tertandatangani"
                    v-model="form.akad_document_file" accept=".jpg,.jpeg,.png, application/pdf" required />
                <div class="flex justify-between text-xs text-gray-400">
                    <p>Format: JPG, JPEG, PNG, PDF</p>
                    <p>Max. 5 MB per file</p>
                </div>
            </div>
        </div>
    </section>
</template>
