<script setup>
import { computed } from 'vue';

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

const maxFdr = 140;
const clampedFdr = computed(() => Math.min(Math.max(numericFdr.value, 0), maxFdr));

const leftPosition = computed(() => {
    return (clampedFdr.value / maxFdr) * 100;
});

const statusText = computed(() => {
    if (numericFdr.value < 85) return 'Posisi saat ini kurang. Perlu penambahan dana.';
    if (numericFdr.value > 100) return 'Posisi saat ini berisiko. Perlu pengurangan.';
    return 'Posisi saat ini normal. Aman.';
});
</script>

<template>
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-gray-50 dark:border-slate-700 shadow-sm flex flex-col justify-between">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#e8f5e9] dark:bg-green-900/30">

                </div>
                <span class="text-gray-600 dark:text-gray-300 font-medium text-base">Rasio Pembiayaan</span>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ numericFdr }}%
            </div>
        </div>

        <!-- Gauge Bar Area -->
        <div class="relative pt-4 pb-2">
            <div 
                class="absolute -top-1 text-sm font-medium text-gray-800 dark:text-gray-200 -translate-x-1/2"
                :style="{ left: `${leftPosition}%` }"
            >
                {{ numericFdr }}%
            </div>

            <div class="w-full h-3 rounded-full border border-gray-200/60 dark:border-slate-600 relative overflow-hidden flex">
                <!-- Zone Kurang (< 85) -->
                <!-- 85 / 140 = 60.714% -->
                <div 
                    class="h-full bg-amber-50 dark:bg-amber-900/30 border-r border-amber-200 dark:border-amber-500/50"
                    style="width: 60.714%;"
                ></div>
                
                <!-- Zone Aman (85 - 100) -->
                <!-- 15 / 140 = 10.714% -->
                <div 
                    class="h-full bg-[#e8f5e9] dark:bg-green-900/40 border-r border-[#81c784] dark:border-green-500/50"
                    style="width: 10.714%;"
                ></div>
                
                <!-- Zone Berisiko (> 100) -->
                <!-- 40 / 140 = 28.572% -->
                <div 
                    class="h-full bg-red-50 dark:bg-red-900/30"
                    style="width: 28.572%;"
                ></div>
            </div>

            <div 
                class="absolute top-[22px] h-6 w-0.5 bg-gray-900 dark:bg-white -translate-x-1/2 rounded-full"
                :style="{ left: `${leftPosition}%` }"
            ></div>

            <div class="flex justify-between text-xs text-gray-400 dark:text-gray-500 mt-2 relative">
                <span>0</span>
                <span class="absolute" style="left: 60.714%; transform: translateX(-50%);">85</span>
                <span class="absolute" style="left: 71.428%; transform: translateX(-50%);">100</span>
                <span>140%</span>
            </div>
        </div>

        <div class="mt-4 text-[13px] text-gray-500 dark:text-gray-400">
            Zona aman 85–100%. {{ statusText }}
        </div>
    </div>
</template>
