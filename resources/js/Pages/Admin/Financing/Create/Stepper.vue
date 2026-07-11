<script setup>
defineProps({
    activeStep: Number,
})

const steps = [
    {
        title: 'Identitas Pribadi',
        deskripsi: 'Isi identitas pemohon',
        icon: 'icon-[tabler--user]',
    },
    {
        title: 'Data Tanggungan dan Penghasilan',
        deskripsi: 'Validasi kemampuan pemohon membayar angsuran',
        icon: 'icon-[fa7-solid--hands-holding-child]',
    },
    {
        title: 'Objek Pembiayaan',
        deskripsi: 'Isi detail objek pembiayaan',
        icon: 'icon-[carbon--financial-assets]',
    },
    {
        title: 'Data Wakalah (Opsional)',
        deskripsi: 'Isi dan unggah dokumen akad wakalah',
        icon: 'icon-[tabler--file-certificate]',
    },
    {
        title: 'Pengadaan Barang',
        deskripsi: 'Isi dan unggah bukti pembelian barang',
        icon: 'icon-[tabler--archive]',
    },
    {
        title: 'Finalisasi Pembiayaan Murabahah',
        deskripsi: 'Isi rincian angsuran dan unggah dokumen akad',
        icon: 'icon-[tabler--clipboard-check]',
    },
]
</script>

<template>
    <div class="card-layout flex flex-col h-fit gap-4">
        <h1 class="card-title">Tahapan</h1>

        <div v-for="(step, i) in steps" :key="i" class="flex items-center gap-4">
            <div :class="[
                'rounded-full flex items-center justify-center h-11 w-11 transition-colors duration-200',
                activeStep === i + 1 ? 'bg-primary text-white shadow-md' : 
                activeStep > i + 1 ? 'bg-gray-200 text-gray-500 border-none' : 
                'bg-white text-gray-400 border border-stroke'
            ]">
                <span :class="step.icon" style="height:20px;width:20px" />
            </div>
            <div class="flex flex-col">
                <h1 :class="['font-medium', activeStep === i + 1 ? 'text-dark-text dark:text-gray-100' : (activeStep > i + 1 ? 'text-gray-600 dark:text-gray-400' : 'text-gray-500 dark:text-gray-500')]">{{ step.title }}</h1>
                <p :class="['text-sm font-body', activeStep > i + 1 ? 'text-gray-400' : 'text-gray-400']">{{ step.deskripsi }}</p>
            </div>
        </div>
    </div>
</template>
