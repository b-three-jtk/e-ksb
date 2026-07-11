<script setup>
import { computed } from 'vue';
import Tooltip from '@/Components/Form/Tooltip.vue';

const props = defineProps({
    fdr: {
        type: [String, Number],
        required: true,
    }
});

const numericFdr = computed(() => {
    if (typeof props.fdr === 'string') {
        return parseFloat(props.fdr.replace('%', ''));
    }
    return Number(props.fdr) || 0;
});

const maxFdr = 125;
const clampedFdr = computed(() => Math.min(Math.max(numericFdr.value, 0), maxFdr));

const leftPosition = computed(() => {
    return (clampedFdr.value / maxFdr) * 100;
});

const statusText = computed(() => {
    if (numericFdr.value < 50) return 'Tidak Aman (Tidak Likuid).';
    if (numericFdr.value < 75) return 'Kurang Aman (Kurang Likuid).';
    if (numericFdr.value <= 99) return 'Cukup Aman (Cukup Likuid).';
});

const actionText = computed(() => {
    if (numericFdr.value < 50) return 'Terlalu banyak dana menganggur. Tindakan: Perlu segera memaksimalkan penyaluran pembiayaan kepada anggota.';
    if (numericFdr.value < 75) return 'Produktivitas perputaran dana belum optimal. Tindakan: Tingkatkan promosi dan penyaluran produk pembiayaan.';
    if (numericFdr.value <= 99) return 'Perputaran dana cukup produktif. Tindakan: Pertahankan kinerja penyaluran dengan tetap memperhatikan prinsip kehati-hatian.';
    return 'Penyaluran dana sangat maksimal. Tindakan: Pastikan ketersediaan kas tunai minimum tetap terjaga untuk melayani penarikan simpanan.';
});
</script>

<template>
    <div
        class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-gray-50 dark:border-slate-700 shadow-sm flex flex-col justify-between">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-lg bg-[#e8f5e9] flex items-center justify-center text-brand-800 text-2xl dark:bg-green-900/30">
                    <span class="icon-[tabler--zoom-money]"></span>
                </div>
                <span class="card-title">Rasio Pembiayaan</span>
                <Tooltip>
                    <p class="font-semibold">Informasi Rasio Pembiayaan (FDR)</p>
                    <p class="mt-1">Rasio FDR mengukur produktivitas koperasi dalam memutar uang simpanan menjadi
                        pembiayaan.</p>
                    <p class="mt-2 font-semibold">Rumus Matematis:</p>
                    <p class="font-mono text-xs mt-1 bg-gray-100 dark:bg-slate-800 p-2 rounded">FDR = (Total Pembiayaan
                        / Total Simpanan) &times; 100%</p>
                </Tooltip>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ numericFdr }}%
            </div>
        </div>

        <div class="relative pt-4 pb-2">
            <div class="absolute -top-1 text-sm font-medium text-gray-800 dark:text-gray-200 -translate-x-1/2"
                :style="{ left: `${leftPosition}%` }">
                {{ numericFdr }}%
            </div>

            <div
                class="w-full h-3 rounded-full border border-gray-200/60 dark:border-slate-600 relative overflow-hidden flex">
                <!-- Tidak Aman (< 50) -->
                <!-- 50 / 125 = 40% -->
                <div class="h-full bg-red-500/20 dark:bg-red-900/40 border-r border-red-300 dark:border-red-500/50"
                    style="width: 40%;"></div>

                <!-- Kurang Aman (50 - 75) -->
                <!-- 25 / 125 = 20% -->
                <div class="h-full bg-amber-200/50 dark:bg-amber-900/40 border-r border-amber-300 dark:border-amber-500/50"
                    style="width: 20%;"></div>

                <!-- Cukup Aman (75 - 100) -->
                <!-- 25 / 125 = 20% -->
                <div class="h-full bg-green-200/50 dark:bg-green-800/40 border-r border-green-300 dark:border-green-500/50"
                    style="width: 20%;"></div>

                <!-- Aman (> 100) -->
                <!-- 25 / 125 = 20% -->
                <div class="h-full bg-[#00a650]/40 dark:bg-brand-900/50" style="width: 20%;"></div>
            </div>

            <div class="absolute top-[22px] h-6 w-0.5 bg-gray-900 dark:bg-white -translate-x-1/2 rounded-full shadow-sm"
                :style="{ left: `${leftPosition}%` }"></div>

            <div class="flex justify-between text-xs text-gray-400 dark:text-gray-500 mt-2 relative">
                <span>0</span>
                <span class="absolute left-[40%] -translate-x-1/2">50</span>
                <span class="absolute left-[60%] -translate-x-1/2">75</span>
                <span class="absolute left-[80%] -translate-x-1/2">100</span>
                <span>125%</span>
            </div>
        </div>
        <div
            class="mt-2 text-[14px] text-brand-800 dark:text-gray-500 bg-brand-300/20 dark:bg-slate-700/50 p-3 rounded-lg border border-gray-100 dark:border-slate-700">
            Nilai <strong>{{ numericFdr }}%</strong> menunjukkan status <strong>{{ statusText }} </strong><span
                class="font-semibold text-gray-700 dark:text-gray-300">Rekomendasi:</span> {{ actionText }}
        </div>
    </div>
</template>
