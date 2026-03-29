<script setup>
import { ref, watch } from 'vue'
import { getTodayYmd } from '@/utils/date'

const props = defineProps({
  selectedMember: {
    type: Object,
    default: null
  },
  selectedSaving: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update-form'])

const form = ref({
  withdrawalDate: getTodayYmd(),
  nominalRaw: '',
  nominalDisplay: '',
  method: 'Tunai',
  bankName: '',
  accountName: '',
  accountNumber: '',
  notes: ''
})

const errors = ref({})

const bankOptions = [
  'BCA', 'BNI', 'BRI', 'Mandiri', 'BTN', 
  'CIMB Niaga', 'Permata', 'Danamon', 'BSI', 'BJB'
]

function formatRp(val) {
  return Number(val || 0).toLocaleString('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  })
}

function onNominalInput(e) {
  const value = e.target.value
  const raw = value.replace(/\D/g, '')
  
  form.value.nominalRaw = raw
  form.value.nominalDisplay = raw ? formatRp(raw) : ''
  
  validateNominal()
}

function validateNominal() {
  errors.value.nominal = ''
  
  if (!form.value.nominalRaw) {
    errors.value.nominal = 'Nominal harus diisi'
    return
  }
  
  const nominal = Number(form.value.nominalRaw)
  if (nominal <= 0) {
    errors.value.nominal = 'Nominal harus lebih dari 0'
    return
  }
  
  if (nominal > props.selectedSaving.balance) {
    errors.value.nominal = `Nominal tidak boleh melebihi saldo (${formatRp(props.selectedSaving.balance)})`
  }
}

function validateWithdrawalDate() {
  errors.value.withdrawalDate = ''
  
  if (!form.value.withdrawalDate) {
    errors.value.withdrawalDate = 'Tanggal harus diisi'
    return
  }
  
  if (form.value.withdrawalDate > getTodayYmd()) {
    errors.value.withdrawalDate = 'Tanggal tidak boleh di masa depan'
  }
}

function validateBankFields() {
  errors.value.bankName = ''
  errors.value.accountName = ''
  errors.value.accountNumber = ''

  if (form.value.method !== 'Non-Tunai') return

  if (!String(form.value.bankName || '').trim()) {
    errors.value.bankName = 'Nama bank harus diisi'
  }

  if (!String(form.value.accountName || '').trim()) {
    errors.value.accountName = 'Atas nama harus diisi'
  }

  if (!String(form.value.accountNumber || '').trim()) {
    errors.value.accountNumber = 'Nomor rekening harus diisi'
  }
}

function updateForm() {
  emit('update-form', {
    ...form.value,
    savingId: props.selectedSaving?.id
  })
}

function applySavedBankInfo() {
  if (form.value.method !== 'Non-Tunai') return

  const savedAccounts = props.selectedMember?.accounts || []
  const latest = savedAccounts[0]
  if (!latest) return

  if (!form.value.bankName) {
    form.value.bankName = latest.bank_name || ''
  }

  if (!form.value.accountName) {
    form.value.accountName = latest.account_name || ''
  }

  if (!form.value.accountNumber) {
    form.value.accountNumber = latest.account_number || ''
  }
}

watch(() => form.value.method, () => {
  if (form.value.method === 'Tunai') {
    form.value.bankName = ''
    form.value.accountName = ''
    form.value.accountNumber = ''
    validateBankFields()
    return
  }

  applySavedBankInfo()
  validateBankFields()
})

watch(() => form.value.withdrawalDate, validateWithdrawalDate)
watch(() => form.value.nominalRaw, validateNominal)
watch(() => form.value.bankName, validateBankFields)
watch(() => form.value.accountName, validateBankFields)
watch(() => form.value.accountNumber, validateBankFields)
watch(form, updateForm, { deep: true })
watch(() => props.selectedSaving?.id, (newId, oldId) => {
  if (!newId || newId === oldId) return

  form.value.withdrawalDate = getTodayYmd()
  form.value.nominalRaw = ''
  form.value.nominalDisplay = ''
  form.value.method = 'Tunai'
  form.value.bankName = ''
  form.value.accountName = ''
  form.value.accountNumber = ''
  form.value.notes = ''
  errors.value = {}
  updateForm()
})

watch(() => props.selectedMember?.id, (newId, oldId) => {
  if (!newId || newId === oldId) return

  if (form.value.method === 'Non-Tunai') {
    form.value.bankName = ''
    form.value.accountName = ''
    form.value.accountNumber = ''
    applySavedBankInfo()
    updateForm()
  }
})

defineExpose({
  form,
})
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
      <h2 class="text-xs font-semibold tracking-widest text-gray-500 dark:text-gray-400 uppercase font-head">
        Detail Penarikan
      </h2>
    </div>

    <div v-if="!selectedSaving" class="text-center py-8 text-gray-500 dark:text-gray-400">
      <div class="text-sm">Pilih jenis simpanan terlebih dahulu untuk mengisi form penarikan</div>
    </div>

    <div v-else class="p-5 space-y-5">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Nominal Penarikan -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
            Nominal Penarikan <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.nominalDisplay"
            type="text"
            placeholder="0"
            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            @input="onNominalInput"
          />
          <div v-if="errors.nominal" class="text-red-500 text-sm mt-1">
            {{ errors.nominal }}
          </div>
        </div>

        <!-- Tanggal Penarikan -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
            Tanggal Penarikan <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.withdrawalDate"
            type="date"
            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-700/70 text-gray-900 dark:text-gray-100 focus:outline-none disabled:opacity-100 disabled:cursor-not-allowed"
            :max="getTodayYmd()"
            disabled
          />
          <div v-if="errors.withdrawalDate" class="text-red-500 text-sm mt-1">
            {{ errors.withdrawalDate }}
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Metode Penarikan -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 font-head">
            Metode Penarikan <span class="text-red-500">*</span>
          </label>
          <div class="grid grid-cols-2 gap-1">
            <label class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 cursor-pointer">
              <input
                v-model="form.method"
                type="radio"
                value="Tunai"
                class="w-4 h-4 text-blue-600"
              />
              <span class="text-sm">Tunai</span>
            </label>
            <label class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 cursor-pointer">
              <input
                v-model="form.method"
                type="radio"
                value="Non-Tunai"
                class="w-4 h-4 text-blue-600"
              />
              <span class="text-sm">Non-Tunai</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Bank Transfer Details -->
      <div v-if="form.method === 'Non-Tunai'" class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
            Nama Bank <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.bankName"
            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Pilih Bank</option>
            <option v-for="bank in bankOptions" :key="bank" :value="bank">
              {{ bank }}
            </option>
          </select>
          <div v-if="errors.bankName" class="text-red-500 text-sm mt-1">
            {{ errors.bankName }}
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
              Atas Nama <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.accountName"
              type="text"
              placeholder="Nama pemegang rekening"
              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <div v-if="errors.accountName" class="text-red-500 text-sm mt-1">
              {{ errors.accountName }}
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
              No. Rekening <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.accountNumber"
              type="text"
              placeholder="Nomor rekening"
              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <div v-if="errors.accountNumber" class="text-red-500 text-sm mt-1">
              {{ errors.accountNumber }}
            </div>
          </div>
        </div>
      </div>

      <!-- Catatan -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
          Catatan / Deskripsi
        </label>
        <textarea
          v-model="form.notes"
          placeholder="Catatan tambahan (opsional)"
          rows="3"
          class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>
    </div>
  </div>
</template>
