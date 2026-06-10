<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import { ref, computed } from 'vue'
import Button from '@/Components/Form/Button.vue'
import Swal from 'sweetalert2'
import { useForm } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import moneyParser from '@/Composables/moneyParser'

import Stepper from './Validation/Stepper.vue'
import Documents from './Validation/Documents.vue'
import useFinancingStatus, { getStatusLabel } from '@/Composables/useFinancingStatus'
import Info from '@/Components/Form/Info.vue'
import PersonalData from './Validation/PersonalData.vue'
import FinancialData from './Validation/FinancialData.vue'
import ValidationNotes from './Validation/ValidationNotes.vue'

const activeStep = ref(1)
const totalSteps = 3

const form = useForm({
    notes: '',
    collateral_document_status: '',
    suitability_status: '',
    income_feasibility_status: '',
    final_decision_status: '',
    monthly_installment: 0,
    monthly_income: 0,
})

const isValidationComplete = computed(() => {
    return (
        form.collateral_document_status &&
        form.suitability_status &&
        form.income_feasibility_status &&
        form.final_decision_status
    )
})

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengelolaan Pembiayaan Murabahah', link: '/admin/financings' },
    { name: 'Validasi Permohonan Pembiayaan Murabahah' },
]

const props = defineProps({
    data: Object,
})

const nextStep = () => {
    activeStep.value++
}

const prevStep = () => {
    activeStep.value--
}

const submit = () => {
    if (!isValidationComplete.value) {
        toast('Lengkapi semua penilaian terlebih dahulu!', {
            type: 'error',
            position: 'bottom-right',
        })
        return
    }

    const statusMapping = {
        'approved': 'Disetujui',
        'rejected': 'Ditolak',
    }

    const finalStatus = statusMapping[form.final_decision_status]

    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin menyimpan validasi permohonan ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, simpan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#007943',
    }).then((result) => {
        if (result.isConfirmed) {
            const submitForm = useForm({
                status: finalStatus,
                notes: form.notes,
            })

            submitForm.put(`/admin/financings/validate/${props.data.financing.id}`, {
                onSuccess: (page) => {
                    if (page.props.flash?.success) {
                        toast(page.props.flash.success, {
                            type: 'success',
                            position: 'bottom-right',
                        })
                    }
                },
                onError: (errors) => {
                    const errorMessages = Object.values(errors).flat()

                    if (errorMessages.length > 0) {
                        toast(errorMessages.join(', '), {
                            type: 'error',
                            position: 'bottom-right',
                        })
                    } else {
                        toast('Gagal menyimpan validasi permohonan', {
                            type: 'error',
                            position: 'bottom-right',
                        })
                    }
                }
            })
        }
    })
}
</script>

<template>
    <AdminLayout title="Validasi Permohonan Pembiayaan Murabahah">
        <PageBreadcrumb page-title="Validasi Permohonan Pembiayaan Murabahah" :items="breadcrumbItems" />
        <div class="grid grid-cols-6 gap-6">
            <div class="bg-secondary flex flex-col col-span-6 rounded-2xl">
                <div class="px-10 py-6 flex justify-between items-center">
                    <h1 class="font-semibold text-white">{{ data.financing.name }}</h1>
                    <div class="flex items-center gap-4">
                        <p class="text-white">No. Transaksi : #{{ data.financing.financing_transaction_code }}</p>
                        <span :class="useFinancingStatus(data.financing.status)">
                            {{ getStatusLabel(data.financing.status) }}
                        </span>
                    </div>
                </div>
                <div class="bg-white grid grid-cols-3 gap-6 p-6 rounded-2xl">
                    <Info label="Spesifikasi Produk" :value="data.financing.specification" />
                    <Info label="Kategori Produk" :value="data.financing.product_type" />
                    <Info label="Kondisi" :value="data.financing.condition" />
                    <Info label="Kuantitas" :value="data.financing.qty" />
                    <Info label="Harga Perkiraan" :value="moneyParser(data.financing.predicted_cost_price)" />
                </div>
            </div>
            <div class="justify-between card-layout flex flex-col col-span-4">
                <PersonalData v-if="activeStep === 1" :data="data" />
                <FinancialData v-if="activeStep === 2" :data="data" />
                <ValidationNotes v-if="activeStep === 3" :data="data" :form="form" />
                <div :class="activeStep === 1 ? 'justify-end' : 'justify-between'" class="flex gap-4 p-4">
                    <Button v-if="activeStep > 1" @click="prevStep" variant="gray">
                        Kembali
                    </Button>
                    <div class="flex items-center gap-4 justify-end">
                        <Button v-if="activeStep !== totalSteps && activeStep !== 3" @click="nextStep"
                            variant="primary">
                            Selanjutnya
                        </Button>
                        <Button
                            v-else-if="activeStep === totalSteps"
                            type="submit"
                            @click="submit"
                            variant="secondary"
                            :disabled="!isValidationComplete"
                        >
                            Validasi
                        </Button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col w-full col-span-2 gap-6">
                <Stepper :active-step="activeStep" />
                <Documents :form="data" />
            </div>
        </div>
    </AdminLayout>
</template>
