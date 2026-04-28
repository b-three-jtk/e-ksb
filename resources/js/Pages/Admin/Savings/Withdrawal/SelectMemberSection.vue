<script setup>
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  members: {
    type: Array,
    default: () => []
  },
  selected: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['selected', 'reset'])

const memberQuery = ref('')
const showDropdown = ref(false)
const selectedMember = computed(() => props.selected)

const memberList = computed(() => {
  if (Array.isArray(props.members)) return props.members
  if (props.members && typeof props.members === 'object') return Object.values(props.members)
  return []
})

function normalizeSearchValue(value) {
  return String(value ?? '').toLowerCase().trim()
}

const memberSuggestions = computed(() => {
  const q = normalizeSearchValue(memberQuery.value)
  if (!q) return []

  return memberList.value
    .filter((m) => {
      const name = normalizeSearchValue(m?.name)
      const memberNumber = normalizeSearchValue(m?.user_code)

      return name.includes(q) || memberNumber.includes(q)
    })
    .slice(0, 6)
})

const showSuggestions = computed(() => {
  return showDropdown.value && memberSuggestions.value.length > 0 && !selectedMember.value
})

function initials(name = '') {
  return name
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() || '')
    .join('')
}

function selectMember(member) {
  memberQuery.value = member.name
  showDropdown.value = false
  emit('selected', member)
}

function resetSelection() {
  memberQuery.value = ''
  emit('reset')
}

function onQueryInput() {
  showDropdown.value = true

  if (props.selected && memberQuery.value !== props.selected.name) {
    emit('reset')
  }
}

watch(
  () => props.selected,
  (val) => {
    if (val) {
      memberQuery.value = val.name || ''
      showDropdown.value = false
      return
    }

    memberQuery.value = ''
  },
  { immediate: true }
)
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
      <h2 class="text-xs font-semibold tracking-widest text-gray-500 dark:text-gray-400 uppercase font-head">
        Data Anggota
      </h2>
    </div>

    <div class="p-5 space-y-4">
      <!-- Search Input -->
      <div class="relative">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
          Cari Anggota <span class="text-red-500">*</span>
        </label>
        <div class="relative">
          <Icon icon="mdi:magnify" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            v-model="memberQuery"
            type="text"
            placeholder="Search Nama / No. Anggota"
            class="pl-10 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600
            rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            @focus="showDropdown = true"
            @input="onQueryInput"
            @blur="() => setTimeout(() => showDropdown = false, 200)"
          />
        </div>

        <!-- Suggestions dropdown -->
        <div
          v-if="showSuggestions"
          class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg mt-1 max-h-64 overflow-auto"
        >
          <button
            v-for="member in memberSuggestions"
            :key="member.id"
            @mousedown.prevent="selectMember(member)"
            type="button"
            class="w-full text-left px-4 py-2.5 hover:bg-blue-50 dark:hover:bg-gray-700 flex items-center gap-3 transition-colors"
          >
            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-700 dark:text-blue-300 font-semibold text-sm shrink-0">
              {{ initials(member.name) }}
            </div>
            <div>
              <div class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ member.name }}</div>
              <div class="text-xs text-gray-500">{{ member.user_code }}</div>
            </div>
          </button>
        </div>
      </div>

      <!-- Selected Member Summary -->
      <Transition name="fade">
        <div
          v-if="selectedMember"
          class="flex items-center gap-4 p-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg"
        >
          <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-xl font-bold text-blue-700 dark:text-blue-300 shrink-0">
            {{ initials(selectedMember.name) }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ selectedMember.name }}</div>
            <div class="text-sm text-gray-500">{{ selectedMember.user_code }}</div>
          </div>
          <button @click="resetSelection" type="button" class="text-red-400 hover:text-red-600 transition-colors shrink-0">
            <Icon icon="mdi:close" width="20" />
          </button>
        </div>
      </Transition>
      <div
        v-if="showDropdown && memberQuery.trim().length > 0 && memberSuggestions.length === 0"
        class="text-sm text-gray-500 dark:text-gray-400"
      >
        Anggota tidak ditemukan
      </div>

      <div v-if="!selectedMember" class="text-center py-8 text-gray-500">
        <div class="text-sm">Silakan cari dan pilih anggota untuk melanjutkan</div>
      </div>
    </div>
  </div>
</template>
