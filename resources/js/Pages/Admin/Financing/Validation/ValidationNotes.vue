<script setup>
import Info from '@/Components/Form/Info.vue'
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import moneyParser from '@/Composables/moneyParser'
import { computed, ref, watch } from 'vue'

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

const totalPrice = ref(0)
const monthlyInstallment = computed(() => {
  if (tenor.value <= 0) return 0
  const costPrice = Number(props.data.financing.predicted_cost_price)
  const margin = Math.round(costPrice * 0.08)
  totalPrice.value = costPrice + margin
  return Math.round(totalPrice.value / tenor.value)
})
const monthlyIncome = computed(() => totalIncome.value - totalExpense.value)
const remainingIncome = computed(() => monthlyIncome.value - monthlyInstallment.value)

watch([tenor, totalPrice, monthlyIncome], () => {
  props.form.monthly_installment = monthlyInstallment.value
  props.form.monthly_income = monthlyIncome.value
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
            <div class="bg-white rounded-2xl mt-4">
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
                        <p class="text-lg font-semibold text-dark-text">{{ moneyParser(totalPrice) }}</p>
                    </div>

                    <!-- Perkiraan Cicilan -->
                    <div>
                        <p class="text-xs text-gray-500 mb-2">Perkiraan Cicilan</p>
                        <p class="text-lg font-semibold text-dark-text">{{ moneyParser(monthlyInstallment)
                        }}<span class="text-sm text-gray-500">/bulan</span></p>
                    </div>
                </div>

                <!-- Sisa Penghasilan -->
                <div class="mt-6 pt-6 border-t">
                    <p class="text-xs text-gray-500 mb-2">Sisa Penghasilan Bulanan (setelah cicilan)</p>
                    <p class="text-lg font-semibold" :class="remainingIncome >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ moneyParser(remainingIncome) }}
                    </p>
                </div>
            </div>
        </section>
        <div class="card-layout">
            <h1 class="card-title">Checklist Penilaian</h1>
            <table class="w-full text-sm text-center my-8 text-gray-500 dark:text-gray-400">
                <thead class="text-gray-400 border-y dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="py-4 text-left pl-6">Aspek yang Dinilai</th>
                        <th class="py-4 text-right pl-6">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dokumen Jaminan -->
                    <tr class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">
                            <div class="flex items-center gap-4">
                                <span>Dokumen Jaminan</span>
                            </div>
                        </td>
                        <td class="py-4 flex gap-4 justify-end pl-6">
                            <div class="flex items-center gap-2">
                                <input type="radio" value="lengkap" class="accent-primary" v-model="form.collateral_document_status">
                                <span>Lengkap</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="radio" value="tidak_lengkap" class="accent-primary" v-model="form.collateral_document_status">
                                <span>Tidak Lengkap</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Kesesuaian -->
                    <tr class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">
                            <div class="flex items-center gap-4">
                                <span>Kesesuaian dengan Kebutuhan</span>
                            </div>
                        </td>
                        <td class="py-4 flex gap-4 justify-end pl-6">
                            <div class="flex items-center gap-2">
                                <input type="radio" value="sesuai" class="accent-primary" v-model="form.suitability_status">
                                <span>Sesuai</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="radio" value="tidak_sesuai" class="accent-primary" v-model="form.suitability_status">
                                <span>Tidak Sesuai</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Kelayakan Penghasilan -->
                    <tr class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">
                            <div class="flex items-center gap-4">
                                <span>Kelayakan Penghasilan</span>
                            </div>
                        </td>
                        <td class="py-4 flex gap-4 justify-end pl-6">
                            <div class="flex items-center gap-2">
                                <input type="radio" value="layak" class="accent-primary" v-model="form.income_feasibility_status">
                                <span>Layak</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="radio" value="dipertimbangkan" class="accent-primary" v-model="form.income_feasibility_status">
                                <span>Dipertimbangkan</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="radio" value="tidak_layak" class="accent-primary" v-model="form.income_feasibility_status">
                                <span>Tidak Layak</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Keputusan Akhir -->
                    <tr class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-4 text-left pl-6">
                            <div class="flex items-center gap-4">
                                <span>Keputusan Akhir Permohonan</span>
                            </div>
                        </td>
                        <td class="py-4 flex gap-4 justify-end pl-6">
                            <div class="flex items-center gap-2">
                                <input type="radio" value="approved" class="accent-primary" v-model="form.final_decision_status">
                                <span>Disetujui</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="radio" value="rejected" class="accent-primary" v-model="form.final_decision_status">
                                <span>Ditolak</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <BaseInputAdmin v-model="form.notes" label="Catatan Pemeriksaan" type="textarea"
                placeholder="Masukkan catatan pemeriksaan" rows="4" />
        </div>
    </div>
</template>
