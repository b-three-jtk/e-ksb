<script setup>
defineProps({
    activeStep: Number,
})

const steps = [
    {
        title: 'Identitas Pribadi',
        description: 'Isi identitas pemohon',
        icon: 'icon-[tabler--user]',
    },
    {
        title: 'Data Tanggungan dan Penghasilan',
        description: 'Validasi kemampuan pemohon membayar angsuran',
        icon: 'icon-[fa7-solid--hands-holding-child]',
    },
    {
        title: 'Objek Pembiayaan',
        description: 'Isi detail objek pembiayaan',
        icon: 'icon-[carbon--financial-assets]',
    },
    {
        title: 'Pengadaan Barang',
        description: 'Isi dan unggah bukti pembelian barang',
        icon: 'icon-[tabler--archive]',
    },
    {
        title: 'Finalisasi Pembiayaan Murabahah',
        description: 'Isi rincian angsuran dan unggah dokumen akad',
        icon: 'icon-[tabler--clipboard-check]',
    },
]
</script>

<template>
    <div class="card-layout flex flex-col h-fit gap-4">
        <h1 class="card-title">Tahapan</h1>

        <div v-for="(step, i) in steps" :key="i" class="flex items-center gap-4">
            <div :class="activeStep === i + 1 ? 'bg-primary text-white' : 'bg-white border border-stroke'"
                class="rounded-full flex items-center justify-center h-11 w-11">
                <span :class="step.icon" style="height:20px;width:20px" />
            </div>
            <div class="flex flex-col">
                <h1 class="font-medium text-dark-text dark:text-gray-300">{{ step.title }}</h1>
                <p class="text-sm text-gray-400 font-body">{{ step.description }}</p>
            </div>
        </div>
    </div>
</template>
