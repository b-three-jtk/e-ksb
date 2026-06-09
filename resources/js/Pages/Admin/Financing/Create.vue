<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import { ref, computed } from 'vue'
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

const activeStep = ref(1)
const totalSteps = 5

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengelolaan Pembiayaan Murabahah', link: '/admin/financings' },
    { name: 'Permohonan Pembiayaan Murabahah' },
]

const props = defineProps({
    data: Object,
    financing: Object,
})

const {
    form,
    searchQuery,
    memberResults,
    isLoadingSearch,
    isMemberSelected,
    searchSupplierQuery,
    supplierResults,
    isLoadingSearchSupplier,
    isSupplierSelected,
    selectMember,
    selectSupplier,
    addHeir,
    removeHeir,
    resetMemberSelection,
    resetSupplierSelection,
    submit,
    saveDraft,
    finalize,
} = useFinancingForm(props.financing)

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

const isStep1Valid = computed(() =>
    isMemberSelected.value &&
    form.member.heirs.length > 0 &&
    form.member.is_have_eligible_saving === true &&
    form.member.is_have_no_obligation === true
)

const isStep2Valid = computed(() =>
    (form.documents?.income_slip || form.income_slip_file) &&
    (form.documents?.bank_book || form.bank_book_file) &&
    form.member.job_title &&
    form.member.company_or_business_name &&
    form.member.business_field &&
    form.member.tenure_year &&
    form.member.workplace_contact &&
    form.member.workplace_address
)

const isStep3Valid = computed(() =>
    form.financing.name && form.collateral.collateral_type
)

// "Ajukan Permohonan" muncul di step 3 jika step 1–3 semua valid
// dan status belum di-approve/reject (tidak boleh re-submit)
const isRequestValid = computed(() =>
    isStep1Valid.value &&
    isStep2Valid.value &&
    isStep3Valid.value
    && form.financing.status === 'Menunggu Kelengkapan Dokumen'
)

const isFinalizationValid = computed(() =>
    form.financing.status === 'Disetujui' &&
    form.financing.akad_date &&
    (form.akad_document_file || form.documents?.akad_document) &&
    form.financing.payment_method
)

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
        <div class="grid grid-cols-6 gap-6">
            <div class="card-layout justify-between flex flex-col col-span-4 px-0!">

                <PersonalData
                    v-if="activeStep === 1"
                    :form="form"
                    :search-query="searchQuery"
                    :is-loading-search="isLoadingSearch"
                    :is-member-selected="isMemberSelected"
                    :member-results="memberResults"
                    :data="props.data"
                    :errors="errors"
                    @update:search-query="searchQuery = $event"
                    @selectMember="selectMember"
                    @addHeir="addHeir"
                    @removeHeir="removeHeir"
                    @resetMemberSelection="resetMemberSelection"
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
                    :search-supplier-query="searchSupplierQuery"
                    :is-loading-search-supplier="isLoadingSearchSupplier"
                    :is-supplier-selected="isSupplierSelected"
                    :supplier-results="supplierResults"
                    :errors="errors"
                    @update:search-supplier-query="searchSupplierQuery = $event"
                    @selectSupplier="selectSupplier"
                    @resetSupplierSelection="resetSupplierSelection"
                    @validate-field="(field) => validateField(field, 4)"
                />

                <Finalization
                    v-if="activeStep === 5"
                    :form="form"
                    :errors="errors"
                    @validate-field="(field) => validateField(field, 5)"
                />

                <div :class="activeStep === 1 ? 'justify-end' : 'justify-between'" class="flex gap-4 p-4">
                    <Button v-if="activeStep > 1" @click="prevStep" variant="gray">
                        Kembali
                    </Button>

                    <div class="flex items-center gap-4 justify-end">
                        <Button
                            v-if="activeStep === 3 && isRequestValid"
                            type="submit"
                            @click="handleSubmit()"
                            variant="secondary"
                        >
                            Ajukan Permohonan
                        </Button>

                        <Button
                            v-if="activeStep < totalSteps"
                            variant="light"
                            @click="handleSaveDraft()"
                        >
                            Simpan Sementara
                        </Button>

                        <Button
                            v-if="activeStep < totalSteps && !isRequestValid"
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
                            Finalisasi Pembiayaan
                        </Button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col w-full col-span-2 gap-6">
                <Stepper :active-step="activeStep" />
                <Documents :form="form" />
            </div>
        </div>
    </AdminLayout>
</template>
