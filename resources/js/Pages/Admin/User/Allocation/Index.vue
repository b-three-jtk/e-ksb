<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import BaseFunctionality from '@/Components/Table/BaseFunctionality.vue'
import AllocationStatsCards from './components/AllocationStatsCards.vue'
import MemberAllocationTable from './components/MemberAllocationTable.vue'
import PjSelectionCard from './components/PjSelectionCard.vue'
import AllocationSummaryCard from './components/AllocationSummaryCard.vue'
import useUserAllocation from '@/Composables/useUserAllocation'

const props = defineProps({
  members: { type: Object, required: true },
  pjUsers: { type: Array, required: true },
  filters: { type: Object, required: true },
  summary: { type: Object, required: true },
})

const breadcrumbItems = [
  { name: 'Dashboard', link: '/admin/dashboard' },
  { name: 'Keanggotaan', link: '/admin/users/list' },
  { name: 'Alokasi Anggota ke Penanggung Jawab' },
]

const filterState = reactive({
  search: props.filters.search ?? '',
  per_page: props.filters.per_page ?? 10,
  allocation_status: props.filters.allocation_status ?? '',
})
const {
  form,
  pjSearch,
  selectedPjId,
  selectedMemberIds,
  brokenMemberAvatarIds,
  brokenPjAvatarIds,
  selectedPj,
  selectedCount,
  totalAllocated,
  canAllocate,
  allVisibleSelected,
  updateSelection,
  toggleVisibleSelection,
  choosePj,
  markBrokenMemberAvatar,
  markBrokenPjAvatar,
  submitAllocation,
} = useUserAllocation(props)

const updateFilterState = (value) => {
  Object.assign(filterState, value)
}

const applyFilters = () => {
  router.get(
    '/admin/users/allocation',
    {
      search: filterState.search || undefined,
      per_page: filterState.per_page,
      allocation_status: filterState.allocation_status || undefined,
    },
    {
      preserveScroll: true,
      replace: true,
      preserveState: false,
    }
  )
}

let searchTimeout
watch(() => filterState.search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(applyFilters, 400)
})
watch(() => filterState.per_page, applyFilters)
watch(() => filterState.allocation_status, applyFilters)
</script>

<template>
  <AdminLayout title="Alokasi Anggota ke Penanggung Jawab">
    <PageBreadcrumb :items="breadcrumbItems" page-title="Alokasi Anggota ke Penanggung Jawab" />

    <div class="px-4 sm:px-6 lg:px-8 py-6">
      <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden dark:border-slate-700 dark:bg-slate-800">
          <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-700">
            <div class="flex flex-wrap items-center justify-between gap-4">
              <div>
                <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Data Anggota</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                  Pilih anggota yang akan dialokasikan ke penanggung jawab.
                </p>
              </div>

              <AllocationStatsCards :summary="summary" />
            </div>
          </div>

          <BaseFunctionality
            v-model:per-page="filterState.per_page"
            v-model:search="filterState.search"
            :filters="filterState"
            @update:filters="updateFilterState"
            :selects="[
              {
                key: 'allocation_status',
                label: 'Semua Alokasi',
                options: [
                  { label: 'Belum Dialokasikan', value: 'unallocated' },
                  { label: 'Sudah Dialokasikan', value: 'allocated' },
                ],
                optionLabel: 'label',
                optionValue: 'value',
              },
            ]"
            :per-page-options="[10, 25, 50, 100]"
            :search-tooltip="['Nama anggota', 'Nomor anggota', 'Nomor telepon']"
          />

          <MemberAllocationTable
            :members="members"
            :selected-member-ids="selectedMemberIds"
            :all-visible-selected="allVisibleSelected"
            :broken-member-avatar-ids="brokenMemberAvatarIds"
            @toggle-visible-selection="toggleVisibleSelection"
            @update-selection="updateSelection"
            @mark-broken-member-avatar="markBrokenMemberAvatar"
          />
        </section>

        <aside class="space-y-6">
          <PjSelectionCard
            :pj-users="pjUsers"
            :broken-pj-avatar-ids="brokenPjAvatarIds"
            :selected-pj-id="selectedPjId"
            :pj-search="pjSearch"
            @update:selected-pj-id="choosePj"
            @update:pj-search="pjSearch = $event"
            @mark-broken-pj-avatar="markBrokenPjAvatar"
          />

          <AllocationSummaryCard
            :selected-pj="selectedPj"
            :selected-count="selectedCount"
            :total-allocated="totalAllocated"
            :can-allocate="canAllocate"
            :processing="form.processing"
            @submit="submitAllocation"
          />
        </aside>
      </div>
    </div>
  </AdminLayout>
</template>
