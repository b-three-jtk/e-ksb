<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import SelectMemberSection from './SelectMemberSection.vue'
import SavingListSection from './SavingListSection.vue'
import DetailSection from './DetailSection.vue'
import ConfirmationModal from '@/Components/Savings/ConfirmationModal.vue'
import Struk from '@/Components/Savings/Struk.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import { getTodayYmd } from '@/utils/date'

const page = usePage()

const breadcrumbItems = [
  { name: 'Dashboard', link: '/admin' },
  { name: 'Pengelolaan Simpanan', link: '/admin/savings/list' },
  { name: 'Penarikan Simpanan' },
]

const members = computed(() => page.props.members || [])
const initialStruk = computed(() => page.props.flash?.struk || null)

const showStrukInitial = ref(!!initialStruk.value)
const dataStruk = ref(initialStruk.value)

const selectedMember = ref(null)
const selectedSaving = ref(null)
const withdrawalFormRef = ref(null)
const currentFormData = ref({})
const showConfirmation = ref(false)
const showStruk = ref(showStrukInitial.value)

async function handleFlashReceipt(struk) {
  if (!struk) return

  dataStruk.value = struk
  showStruk.value = true

  toast('Penarikan simpanan berhasil disimpan!', {
    type: 'success',
    position: 'bottom-right',
  })
}

watch(
  () => page.props.flash?.struk,
  (struk) => {
    handleFlashReceipt(struk)
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
  memberNumber: selectedMember.value?.member_code || '',
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

  router.post('/admin/savings/penarikan', formData, {
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

        <Transition name="modal">
          <div
            v-if="showStruk && dataStruk"
            class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50"
            @click.self="showStruk = false"
          >
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden w-full max-w-sm">
              <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-gray-100">Transaksi Berhasil</h3>
                  <p class="text-sm text-gray-500 mt-0.5">Berikut kwitansi penarikan</p>
                </div>
                <button
                  @click="showStruk = false"
                  class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                >
                  <Icon icon="mdi:close" width="22" />
                </button>
              </div>
              <div class="overflow-y-auto max-h-[70vh] p-5 flex justify-center">
                <Struk mode="withdrawal" :transaksi="dataStruk" />
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
