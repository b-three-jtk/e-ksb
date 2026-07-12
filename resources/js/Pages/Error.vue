<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import Button from '@/Components/Form/Button.vue'

const props = defineProps({
  status: Number,
})

const title = computed(() => {
  return {
    503: 'Layanan Tidak Tersedia',
    500: 'Kesalahan Server',
    404: 'Halaman Tidak Ditemukan',
    403: 'Akses Ditolak',
  }[props.status] || 'Terjadi Kesalahan'
})

const description = computed(() => {
  return {
    503: 'Mohon maaf, layanan kami sedang dalam pemeliharaan. Silakan coba lagi nanti.',
    500: 'Mohon maaf, terjadi kesalahan pada server kami. Tim kami sedang menanganinya.',
    404: 'Mohon maaf, halaman yang Anda cari tidak dapat ditemukan. Mungkin URL salah atau halaman telah dipindahkan.',
    403: 'Mohon maaf, Anda tidak memiliki izin untuk mengakses halaman ini.',
  }[props.status] || 'Mohon maaf, terjadi kesalahan yang tidak terduga.'
})

const icon = computed(() => {
    return {
        503: 'mdi:server-network-off',
        500: 'mdi:alert-circle-outline',
        404: 'mdi:file-search-outline',
        403: 'mdi:shield-lock-outline'
    }[props.status] || 'mdi:alert'
})

const goBack = () => {
    window.history.back()
}
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col justify-center items-center px-4 py-12 text-center">
        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-primary/10 text-primary mb-8">
            <Icon :icon="icon" class="w-12 h-12" />
        </div>
        
        <h1 class="text-9xl font-extrabold text-gray-300 dark:text-gray-700 font-head">{{ status }}</h1>
        <p class="mt-4 text-2xl font-semibold text-gray-800 dark:text-gray-200 font-head">{{ title }}</p>
        <p class="mt-2 text-gray-500 dark:text-gray-400 max-w-md mx-auto">{{ description }}</p>
        
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 w-full sm:w-auto">
            <Button variant="primary" size="medium" href="/" class="w-full sm:w-auto min-w-[200px]">
                Kembali ke Beranda
            </Button>
            <Button variant="outline" size="medium" @click="goBack" type="button" class="w-full sm:w-auto min-w-[200px]">
                Kembali ke Sebelumnya
            </Button>
        </div>
    </div>
</template>
