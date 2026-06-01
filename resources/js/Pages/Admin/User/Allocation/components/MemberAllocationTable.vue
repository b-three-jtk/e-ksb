<script setup>
import { computed } from 'vue'
import BaseTable from '@/Components/Table/BaseTable.vue'
import Pagination from '@/Components/Table/Pagination.vue'
import UserIcon from '@/Icons/UserIcon.vue'

const props = defineProps({
  members: { type: Object, required: true },
  selectedMemberIds: { type: Array, required: true },
  allVisibleSelected: { type: Boolean, required: true },
  brokenMemberAvatarIds: { type: Object, required: true },
})

defineEmits(['toggle-visible-selection', 'update-selection', 'mark-broken-member-avatar'])

const memberRows = computed(() => props.members?.data ?? [])

const memberColumns = [
  { key: 'selection', label: '', align: 'center' },
  { key: 'profil', label: 'Profil Anggota' },
  { key: 'joined_date', label: 'Tanggal Bergabung' },
  { key: 'status', label: 'Status' },
  { key: 'pj_name', label: 'Penanggung Jawab Saat Ini' },
]

const statusBadgeClass = (status) => {
  switch (status) {
    case 'Aktif':
      return 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'
    case 'Tidak Aktif':
      return 'bg-rose-100 text-rose-700 ring-1 ring-rose-200'
    default:
      return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'
  }
}

const allocationBadgeClass = (status) => {
  return status === 'Sudah Dialokasikan'
    ? 'bg-sky-100 text-sky-700 ring-1 ring-sky-200'
    : 'bg-amber-100 text-amber-700 ring-1 ring-amber-200'
}
</script>

<template>
  <BaseTable :columns="memberColumns" :data="memberRows">
    <template #header-selection>
      <input
        type="checkbox"
        :checked="allVisibleSelected"
        @change="$emit('toggle-visible-selection', $event.target.checked)"
        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
      />
    </template>

    <template #cell-selection="{ row }">
      <input
        type="checkbox"
        :checked="selectedMemberIds.includes(row.member_id)"
        @change="$emit('update-selection', row.member_id, $event.target.checked)"
        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
      />
    </template>

    <template #cell-profil="{ row }">
      <div class="flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-100 text-slate-400 ring-2 ring-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:ring-slate-700">
          <img
            v-if="row.avatar && !brokenMemberAvatarIds.has(row.id)"
            :src="row.avatar"
            :alt="row.name"
            class="h-full w-full object-cover"
            @error="$emit('mark-broken-member-avatar', row.id)"
          />
          <UserIcon v-else class="h-6 w-6" />
        </div>
        <div>
          <div class="font-semibold text-slate-900 dark:text-white">{{ row.name }}</div>
          <div class="text-sm text-slate-500 dark:text-slate-400">{{ row.user_code }}</div>
          <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ row.phone_number || '-' }}</div>
        </div>
      </div>
    </template>

    <template #cell-joined_date="{ row }">
      <span class="text-sm text-slate-600 dark:text-slate-300">{{ row.joined_date }}</span>
    </template>

    <template #cell-status="{ row }">
      <div>
        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(row.status)">
          {{ row.status }}
        </span>
        <div class="mt-2">
          <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="allocationBadgeClass(row.allocation_status)">
            {{ row.allocation_status }}
          </span>
        </div>
      </div>
    </template>

    <template #cell-pj_name="{ row }">
      <div class="font-medium text-slate-900 dark:text-white">
        {{ row.pj_name || '-' }}
      </div>
    </template>

    <template #empty>
      Tidak ada anggota yang sesuai dengan filter saat ini.
    </template>
  </BaseTable>

  <Pagination :links="members.links" :total="members.total" />
</template>