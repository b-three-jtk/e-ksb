<script setup>
import Info from '@/Components/Form/Info.vue'
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'

defineProps({
    data: Object,
    form: Object,
})
</script>

<template>
    <div class="gap-6 flex flex-col">
        <div class="card-layout">
            <div class="grid grid-cols-2 gap-6 mt-8">
                <Info label="Jenis Jaminan" :value="data.collateral.collateral_type" />
                <Info label="Nilai Estimasi Jaminan (Rp)" :value="data.collateral.estimated_market_value" />
                <Info label="Atas Nama" :value="data.collateral.owner_name" />
                <Info label="Lokasi / Kondisi Jaminan" :value="data.collateral.collateral_location" />
            </div>
        </div>
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
                                <input
                                    type="radio"
                                    value="lengkap"
                                    v-model="form.collateral_document_status"
                                >
                                <span>Lengkap</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    value="tidak_lengkap"
                                    v-model="form.collateral_document_status"
                                >
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
                                <input
                                    type="radio"
                                    value="sesuai"
                                    v-model="form.suitability_status"
                                >
                                <span>Sesuai</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    value="tidak_sesuai"
                                    v-model="form.suitability_status"
                                >
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
                                <input
                                    type="radio"
                                    value="layak"
                                    v-model="form.income_feasibility_status"
                                >
                                <span>Layak</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    value="dipertimbangkan"
                                    v-model="form.income_feasibility_status"
                                >
                                <span>Dipertimbangkan</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    value="tidak_layak"
                                    v-model="form.income_feasibility_status"
                                >
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
                                <input
                                    type="radio"
                                    value="approved"
                                    v-model="form.final_decision_status"
                                >
                                <span>Disetujui</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    value="rejected"
                                    v-model="form.final_decision_status"
                                >
                                <span>Ditolak</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <BaseInputAdmin
                v-model="form.notes"
                label="Catatan Pemeriksaan"
                type="textarea"
                placeholder="Masukkan catatan pemeriksaan"
                rows="4"
            />
        </div>
    </div>
</template>
