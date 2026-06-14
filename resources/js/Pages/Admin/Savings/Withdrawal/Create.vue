<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import SelectMemberSection from './SelectMemberSection.vue'
import SavingListSection from './SavingListSection.vue'
import DetailSection from './DetailSection.vue'
import ConfirmationModal from '@/Components/Savings/ConfirmationModal.vue'
import Struk from '@/Components/Savings/Struk.vue'
import ModalDocument from '@/Components/ModalDocument.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, watch, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import { getTodayYmd } from '@/utils/date'

const breadcrumbItems = [
  { name: 'Dashboard', link: '/admin' },
  { name: 'Pengelolaan Simpanan', link: '/admin/savings' },
  { name: 'Penarikan Simpanan' },
]

const page = usePage()

const members = computed(() => page.props.members || [])


const selectedMember = ref(null)
const selectedSaving = ref(null)
const withdrawalFormRef = ref(null)
const currentFormData = ref({})
const showConfirmation = ref(false)
const showStruk = ref(false)
const dataStruk = ref(null)
const receipt = ref(null)
const isSubmitting = ref(false)
const modalReceipt = ref(null)

watch(
  () => page.props.flash?.struk,
  (struk) => {
    if (!struk) return

    dataStruk.value = struk
    receipt.value = page.props.flash?.receipt || null
    showStruk.value = true

    nextTick(() => {
      modalReceipt.value?.openModal?.()
    })

    toast.success('Penarikan simpanan berhasil disimpan', {
      position: 'bottom-right'
    })
  },
  { immediate: true }
)

const isFormValid = computed(() => {
  if (!selectedMember.value || !selectedSaving.value) return false

  const form = withdrawalFormRef.value?.form
  if (!form) return false

  const nominal = Number(form.nominalRaw || 0)
  const maxWithdrawal = Number(selectedSaving.value?.balance || 0)

  return (
    form.nominalRaw &&
    nominal > 0 &&
    nominal <= maxWithdrawal &&
    form.withdrawalDate &&
    form.withdrawalDate <= getTodayYmd() &&
    (!form.method || form.method !== 'Non-Tunai' ||
      (form.bankName && form.accountName && form.accountNumber))
  )
})

const confirmationData = computed(() => ({
  memberName: selectedMember.value?.name || '',
  memberNumber: selectedMember.value?.user_code || '',
  savingType: selectedSaving.value?.type || '',
  method: currentFormData.value.method || 'Tunai',
  amount: currentFormData.value.nominalRaw || 0,
  balance: selectedSaving.value?.balance || 0,
  date: currentFormData.value.withdrawalDate || getTodayYmd(),
  bankName: currentFormData.value.bankName || '',
  accountName: currentFormData.value.accountName || '',
  accountNumber: currentFormData.value.accountNumber || '',
}))

function onMemberSelected(member) {
  selectedMember.value = member
  selectedSaving.value = null
}

function onMemberReset() {
  selectedMember.value = null
  selectedSaving.value = null
}

function onSavingSelected(saving) {
  selectedSaving.value = saving
}

function onFormUpdate(data) {
  currentFormData.value = data
}

function openConfirmation() {
  if (!isFormValid.value) {
    toast('Lengkapi data yang wajib diisi', { type: 'warning', position: 'bottom-right' })
    return
  }
  showConfirmation.value = true
}

function submitWithdrawal() {
  if (isSubmitting.value) return

  isSubmitting.value = true

  const formData = new FormData()
  formData.append('member_id', selectedMember.value.id)
  formData.append('saving_account_id', selectedSaving.value.id)
  formData.append('amount', currentFormData.value.nominalRaw)
  formData.append('withdrawal_date', currentFormData.value.withdrawalDate)
  formData.append('method', currentFormData.value.method)
  formData.append('notes', currentFormData.value.notes || '')

  if (currentFormData.value.method === 'Non-Tunai') {
    formData.append('bank_name', currentFormData.value.bankName)
    formData.append('account_name', currentFormData.value.accountName)
    formData.append('account_number', currentFormData.value.accountNumber)
  }

  router.post('/admin/savings/withdrawal', formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      showConfirmation.value = false
      reset()
    },
    onError: (errors) => {
      showConfirmation.value = false
      const msg = Object.values(errors).flat().join('\n')
      toast(msg || 'Gagal menyimpan', { type: 'error', position: 'bottom-right' })
    },
    onFinish: () => {
      isSubmitting.value = false
    },
  })
}

function reset() {
  selectedMember.value = null
  selectedSaving.value = null
  currentFormData.value = {}
}
</script>

<template>
  <AdminLayout title="Penarikan Simpanan">
    <PageBreadcrumb page-title="Penarikan Simpanan" :items="breadcrumbItems" />
    <div class="py-6 px-4 sm:px-6 lg:px-8">
      <div class="w-full px-4 sm:px-10 lg:px-10 space-y-6 font-body">
        <SelectMemberSection
          :members="members"
          :selected="selectedMember"
          @selected="onMemberSelected"
          @reset="onMemberReset"
        />

        <SavingListSection
          v-if="selectedMember"
          :selectedMember="selectedMember"
          :selected="selectedSaving"
          @selected="onSavingSelected"
        />

        <ConfirmationModal
          v-if="selectedSaving"
          :is-open="showConfirmation"
          type="withdrawal"
          :data="confirmationData"
          :loading="isSubmitting"
          @confirm="submitWithdrawal"
          @close="showConfirmation = false"
        />

        <DetailSection
          v-if="selectedSaving"
          ref="withdrawalFormRef"
          :selectedMember="selectedMember"
          :selectedSaving="selectedSaving"
          @update-form="onFormUpdate"
        />

        <div v-if="selectedSaving" class="flex justify-end gap-3">
          <button
            @click="reset"
            type="button"
            class="px-6 py-2 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 font-medium transition"
          >
            Reset
          </button>
          <button
            @click="openConfirmation"
            type="button"
            :disabled="!isFormValid"
            :class="[
              'px-6 py-2 rounded-lg font-medium transition',
              isFormValid
                ? 'bg-green-600 text-white hover:bg-green-700'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
            ]"
          >
            Posting
          </button>
        </div>

        <ModalDocument
          v-if="showStruk"
          ref="modalReceipt"
          modal-id="withdrawal-receipt"
          title="Struk Penarikan"
          name="Struk Penarikan"
          :attachment="receipt"
        />
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
