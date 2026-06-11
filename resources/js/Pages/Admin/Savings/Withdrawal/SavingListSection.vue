<script setup>
import { computed } from 'vue'

const props = defineProps({
  selectedMember: {
    type: Object,
    default: null
  },
  selected: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['selected'])

const isWithdrawableType = (type) => {
  const lower = (type || '').toLowerCase()
  if (lower.includes('pokok') || lower.includes('wajib')) return false
  return true
}

function getMaturityDate(account) {
  const tenor = Number(account?.tenor_months || 0)
  const openedAt = account?.opened_at
  if (!tenor || !openedAt) return null

  const date = new Date(openedAt)
  if (Number.isNaN(date.getTime())) return null

  date.setMonth(date.getMonth() + tenor)
  return date
}

function formatDate(val) {
  if (!val) return '-'
  return val.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

function evaluateAvailability(acc) {
  const type = (acc.type || '').toLowerCase()
  const balance = Number(acc.balance || 0)

  if (!isWithdrawableType(acc.type)) {
    return {
      isAvailable: false,
      reason: 'Jenis simpanan ini tidak dapat dicairkan',
    }
  }

  if (balance <= 0) {
    return {
      isAvailable: false,
      reason: 'Saldo belum tersedia',
    }
  }

  if (type.includes('berjangka')) {
    const maturityDate = getMaturityDate(acc)
    const today = new Date()
    today.setHours(0, 0, 0, 0)

    if (maturityDate && today < maturityDate) {
      return {
        isAvailable: false,
        reason: `Belum jatuh tempo. Pencairan mulai ${formatDate(maturityDate)}`,
      }
    }
  }

  if (type.includes('ibadah')) {
    const target = Number(acc.target_amount || 0)
    if (target > 0 && balance < target) {
      return {
        isAvailable: false,
        reason: `Target belum tercapai (minimal ${formatRp(target)})`,
      }
    }
  }

  return {
    isAvailable: true,
    reason: '',
  }
}

const savingsList = computed(() => {
  if (!props.selectedMember) return []
  
  const accounts = props.selectedMember.savingAccounts || []
  return accounts
    .map(acc => {
      const availability = evaluateAvailability(acc)
      const typeLower = (acc.type || '').toLowerCase()
      const isFullWithdrawal = typeLower.includes('berjangka') || typeLower.includes('ibadah')
      return {
        id: acc.id,
        type: acc.type,
        balance: acc.balance,
        isAvailable: availability.isAvailable,
        reason: availability.reason,
        isFullWithdrawal,
      }
    })
})

function formatRp(val) {
  return Number(val || 0).toLocaleString('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  })
}

function selectSaving(saving) {
  if (!saving.isAvailable) return
  emit('selected', saving)
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
      <h2 class="text-xs font-semibold tracking-widest text-gray-500 dark:text-gray-400 uppercase font-head">
        Pilih Jenis Simpanan
      </h2>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hanya simpanan yang dapat ditarik yang bisa dipilih</p>
    </div>
    
    <div v-if="!selectedMember" class="text-center py-8 text-gray-500 dark:text-gray-400">
      <div class="text-sm">Pilih anggota terlebih dahulu untuk melihat daftar simpanan</div>
    </div>

    <div v-else-if="savingsList.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
      <div class="text-sm">Anggota tidak memiliki rekening simpanan</div>
    </div>

    <div v-else class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="saving in savingsList"
        :key="saving.id"
        @click="selectSaving(saving)"
        :class="[
          'p-4 border-2 rounded-lg cursor-pointer transition-all',
          saving.isAvailable
            ? props.selected?.id === saving.id
              ? 'border-green-600 bg-green-50 dark:bg-green-900/20'
              : 'border-gray-300 dark:border-gray-600 hover:border-green-400 bg-white dark:bg-gray-800'
            : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/60 cursor-not-allowed'
        ]"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <h3 class="font-semibold text-sm mb-1 text-gray-900 dark:text-gray-100">{{ saving.type }}</h3>
            <div class="text-lg font-bold mb-2 text-gray-900 dark:text-gray-100">
              {{ formatRp(saving.balance) }}
            </div>
            <div v-if="saving.isAvailable" class="inline-block px-2 py-1 bg-green-100 text-green-700 text-xs rounded font-medium">
              Dapat Ditarik
            </div>
            <div v-else class="inline-block px-2 py-1 bg-gray-200 text-gray-600 text-xs rounded font-medium">
              {{ saving.reason || 'Tidak Dapat Ditarik' }}
            </div>
          </div>
          <div v-if="saving.isAvailable" class="ml-2">
            <svg
              v-if="props.selected?.id === saving.id"
              class="w-6 h-6 text-green-600"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clip-rule="evenodd"
              />
            </svg>
            <svg
              v-else
              class="w-6 h-6 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div v-else class="ml-2">
            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
