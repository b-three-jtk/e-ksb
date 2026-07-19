<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'

const props = defineProps({
    form: Object,
    errors: Object,
})

const emit = defineEmits(['validate-field'])

const onFieldChange = (field) => emit('validate-field', field)
</script>

<template>
    <section class="flex flex-col gap-6">
        <div class="card-layout mx-4">
            <h1 class="card-title">Data Wakalah</h1>
            <p class="text-sm text-gray-500 mb-4">Wakalah bersifat opsional. Centang kotak di bawah ini jika pengadaan
                barang menggunakan skema Wakalah.</p>

            <!-- Wakalah toggle -->
            <div class="flex items-center gap-2">
                <input v-model="form.is_wakalah" type="checkbox" id="wakalah"
                    class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary-500" />
                <label for="wakalah" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Pengadaan dengan Skema Wakalah
                </label>
            </div>

            <!-- Wakalah section -->
            <div v-if="form.is_wakalah" class="grid grid-cols-2 items-end gap-6 mt-6">
                <BaseInputAdmin v-model="form.pembiayaan.akad_wakalah_date" required label="Tanggal Akad Wakalah"
                    :error="errors?.akad_wakalah_date" @input="onFieldChange('akad_wakalah_date')" type="date" />
                <a :href="form.pembiayaan.id && form.pembiayaan.akad_wakalah_date ? `/admin/pembiayaan/${form.pembiayaan.id}/wakalah/download?date=${form.pembiayaan.akad_wakalah_date}` : '#'"
                    target="_blank"
                    :class="[
                        'border flex justify-between rounded-lg p-4 transition-colors',
                        (!form.pembiayaan.akad_wakalah_date) ? 'border-gray-200 bg-gray-50 cursor-not-allowed pointer-events-none' : 'border-primary bg-primary/5 hover:bg-primary/10'
                    ]">
                    <div :class="['text-sm font-medium', (!form.pembiayaan.akad_wakalah_date) ? 'text-gray-400' : 'text-primary']">
                        Unduh Dokumen Akad Wakalah
                    </div>
                    <span :class="['icon-[tabler--download] text-xl', (!form.pembiayaan.akad_wakalah_date) ? 'text-gray-400' : 'text-primary']"></span>
                </a>

                <div class="col-span-2 grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <BaseInputAdmin type="file" label="Upload Dokumen Wakalah Tertandatangani"
                            v-model="form.akad_wakalah_file" accept="application/pdf" required :disabled="!form.pembiayaan.akad_wakalah_date"
                            :error="errors?.akad_wakalah_file" @change="onFieldChange('akad_wakalah_file')" />
                        <div class="grid grid-cols-2 text-xs text-gray-400 gap-1">
                            <p>Format: PDF</p>
                            <a v-if="form.documents?.akad_wakalah_document" :href="form.documents.akad_wakalah_document" target="_blank" class="text-primary hover:underline flex items-center gap-1 w-fit mt-2">
                                <span class="icon-[tabler--external-link]"></span>
                                Lihat Dokumen Saat Ini
                            </a>
                            <p>Max. 2 MB per file</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</template>
