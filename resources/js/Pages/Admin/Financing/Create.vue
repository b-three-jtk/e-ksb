<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import { ref } from 'vue'
import Button from '@/Components/Form/Button.vue'
import { useUserValidation } from '@/Composables/Validation/useUserValidation'
import { useFinancingForm } from '@/Composables/Form/useFinancingForm'
import PersonalData from './Create/PersonalData.vue'

import FinancialData from './Create/FinancialData.vue'
import FinancingObjectData from './Create/FinancingObjectData.vue'
import ProcurementData from './Create/ProcurementData.vue'
import FinalizationData from './Create/Finalization.vue'
import Stepper from './Create/Stepper.vue'

const activeStep = ref(1)
const totalSteps = 5

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengelolaan Pembiayaan Murabahah', link: '/admin/financing/list' },
    { name: 'Permohonan Pembiayaan Murabahah' },
]

const props = defineProps({
    educations: Array,
    marriageStatuses: Array,
    income_types: Array,
    expense_types: Array,
    relationships: Array,
    conditions: Array,
})

const {
    form,
    searchQuery,
    memberResults,
    isLoadingSearch,
    selectedMember,
    isMemberSelected,
    filteredMembers,
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
    submitDraft,
} = useFinancingForm()

const { errors } = useUserValidation(form)

const nextStep = () => {
    activeStep.value++
}

const prevStep = () => {
    activeStep.value--
}
</script>

<template>
    <AdminLayout title="Permohonan Pembiayaan Murabahah">
        <PageBreadcrumb page-title="Permohonan Pembiayaan Murabahah" :items="breadcrumbItems" />
        <div class="grid grid-cols-6 gap-6">
            <div class="card-layout col-span-4 px-0!">
                <PersonalData v-if="activeStep === 1" :form="form" :search-query="searchQuery"
                    :is-loading-search="isLoadingSearch" :is-member-selected="isMemberSelected"
                    :filtered-members="filteredMembers" :educations="educations" :relationships="relationships"
                    :errors="errors" @update:search-query="searchQuery = $event" @selectMember="selectMember"
                    @addHeir="addHeir" @removeHeir="removeHeir" @resetMemberSelection="resetMemberSelection" />

                <FinancialData v-if="activeStep === 2" :form="form" :marriage-statuses="marriageStatuses"
                    :income_types="income_types" :expense_types="expense_types" :total-income="totalIncome"
                    :total-expense="totalExpense" :net-income="netIncome" @addIncome="addIncome"
                    @removeIncome="removeIncome" @addExpense="addExpense" @removeExpense="removeExpense" />

                <FinancingObjectData v-if="activeStep === 3" :form="form" :conditions="conditions" />

                <ProcurementData v-if="activeStep === 4" :form="form" :search-supplier-query="searchSupplierQuery"
                    :is-loading-search-supplier="isLoadingSearchSupplier" :is-supplier-selected="isSupplierSelected"
                    :filtered-suppliers="filteredSuppliers" @update:search-supplier-query="searchSupplierQuery = $event"
                    @selectSupplier="selectSupplier" />

                <FinalizationData v-if="activeStep === 5" :form="form" />

                <div :class="activeStep === 1 ? 'justify-end' : 'justify-between' " class="flex gap-4 p-4">
                    <Button v-if="activeStep > 1" @click="prevStep" variant="gray">
                        Kembali
                    </Button>
                    <div class="flex items-center gap-4 justify-end">
                        <Button v-if="activeStep > 2" variant="light" @click="submitDraft">
                            Simpan Sementara
                        </Button>
                        <Button v-if="activeStep < totalSteps && activeStep !== 5" @click="nextStep" variant="primary"
                            :disabled="(activeStep === 1 && !isMemberSelected) || (activeStep === 2 && form.member.incomes.length === 0) || (activeStep === 2 && form.member.expenses.length === 0) || (activeStep === 3 && !form.financing_object.object_name) || (activeStep === 4 && !form.financing.cost_price)">
                            Selanjutnya
                        </Button>
                        <Button v-else-if="activeStep === totalSteps" type="submit" variant="secondary">Ajukan Permohonan</Button>
                    </div>
                </div>
            </div>

            <Stepper :active-step="activeStep" />
        </div>
    </AdminLayout>
</template>
