<script setup>

const props = defineProps({
    steps: {
        type: Array,
        required: true,
        validator: (val) => val.every(step => step.label && step.key)
    },
    currentStep: {
        type: Number,
        required: true
    }
});

const getStepStatus = (index) => {
    if (index < props.currentStep) return 'completed';
    if (index === props.currentStep) return 'current';
    return 'pending';
};
</script>

<template>
    <div class="w-full bg-blue-900 rounded-2xl px-12 py-4 mb-8 mt-24">
        <div class="relative flex items-center justify-between">
            <div class="absolute top-4 left-0 right-0 h-0.5 bg-gray-400/30 -z-0">
                <div 
                    class="h-full bg-orange-500 transition-all duration-500"
                    :style="{ width: `${(currentStep / (steps.length - 1)) * 100}%` }"
                ></div>
            </div>

            <div 
                v-for="(step, index) in steps" 
                :key="step.key"
                class="relative flex flex-col items-center z-10"
                :class="index === 0 ? 'items-start' : index === steps.length - 1 ? 'items-end' : 'items-center'"
            >
                <div
                class="w-8 h-8 rounded-full flex items-center justify-center transition-all"
                :class="{
                    'bg-blue-900 border-4 border-orange-500': getStepStatus(index) === 'current',
                    'bg-orange-500 border-2 border-orange-500': getStepStatus(index) === 'completed',
                    'bg-blue-900 border-2 border-gray-400/30': getStepStatus(index) === 'pending'
                }"
                >
                    <span 
                    v-if="getStepStatus(index) === 'completed'" 
                    class="icon-[mdi--check] text-white text-lg"
                    ></span>

                    <div
                    v-else-if="getStepStatus(index) === 'current'"
                    class="w-3 h-3 rounded-full bg-orange-500"
                    ></div>

                    <div
                    v-else
                    class="w-3 h-3 rounded-full bg-white"
                    ></div>
                </div>

                <span 
                    class="mt-3 text-sm font-head font-medium transition-colors whitespace-nowrap"
                    :class="{
                        'text-orange-400': getStepStatus(index) === 'current' || getStepStatus(index) === 'completed',
                        'text-white/60': getStepStatus(index) === 'pending'
                    }"
                >
                    {{ step.label }}
                </span>
            </div>
        </div>
    </div>
</template>
