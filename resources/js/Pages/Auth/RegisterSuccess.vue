<script setup>
import { router } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
</script>

<template>
  <AuthLayout title="Pendaftaran Berhasil">
    <div class="flex items-center justify-center px-4 min-h-screen">
      <div class="max-w-lg w-full rounded-3xl bg-blue-900/80 p-12 text-white shadow-2xl">
        <h1 class="text-4xl font-bold mb-3">
          <span class="text-orange-500">Terima Kasih!</span><br />
          <span class="text-white">Pendaftaran Anda telah berhasil dikirim</span>
        </h1>

        <p class="mt-6 text-base text-gray-300 leading-relaxed">
          Data Anda sedang <span class="text-orange-500 font-medium">ditinjau</span> oleh admin.<br />
          Silakan periksa email Anda secara berkala untuk pembaruan lebih lanjut.
        </p>

        <div class="flex justify-end">
        <button
            @click="router.visit('/')"
            class="mt-8 inline-flex items-center justify-center
                rounded-lg bg-orange-500 px-8 py-2
                text-base font-semibold text-white
                hover:bg-orange-600 transition-colors shadow-lg"
        >
            Kembali ke Beranda
        </button>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>
