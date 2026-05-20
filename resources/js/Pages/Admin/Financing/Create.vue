<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import { ref, computed } from 'vue'
import Button from '@/Components/Form/Button.vue'
import { useUserValidation } from '@/Composables/Validation/useUserValidation'
import { useFinancingForm } from '@/Composables/Form/useFinancingForm'
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
    { name: 'Pengelolaan Pembiayaan Murabahah', link: '/admin/financing' },
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
    selectedMember,
    isMemberSelected,
    searchSupplierQuery,
    supplierResults,
    isLoadingSearchSupplier,
    selectedSupplier,
    isSupplierSelected,
    filteredSuppliers,
    totalIncome,
    totalExpense,
    netIncome,
    selectMember,
    selectSupplier,
    addIncome,
    removeIncome,
    addExpense,
    removeExpense,
    addHeir,
    removeHeir,
    resetMemberSelection,
    resetSupplierSelection,
    submit,
} = useFinancingForm(props.financing)

const { errors } = useUserValidation(form)

const nextStep = () => {
    activeStep.value++
}

const prevStep = () => {
    activeStep.value--
}

const goToStep = (step) => {
    activeStep.value = step
}

const isStep1Valid = computed(() => isMemberSelected.value && form.member.heirs.length > 0)

const isStep2Valid = computed(() =>
    (form.documents?.income_slip || form.income_slip_file) && (form.documents?.bank_book || form.bank_book_file) && form.member.job_title && form.member.company_or_business_name && form.member.business_field && form.member.tenure_year && form.member.workplace_contact && form.member.workplace_address
)

const isStep3Valid = computed(() =>
    form.financing.name && form.collateral.collateral_type &&
    form.financing.status !== 'Menunggu Kelengkapan Dokumen' && form.financing.status !== 'Ditolak'
)

const isStep4Valid = computed(() => form.supplier.supplier_name && form.financing.cost_price && (form.purchase_receipt_file || form.documents.purchase_receipt))

const isRequestValid = computed(() => isStep1Valid.value && isStep2Valid.value && form.financing.name && form.collateral.collateral_type)

const isFinalizationValid = computed(() => form.financing.status === 'Disetujui' && form.financing.akad_date && (form.akad_document_file || form.documents.akad_document) && form.financing.payment_method)

</script>

<template>
    <AdminLayout title="Permohonan Pembiayaan Murabahah">
        <PageBreadcrumb page-title="Permohonan Pembiayaan Murabahah" :items="breadcrumbItems" />
        <div class="grid grid-cols-6 gap-6">
            <div class="card-layout justify-between flex flex-col col-span-4 px-0!">
                <PersonalData v-if="activeStep === 1" :form="form" :search-query="searchQuery"
                    :is-loading-search="isLoadingSearch" :is-member-selected="isMemberSelected"
                    :member-results="memberResults" :data="props.data" :errors="errors"
                    @update:search-query="searchQuery = $event" @selectMember="selectMember" @addHeir="addHeir"
                    @removeHeir="removeHeir" @resetMemberSelection="resetMemberSelection" />

                <FinancialData v-if="activeStep === 2" :form="form" :data="props.data" @addIncome="addIncome"
                    @removeIncome="removeIncome" @addExpense="addExpense" @removeExpense="removeExpense" />

                <FinancingObjectData v-if="activeStep === 3" :form="form" :data="props.data" />

                <ProcurementData v-if="activeStep === 4" :form="form" :search-supplier-query="searchSupplierQuery"
                    :is-loading-search-supplier="isLoadingSearchSupplier" :is-supplier-selected="isSupplierSelected"
                    :filtered-suppliers="filteredSuppliers" @update:search-supplier-query="searchSupplierQuery = $event"
                    @selectSupplier="selectSupplier" @resetSupplierSelection="resetSupplierSelection" />

                <Finalization v-if="activeStep === 5" :form="form" />

                <div :class="activeStep === 1 ? 'justify-end' : 'justify-between'" class="flex gap-4 p-4">
                    <Button v-if="activeStep > 1" @click="prevStep" variant="gray">
                        Kembali
                    </Button>
                    <div class="flex items-center gap-4 justify-end">
                        <Button v-if="activeStep < totalSteps" variant="light"
                            @click="submit()" :disabled="(activeStep === 1 && !isStep1Valid) ||
                            (activeStep === 2 && !isStep2Valid) ||
                            (activeStep === 3 && !isStep3Valid) ||
                            (activeStep === 4 && !isStep4Valid)
                            ">
                            Simpan Sementara
                        </Button>

                        <Button v-if="activeStep < totalSteps" @click="nextStep" variant="primary" :disabled="(activeStep === 1 && !isStep1Valid) ||
                            (activeStep === 2 && !isStep2Valid) ||
                            (activeStep === 3 && !isStep3Valid) ||
                            (activeStep === 4 && !isStep4Valid)
                            ">
                            Selanjutnya
                        </Button>

                        <Button :disabled="!isRequestValid" v-if="activeStep === 3 && (form.financing.status === 'Menunggu Kelengkapan Dokumen' || form.financing.status === 'Ditolak')" type="submit" @click="submit('PENDING_REVIEW')" variant="secondary">
                            Ajukan Permohonan
                        </Button>

                        <Button :disabled="!isFinalizationValid"  v-if="activeStep === 5" type="submit" @click="submit('FINAL')" variant="secondary">
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
