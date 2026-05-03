<script setup>
import UserIcon from '@/Icons/UserIcon.vue'

defineProps({
    activeStep: Number,
})
</script>

<template>
    <div class="card-layout flex flex-col h-fit gap-4">
        <h1 class="card-title">Tahapan</h1>

        <div class="flex items-center gap-4">
            <div :class="activeStep === 1 ? 'bg-primary text-white' : 'bg-white border border-stroke'"
                class="rounded-full flex items-center justify-center h-11 w-11">
                <UserIcon class="w-5 h-5" />
            </div>
            <div class="flex flex-col">
                <h1 class="font-medium text-dark-text">Identitas Pribadi</h1>
                <p class="text-sm text-gray-400 font-body">Detail identitas pemohon</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div :class="activeStep === 2 ? 'bg-primary text-white' : 'bg-white border border-stroke'"
                class="rounded-full flex items-center justify-center h-11 w-11">
                <span class="icon-[fa7-solid--hands-holding-child]" style="height: 20px; width: 20px;"></span>
            </div>
            <div class="flex flex-col">
                <h1 class="font-medium text-dark-text">Data Tanggungan dan Penghasilan</h1>
                <p class="text-sm text-gray-400 font-body">Validasi kemampuan pemohon membayar angsuran</p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div :class="activeStep === 3 ? 'bg-primary text-white' : 'bg-white border border-stroke'"
                class="rounded-full flex items-center justify-center h-11 w-11">
                <span class="icon-[tabler--archive]" style="height: 20px; width: 20px;"></span>
            </div>
            <div class="flex flex-col">
                <h1 class="font-medium text-dark-text">Catatan Pemeriksaan</h1>
                <p class="text-sm text-gray-400 font-body">Catatan dan komentar dari pemeriksa</p>
            </div>
        </div>

    </div>
</template>
