<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { toast } from 'vue3-toastify'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import { formatCurrency } from '../../../utils/currency'
import FormPoin from './FormPoin.vue'
import FormSimpanan from './FormSimpanan.vue'
import FormPembiayaan from './FormPembiayaan.vue'
import BaseTable from '@/Components/Table/BaseTable.vue'
import Swal from 'sweetalert2'

const props = defineProps({
    settings: Object,
    settingsHistory: Object,
})

const tabs = [
    { key: 'points', label: 'Poin' },
    { key: 'savings', label: 'Simpanan' },
    { key: 'financing', label: 'Pembiayaan Murabahah' },
]

const activeTab = ref('points')
const processingSection = ref(null)

const forms = reactive({
    points: {
        saving_point_amount: '',
        saving_point_reward: '',
        effective_date: '',
    },
    savings: {
        saving_pokok_amount: '',
        saving_pokok_effective_date: '',
        saving_wajib_amount: '',
        saving_wajib_effective_date: '',
    },
    financing: {
        murabahah_margin_percentage: '',
        effective_date: '',
    },
})

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Pengaturan Umum' },
]

const getSetting = (section, key) => props.settings?.[section]?.[key] ?? {}

const formatDate = (value) => {
    if (!value) return '-'

    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(date)
}

const formatInteger = (value) => {
    if (value === null || value === undefined || value === '') return '-'

    const numberValue = Number(value)
    if (Number.isNaN(numberValue)) return '-'

    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(numberValue)
}

const formatMoney = (value) => {
    if (value === null || value === undefined || value === '') return '-'

    const numberValue = Number(value)
    if (Number.isNaN(numberValue)) return '-'

    return formatCurrency(numberValue)
}

const formatHistoryValue = (value, key) => {
    if (key === 'murabahah_margin_percentage') {
        return `${formatInteger(value)} %`
    }

    if (['saving_point_amount', 'saving_pokok_amount', 'saving_wajib_amount'].includes(key)) {
        return formatMoney(value)
    }

    if (key === 'saving_point_reward') {
        return formatInteger(value)
    }

    return value ?? '-'
}


const sectionTitle = computed(() => {
    if (activeTab.value === 'points') return 'Penetapan Besaran Poin'
    if (activeTab.value === 'savings') return 'Penetapan Besaran Simpanan'

    return 'Penetapan Persentase Margin Pembiayaan'
})

const summaryGridClass = computed(() => {
    if (activeTab.value === 'points') return 'grid grid-cols-1 gap-4 md:grid-cols-4'
    if (activeTab.value === 'savings') return 'grid grid-cols-1 gap-4 md:grid-cols-2'

    return 'grid grid-cols-1 gap-4 md:grid-cols-3'
})

const summaryCards = computed(() => {
    if (activeTab.value === 'points') {
        const pointsAmount = getSetting('points', 'saving_point_amount')
        const pointReward = getSetting('points', 'saving_point_reward')

        return [
            {
                value: formatMoney(pointsAmount.value),
                label: 'Jumlah Simpanan',
            },
            {
                value: formatInteger(pointReward.value),
                label: 'Poin Diperoleh',
            },
            {
                value: formatDate(pointsAmount.effective_date || pointReward.effective_date),
                label: 'Berlaku Sejak',
            },
            {
                value: formatDate(pointsAmount.updated_at || pointReward.updated_at),
                label: 'Terakhir Diperbarui',
            },
        ]
    }

    if (activeTab.value === 'savings') {
    const savingsPokok = getSetting('savings', 'saving_pokok_amount')
    const savingsWajib = getSetting('savings', 'saving_wajib_amount')
        return [
            {
                value: 'Simpanan Pokok',
                label: '',
                details: [
                    { label: 'Besaran', value: formatMoney(savingsPokok.value) },
                    { label: 'Berlaku Sejak', value: formatDate(savingsPokok.effective_date) },
                    { label: 'Dibayarkan', value: 'Saat Berhasil Mendaftar' },
                    { label: 'Tanggal Diperbarui', value: formatDate(savingsPokok.updated_at) },
                ],
            },
            {
                value: 'Simpanan Wajib',
                label: '',
                details: [
                    { label: 'Besaran', value: formatMoney(savingsWajib.value) },
                    { label: 'Berlaku Sejak', value: formatDate(savingsWajib.effective_date) },
                    { label: 'Dibayarkan', value: 'Setiap Bulan' },
                    { label: 'Tanggal Diperbarui', value: formatDate(savingsWajib.updated_at) },
                ],
            },
        ]
    }

    const financing = getSetting('financing', 'murabahah_margin_percentage')

    return [
        {
            value: `${formatInteger(financing.value)} %`,
            label: 'Besaran Margin Pembiayaan',
        },
        {
            value: formatDate(financing.effective_date),
            label: 'Berlaku Sejak',
        },
        {
            value: formatDate(financing.updated_at),
            label: 'Terakhir Diperbarui',
        },
    ]
})

