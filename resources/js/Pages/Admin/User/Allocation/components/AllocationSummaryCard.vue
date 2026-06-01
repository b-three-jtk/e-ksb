<script setup>
import Button from '@/Components/Form/Button.vue'

defineProps({
  selectedPj: { type: Object, default: null },
  selectedCount: { type: Number, required: true },
  totalAllocated: { type: Number, required: true },
  canAllocate: { type: Boolean, required: true },
  processing: { type: Boolean, required: true },
})

defineEmits(['submit'])
</script>

<template>
  <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
      <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ringkasan Alokasi</h2>
    </div>

    <div class="space-y-4 px-5 py-5">
      <div>
        <div class="text-sm text-slate-500 dark:text-slate-400">Penanggung Jawab Terpilih</div>
        <div class="mt-1 text-base font-semibold text-slate-900 dark:text-white">
          {{ selectedPj?.name || '-' }}
        </div>
        <div class="text-sm text-slate-500 dark:text-slate-400">
          {{ selectedPj?.user_code || '-' }}
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3 text-sm">
        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-700/60">
          <div class="text-slate-500 dark:text-slate-300">Dipilih</div>
          <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ selectedCount }}</div>
        </div>
        <div class="rounded-xl bg-emerald-50 px-4 py-3 dark:bg-emerald-900/30">
          <div class="text-emerald-700 dark:text-emerald-300">Total Anggota</div>
          <div class="text-lg font-semibold text-emerald-800 dark:text-emerald-200">{{ totalAllocated }}</div>
        </div>
      </div>

      <Button
        type="button"
        class="w-full justify-center"
        :disabled="!canAllocate || processing"
        @click="$emit('submit')"
      >
        {{ processing ? 'Menyimpan...' : 'Simpan Alokasi' }}
      </Button>
    </div>
  </section>
</template>