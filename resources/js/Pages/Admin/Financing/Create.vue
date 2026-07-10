<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import { ref, computed, onMounted } from 'vue'
import Button from '@/Components/Form/Button.vue'
import { useFinancingForm } from '@/Composables/Form/useFinancingForm'
import { useFinancingValidation } from '@/Composables/Validation/useFinancingValidation'
import PersonalData from './Create/PersonalData.vue'
import FinancialData from './Create/FinancialData.vue'
import FinancingObjectData from './Create/FinancingObjectData.vue'
import ProcurementData from './Create/ProcurementData.vue'
import Finalization from './Create/Finalization.vue'
import Stepper from './Create/Stepper.vue'
import Documents from './Create/Documents.vue'
import { useInputSanitizers } from '@/Composables/useInputSanitizers'

const { onlyLetters, onlyNumbers } = useInputSanitizers()

const activeStep = ref(1)
const totalSteps = 5

onMounted(() => {
    if (props.pembiayaan && form.pembiayaan.status) {
        if (form.pembiayaan.status === 'Disetujui') {
            activeStep.value = 4
        } else if (['Disetujui dengan Catatan', 'Menunggu Kelengkapan Dokumen', 'Ditolak'].includes(form.pembiayaan.status)) {
            activeStep.value = 3
        }
    }
})

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengelolaan Pembiayaan Murabahah', link: '/admin/pembiayaan' },
    { name: 'Permohonan Pembiayaan Murabahah' },
]

const props = defineProps({
    data: Object,
    pembiayaan: Object,
})

const {
    form,
    searchQuery,
    anggotaResults,
    isLoadingSearch,
    isAnggotaSelected,
    searchPemasokQuery,
    pemasokResults,
    isLoadingSearchPemasok,
    isPemasokSelected,
    selectAnggota,
    selectPemasok,
    addAhliWaris,
    removeAhliWaris,
    resetAnggotaSelection,
    resetPemasokSelection,
    submit,
    saveDraft,
    finalize,
} = useFinancingForm(props.pembiayaan)

const {
    errors,
    validateAndShowErrors,
    validateField,
} = useFinancingValidation(form)

const nextStep = () => {
    const valid = validateAndShowErrors(activeStep.value)
    if (!valid) return
    activeStep.value++
}

const prevStep = () => {
    activeStep.value--
}

const isStep3Valid = computed(() =>
    form.pembiayaan.nama_barang && !form.processing
)

const isFinalizationValid = computed(() =>
    form.pembiayaan.status === 'Disetujui' &&
    form.pembiayaan.tgl_akad &&
    (form.akad_document_file || form.documents?.akad_document) &&
    form.pembiayaan.metode_pembayaran
)

const draftStatuses = [
    'Menunggu Kelengkapan Dokumen',
    'Ditolak',
    'Disetujui dengan Catatan'
]

const showSubmitButton = computed(() => {
    return activeStep.value === 3 && draftStatuses.includes(form.pembiayaan.status)
})

const showNextButton = computed(() => {
    if (activeStep.value >= totalSteps || activeStep.value === 5) {
        return false
    }

    if (activeStep.value === 3 && draftStatuses.includes(form.pembiayaan.status)) {
        return false
    }

    if (activeStep.value === 4 && form.is_wakalah && form.wakala) {
        return false
    }

    return true
})

const handleSubmit = () => {
    const s1 = validateAndShowErrors(1)
    if (!s1) { activeStep.value = 1; return }
    const s2 = validateAndShowErrors(2)
    if (!s2) { activeStep.value = 2; return }
    const s3 = validateAndShowErrors(3)
    if (!s3) { activeStep.value = 3; return }
    submit()
}

const handleFinalize = () => {
    const valid = validateAndShowErrors(5)
    if (!valid) return
    finalize()
}

const handleSaveDraft = () => {
    saveDraft()
}
</script>

<template>
    <AdminLayout title="Permohonan Pembiayaan Murabahah">
        <PageBreadcrumb page-title="Permohonan Pembiayaan Murabahah" :items="breadcrumbItems" />
        <div class="grid grid-cols-1 lg:grid-cols-6 gap-4">
            <div class="card-layout justify-between flex flex-col col-span-4 px-0!">

                <PersonalData
                    v-if="activeStep === 1"
                    :form="form"
                    :search-query="searchQuery"
                    :is-loading-search="isLoadingSearch"
                    :is-anggota-selected="isAnggotaSelected"
                    :anggota-results="anggotaResults"
                    :data="props.data"
                    :only-letters="onlyLetters"
                    :only-numbers="onlyNumbers"
                    :errors="errors"
                    @update:search-query="searchQuery = $event"
                    @selectAnggota="selectAnggota"
                    @addAhliWaris="addAhliWaris"
                    @removeAhliWaris="removeAhliWaris"
                    @resetAnggotaSelection="resetAnggotaSelection"
                    @validate-field="(field) => validateField(field, 1)"
                />

                <FinancialData
                    v-if="activeStep === 2"
                    :form="form"
                    :data="props.data"
                    :errors="errors"
                    @validate-field="(field) => validateField(field, 2)"
                />

                <FinancingObjectData
                    v-if="activeStep === 3"
                    :form="form"
                    :data="props.data"
                    :errors="errors"
                    @validate-field="(field) => validateField(field, 3)"
                />

                <ProcurementData
                    v-if="activeStep === 4"
                    :form="form"
                    :data="props.data"
                    :search-pemasok-query="searchPemasokQuery"
                    :is-loading-search-pemasok="isLoadingSearchPemasok"
                    :is-pemasok-selected="isPemasokSelected"
                    :pemasok-results="pemasokResults"
                    :errors="errors"
                    @update:search-pemasok-query="searchPemasokQuery = $event"
                    @selectPemasok="selectPemasok"
                    @resetPemasokSelection="resetPemasokSelection"
                    @validate-field="(field) => validateField(field, 4)"
                />

                <Finalization
                    v-if="activeStep === 5"
                    :form="form"
                    :data="props.data"
                    :errors="errors"
                    @validate-field="(field) => validateField(field, 5)"
                />

                <div :class="activeStep === 1 ? 'justify-end' : 'justify-between'" class="flex gap-4 p-4">
                    <Button v-if="activeStep > 1" @click="prevStep" variant="gray">
                        Kembali
                    </Button>

                    <div class="flex items-center gap-4 justify-end">

                        <Button
                            v-if="activeStep >= 3"
                            variant="light"
                            @click="handleSaveDraft()"
                        >
                            Simpan Sementara
                        </Button>

                        <Button
                            v-if="showSubmitButton"
                            :disabled="!isStep3Valid"
                            type="submit"
                            @click="handleSubmit()"
                            variant="secondary"
                        >
                            <div v-if="form.processing" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" />
                            Ajukan Permohonan
                        </Button>

                        <Button
                            v-if="showNextButton"
                            @click="nextStep"
                            variant="primary"
                        >
                            Selanjutnya
                        </Button>
                        <Button
                            v-if="activeStep === 5"
                            :disabled="!isFinalizationValid"
                            type="submit"
                            @click="handleFinalize()"
                            variant="secondary"
                        >
                            <div v-if="form.processing" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" />
                            Finalisasi Pembiayaan
                        </Button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col w-full col-span-2 gap-4">
                <Stepper :active-step="activeStep" />
                <Documents :form="form" />
            </div>
        </div>
    </AdminLayout>
</template>