const syncForms = () => {
    forms.points.saving_point_amount = props.settings?.points?.saving_point_amount?.value ?? ''
    forms.points.saving_point_reward = props.settings?.points?.saving_point_reward?.value ?? ''
    forms.points.effective_date = props.settings?.points?.saving_point_amount?.effective_date
        ?? props.settings?.points?.saving_point_reward?.effective_date
        ?? ''

    forms.savings.saving_pokok_amount = props.settings?.savings?.saving_pokok_amount?.value ?? ''
    forms.savings.saving_pokok_effective_date = props.settings?.savings?.saving_pokok_amount?.effective_date ?? ''
    forms.savings.saving_wajib_amount = props.settings?.savings?.saving_wajib_amount?.value ?? ''
    forms.savings.saving_wajib_effective_date = props.settings?.savings?.saving_wajib_amount?.effective_date ?? ''

    forms.financing.murabahah_margin_percentage = props.settings?.financing?.murabahah_margin_percentage?.value ?? ''
    forms.financing.effective_date = props.settings?.financing?.murabahah_margin_percentage?.effective_date ?? ''
}

const historyItems = computed(() => props.settingsHistory?.[activeTab.value] ?? [])

watch(() => props.settings, syncForms, { immediate: true, deep: true })

const submitSection = (section) => {
    processingSection.value = section

    router.post('/admin/settings', {
        section,
        ...forms[section],
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Pengaturan berhasil disimpan.', {
                position: 'bottom-right',
                transition: 'slide',
                autoClose: 5000,
            })
        },
        onFinish: () => {
            processingSection.value = null
        },
    })
}

const historyColumns = [
    {
        key: 'label',
        label: 'Pengaturan',
    },
    {
        key: 'value',
        label: 'Nilai',
    },
    {
        key: 'effective_date',
        label: 'Tanggal Berlaku',
    },
    {
        key: 'updated_by',
        label: 'Diubah Oleh',
    },
    {
        key: 'updated_at',
        label: 'Diubah Pada',
    },
]

const submitAlert = async (section) => {
    const result = await Swal.fire({
        title: 'Simpan Pengaturan?',
        text: activeTab.value === 'points'
            ? 'Perubahan konfigurasi poin akan disimpan.'
            : activeTab.value === 'savings'
                ? 'Perubahan konfigurasi simpanan akan disimpan.'
                : 'Perubahan konfigurasi margin pembiayaan akan disimpan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        confirmButtonColor: '#007943',
        cancelButtonColor: '#d33',
    })

    if (result.isConfirmed) {
        submitSection(section)
    }
}

const isProcessing = (section) => processingSection.value === section
</script>

<template>
    <AdminLayout title="Pengaturan Umum">
        <PageBreadcrumb :page-title="'Pengaturan Umum'" :items="breadcrumbItems" />

        <div class="mt-10 space-y-8">
            <div :class="summaryGridClass">
                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-[0_8px_24px_rgba(15,23,42,0.04)]"
                >
                    <div class="text-2xl font-semibold tracking-tight text-slate-800">
                        {{ card.value }}
                    </div>
                    <div class="mt-1 text-sm text-slate-500">
                        {{ card.label }}
                    </div>

                    <div v-if="card.details" class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                        <div v-for="detail in card.details" :key="detail.label" class="space-y-1">
                            <div class="text-slate-400">
                                {{ detail.label }}
                            </div>
                            <div class="font-semibold text-slate-700">
                                {{ detail.value }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative pt-4">
                <div class="flex gap-1 mb-[-1px]">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        @click="activeTab = tab.key"
                        class="font-head px-4 py-2 rounded-t-lg text-sm border transition shadow-sm"
                        :class="activeTab === tab.key
                            ? 'bg-white text-brand-900 border-gray-200 shadow-sm'
                            : 'bg-slate-100 text-slate-500 border-slate-100'"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <div class="rounded-b-2xl rounded-tr-2xl border border-slate-200 bg-white shadow-[0_8px_24px_rgba(15,23,42,0.05)] overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-200">
                        <h3 class="text-xl font-semibold text-slate-800">
                            {{ sectionTitle }}
                        </h3>
                    </div>

                    <div v-if="activeTab === 'points'" class="p-6 md:p-8">
                        <FormPoin
                            :form="forms.points"
                            :is-processing="isProcessing('points')"
                            @submit="submitAlert('points')"
                        />
                    </div>

                    <div v-else-if="activeTab === 'savings'" class="p-0">
                        <FormSimpanan
                            :form="forms.savings"
                            :is-processing="isProcessing('savings')"
                            @submit="submitAlert('savings')"
                        />
                    </div>

                    <div v-else-if="activeTab === 'financing'" class="p-6 md:p-8">
                        <FormPembiayaan
                            :form="forms.financing"
                            :is-processing="isProcessing('financing')"
                            @submit="submitAlert('financing')"
                        />
                    </div>
                </div>

                <div class="rounded-2xl mt-4 border border-slate-200 bg-white shadow-[0_8px_24px_rgba(15,23,42,0.05)] overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-800">Riwayat Pengaturan {{ sectionTitle }}</h3>
                    </div>

                    <div class="max-h-[400px] overflow-auto mt-4 m-6">
                        <BaseTable
                            :columns="historyColumns"
                            :data="historyItems"
                        >
                            <template #cell-value="{ row }">
                                {{ formatHistoryValue(row.value, row.key) }}
                            </template>

                            <template #cell-effective_date="{ row }">
                                {{ formatDate(row.effective_date) }}
                            </template>

                            <template #cell-updated_by="{ row }">
                                {{ row.updated_by || '-' }}
                            </template>

                            <template #cell-updated_at="{ row }">
                                {{ formatDate(row.updated_at) }}
                            </template>

                            <template #empty>
                                Belum ada riwayat perubahan untuk bagian ini.
                            </template>
                        </BaseTable>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>