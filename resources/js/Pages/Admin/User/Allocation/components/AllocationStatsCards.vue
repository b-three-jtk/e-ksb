<script setup>
defineProps({
  summary: { type: Object, required: true },
})
</script>

<template>
  <div class="grid grid-cols-3 gap-3 text-sm">
    <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-700/60">
      <div class="text-slate-500 dark:text-slate-300">Total</div>
      <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ summary.total_members }}</div>
    </div>
    <div class="rounded-xl bg-emerald-50 px-4 py-3 dark:bg-emerald-900/30">
      <div class="text-emerald-700 dark:text-emerald-300">Dialokasikan</div>
      <div class="text-lg font-semibold text-emerald-800 dark:text-emerald-200">{{ summary.allocated_members }}</div>
    </div>
    <div class="rounded-xl bg-amber-50 px-4 py-3 dark:bg-amber-900/30">
      <div class="text-amber-700 dark:text-amber-300">Belum Dialokasikan</div>
      <div class="text-lg font-semibold text-amber-800 dark:text-amber-200">{{ summary.unallocated_members }}</div>
    </div>
  </div>
</template>