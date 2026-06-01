<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import UserIcon from '@/Icons/UserIcon.vue'

const props = defineProps({
  pjUsers: { type: Array, required: true },
  brokenPjAvatarIds: { type: Object, required: true },
  selectedPjId: { type: [String, Number], required: true },
  pjSearch: { type: String, required: true },
})

const emit = defineEmits(['update:selectedPjId', 'update:pjSearch', 'mark-broken-pj-avatar'])

const visiblePjUsers = computed(() => {
  const keyword = props.pjSearch.trim().toLowerCase()

  if (!keyword) {
    return props.pjUsers
  }

  return props.pjUsers.filter((pj) => {
    return [pj.name, pj.user_code, pj.phone_number]
      .filter(Boolean)
      .some((value) => String(value).toLowerCase().includes(keyword))
  })
})

const choosePj = (pjId) => {
  emit('update:selectedPjId', pjId)
}
</script>

<template>
  <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Penanggung Jawab</h2>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cari dan pilih PJ tujuan alokasi.</p>
    </div>

    <div class="px-5 py-4">
      <div class="relative">
        <Icon icon="ic:baseline-search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
        <input
          :value="pjSearch"
          @input="emit('update:pjSearch', $event.target.value)"
          type="text"
          placeholder="Cari penanggung jawab"
          class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-emerald-500"
        />
      </div>

      <div class="mt-4 space-y-3 max-h-[420px] overflow-auto pr-1">
        <button
          v-for="pj in visiblePjUsers"
          :key="pj.id"
          type="button"
          @click="choosePj(pj.id)"
          class="w-full rounded-2xl border p-4 text-left transition"
          :class="selectedPjId === pj.id
            ? 'border-emerald-500 bg-emerald-50 shadow-sm dark:border-emerald-500 dark:bg-emerald-900/20'
            : 'border-slate-200 bg-slate-50 hover:border-emerald-300 hover:bg-emerald-50/60 dark:border-slate-700 dark:bg-slate-900/40 dark:hover:border-emerald-500/60'"
        >
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800">
              <img
                v-if="pj.avatar && !brokenPjAvatarIds.has(pj.id)"
                :src="pj.avatar"
                :alt="pj.name"
                class="h-full w-full object-cover"
                @error="$emit('mark-broken-pj-avatar', pj.id)"
              />
              <UserIcon v-else class="h-6 w-6" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="truncate font-semibold text-slate-900 dark:text-white">{{ pj.name }}</div>
              <div class="truncate text-sm text-slate-500 dark:text-slate-400">{{ pj.user_code || '-' }}</div>
            </div>
          </div>
          <div class="mt-3 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>{{ pj.phone_number || '-' }}</span>
            <span>{{ pj.allocated_members_count }} anggota</span>
          </div>
        </button>

        <div v-if="!visiblePjUsers.length" class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
          Tidak ada penanggung jawab yang cocok.
        </div>
      </div>
    </div>
  </section>
</template>