<script setup>
import { router } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
</script>

<template>
  <AuthLayout title="Pendaftaran Berhasil">
    <div class="flex items-center justify-center px-4 min-h-screen">
      <div class="max-w-lg w-full rounded-3xl bg-blue-900/80 p-12 text-white shadow-2xl">
        <h1 class="text-4xl font-bold mb-3">
          <span class="text-orange-500">Thanks!</span><br />
          <span class="text-white">Your registration is submitted</span>
        </h1>

        <p class="mt-6 text-base text-gray-300 leading-relaxed">
          Your data is being <span class="text-orange-500 font-medium">reviewed</span> by the admin.<br />
          Please check your email regularly for further updates.
        </p>

        <div class="flex justify-end">
        <button
            @click="router.visit('/')"
            class="mt-8 inline-flex items-center justify-center
                rounded-lg bg-orange-500 px-8 py-2
                text-base font-semibold text-white
                hover:bg-orange-600 transition-colors shadow-lg"
        >
            Back to Home
        </button>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>
