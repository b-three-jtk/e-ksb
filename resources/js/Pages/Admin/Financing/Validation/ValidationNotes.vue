<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import moneyParser from '@/Composables/moneyParser'
import { computed, ref, watch } from 'vue'
import Info from '@/Components/Form/Info.vue'

const props = defineProps({
    data: Object,
    form: Object,
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

const tenor = ref(12)

const totalIncome = computed(() => {
    return incomes.value.reduce((total, item) => total + (Number(props.data.member[item.model]) || 0), 0)
})

const totalExpense = computed(() => {
    return expenses.value.reduce((total, item) => total + (Number(props.data.member[item.model]) || 0), 0)
})

const totalPrice = computed(() => {
    const costPrice = Number(props.data?.financing?.predicted_cost_price || 0)
    const marginPercentage = Number(props.data?.margin_percentage || 0)
    const margin = Math.round(costPrice * (marginPercentage / 100))
    return costPrice + margin
})

const monthlyInstallment = computed(() => {
    if (tenor.value <= 0) return 0
    return Math.round(totalPrice.value / tenor.value)
})

const monthlyIncome = computed(() => totalIncome.value - totalExpense.value)
const remainingIncome = computed(() => monthlyIncome.value - monthlyInstallment.value)

watch([monthlyInstallment, monthlyIncome], () => {
    props.form.monthly_installment = monthlyInstallment.value
    props.form.monthly_income = monthlyIncome.value
}, { immediate: true })

watch([
    () => props.form.collateral_document_status,
    () => props.form.suitability_status,
    () => props.form.income_feasibility_status,
], () => {
    const doc = props.form.collateral_document_status
    const suit = props.form.suitability_status
    const income = props.form.income_feasibility_status

    // Kalau ada satu saja yang "gagal"= Ditolak
    if (suit === 'tidak_sesuai' || income === 'tidak_layak') {
        props.form.final_decision_status = 'Ditolak'
        return
    }

    // Semua oke tapi dokumen tidak lengkap, atau penghasilan dipertimbangkan → Disetujui dengan Catatan
    if (doc === 'tidak_lengkap' || income === 'dipertimbangkan') {
        props.form.final_decision_status = 'Disetujui dengan Catatan'
        return
    }

    // Semua oke= Disetujui
    props.form.final_decision_status = 'Disetujui'
}, { immediate: true })
</script>

<template>
    <div class="gap-6 flex flex-col">
        <div class="card-layout">
            <h1 class="card-title">Data Jaminan</h1>
            <div class="grid grid-cols-2 gap-6 mt-8">
                <Info label="Jenis Jaminan" :value="data.collateral.collateral_type" />
                <Info label="Nilai Estimasi Jaminan (Rp)"
                    :value="moneyParser(data.collateral.estimated_market_value)" />
                <Info label="Atas Nama" :value="data.collateral.owner_name" />
                <Info label="Lokasi / Kondisi Jaminan" :value="data.collateral.collateral_location" />
            </div>
        </div>
        <section class="card-layout">
            <h1 class="card-title text-lg!">Simulasi Cicilan</h1>
            <div class="bg-white dark:bg-gray-800 rounded-2xl mt-4">
                <!-- Tenor Slider -->
                <div class="mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Jangka Waktu Cicilan</label>
                        <span class="text-lg font-semibold text-primary dark:text-secondary">{{ tenor }} Bulan</span>
                    </div>
                    <input v-model.number="tenor" type="range" min="3" max="60" step="1"
                        class="w-full h-2 rounded-lg appearance-none cursor-pointer" :style="{
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
                        <p class="text-gray-500 dark:text-gray-300 mb-2">Jumlah Pembiayaan</p>
                        <p class="text-lg font-semibold text-dark-text dark:text-gray-200">{{ moneyParser(totalPrice) }}</p>
                    </div>

                    <!-- Perkiraan Cicilan -->
                    <div>
                        <p class="text-gray-500 dark:text-gray-300 mb-2">Perkiraan Cicilan</p>
                        <p class="text-lg font-semibold text-dark-text dark:text-gray-200">{{ moneyParser(monthlyInstallment)
                        }}<span class="text-sm text-gray-500 dark:text-gray-300">/bulan</span></p>
                    </div>
                </div>

                <!-- Sisa Penghasilan -->
                <div class="mt-6 pt-6 border-t">
                    <p class="text-gray-500 mb-2 dark:text-gray-300">Sisa Penghasilan Bulanan (setelah cicilan)</p>
                    <p class="text-lg font-semibold" :class="remainingIncome >= 0 ? 'text-secondary' : 'text-red-600'">
                        {{ moneyParser(remainingIncome) }}
                    </p>
                </div>
            </div>
        </section>
        <div class="card-layout">
            <h1 class="card-title">Checklist Penilaian</h1>
            <table class="w-full text-sm text-center my-4 text-gray-500 dark:text-gray-400">
                <thead class="text-gray-400 border-y dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="py-4 text-left pl-6">Aspek yang Dinilai</th>
                        <th class="py-4 text-right pl-6">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dokumen Jaminan -->
                    <tr class="bg-white border-b text-dark-text dark:text-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">
                            <div class="flex items-center gap-4">
                                <span>Dokumen Jaminan</span>
                            </div>
                        </td>
                        <td class="py-4 flex gap-4 justify-end pl-6">
                            <div class="flex items-center gap-2">
                                <input type="radio" value="lengkap" class="accent-primary"
                                    v-model="form.collateral_document_status">
                                <span>Lengkap</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="radio" value="tidak_lengkap" class="accent-primary"
                                    v-model="form.collateral_document_status">
                                <span>Tidak Lengkap</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Kesesuaian -->
                    <tr class="bg-white border-b text-dark-text dark:text-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">
                            <div class="flex items-center gap-4">
                                <span>Kesesuaian dengan Kebutuhan</span>
                            </div>
                        </td>
                        <td class="py-4 flex gap-4 justify-end pl-6">
                            <div class="flex items-center gap-2">
                                <input type="radio" value="sesuai" class="accent-primary"
                                    v-model="form.suitability_status">
                                <span>Sesuai</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="radio" value="tidak_sesuai" class="accent-primary"
                                    v-model="form.suitability_status">
                                <span>Tidak Sesuai</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Kelayakan Penghasilan -->
                    <tr class="bg-white border-b text-dark-text dark:text-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">
                            <div class="flex items-center gap-4">
                                <span>Kelayakan Penghasilan</span>
                            </div>
                        </td>
                        <td class="py-4 flex gap-4 justify-end pl-6">
                            <div class="flex items-center gap-2">
                                <input type="radio" value="layak" class="accent-primary"
                                    v-model="form.income_feasibility_status">
                                <span>Layak</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="radio" value="dipertimbangkan" class="accent-primary"
                                    v-model="form.income_feasibility_status">
                                <span>Dipertimbangkan</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="radio" value="tidak_layak" class="accent-primary"
                                    v-model="form.income_feasibility_status">
                                <span>Tidak Layak</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="form.collateral_document_status && form.final_decision_status && form.suitability_status && form.income_feasibility_status"
                class="flex justify-between items-center border rounded-2xl px-6 py-4 mb-4" :class="{
                    'text-primary bg-light-bg': form.final_decision_status === 'Disetujui',
                    'text-red-700 bg-red-100': form.final_decision_status === 'Ditolak',
                    'text-yellow-600 bg-yellow-100': form.final_decision_status === 'Disetujui dengan Catatan',
                }">
                <p class="text-lg font-semibold">Keputusan Akhir Verifikasi</p>
                <p class="text-lg font-body font-semibold">
                    {{ form.final_decision_status }}
                </p>
            </div>

            <BaseInputAdmin v-if="form.final_decision_status !== 'Disetujui'" v-model="form.notes" label="Catatan Pemeriksaan" type="textarea"
                placeholder="Masukkan catatan pemeriksaan" rows="4" />
        </div>
    </div>
</template>
