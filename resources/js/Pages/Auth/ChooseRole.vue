<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { toast } from 'vue3-toastify'

const selectingRole = ref(false)
const selectedRole = ref(null)

const pilihRole = (role) => {
  selectedRole.value = role
  selectingRole.value = true

  router.post('/auth/select-role', { role }, {
    onError: () => {
      toast.error('Terjadi kesalahan. Silakan coba lagi.', {
        autoClose: 3000,
        position: 'bottom-right',
      })
      selectingRole.value = false
      selectedRole.value = null
    },
  })
}
</script>

<template>
  <AuthLayout title="Pilih Mode Masuk">
    <div class="w-full px-4 py-8">
      <div class="max-w-lg mx-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-2xl rounded-2xl overflow-hidden">

        <!-- Header gradient strip -->
        <div class="h-1.5 w-full bg-gradient-to-r from-primary to-secondary"></div>

        <div class="p-8 sm:p-10">

          <!-- Logo -->
          <div class="flex justify-center mb-6">
            <img class="max-h-16" src="/public/images/logo/logo-icon.svg" alt="Logo">
          </div>

          <!-- Icon & judul -->
          <div class="flex flex-col items-center mb-8">
            <div class="w-16 h-16 rounded-full bg-primary/10 dark:bg-primary/20 flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
              </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white font-head text-center">
              Pilih Mode Masuk
            </h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 text-center leading-relaxed">
              Akun Anda terdaftar sebagai <strong class="text-gray-700 dark:text-gray-200">Pengurus</strong> sekaligus
              <strong class="text-gray-700 dark:text-gray-200">Anggota</strong>.<br>
              Pilih mode yang ingin Anda gunakan saat ini.
            </p>
          </div>

          <!-- Pilihan Kartu -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <!-- Kartu: Pengurus -->
            <button
              id="btn-masuk-pengurus"
              @click="pilihRole('admin')"
              :disabled="selectingRole"
              class="group relative flex flex-col items-center gap-4 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-600 hover:border-primary dark:hover:border-primary hover:shadow-lg hover:shadow-primary/10 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-gray-800"
              :class="{ 'border-primary shadow-lg shadow-primary/10 -translate-y-0.5': selectedRole === 'admin' }"
            >
              <!-- Loading spinner overlay -->
              <div v-if="selectedRole === 'admin' && selectingRole" class="absolute inset-0 rounded-xl flex items-center justify-center bg-white/70 dark:bg-gray-800/70">
                <svg class="animate-spin w-6 h-6 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
              </div>

              <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-700 group-hover:bg-primary/15 dark:group-hover:bg-primary/25 flex items-center justify-center transition-colors duration-200"
                :class="{ 'bg-primary/15 dark:bg-primary/25': selectedRole === 'admin' }">
                <svg class="w-7 h-7 text-gray-500 dark:text-gray-400 group-hover:text-primary transition-colors duration-200"
                  :class="{ 'text-primary': selectedRole === 'admin' }"
                  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
              </div>

              <div class="text-center">
                <div class="font-bold text-gray-700 dark:text-gray-100 group-hover:text-primary transition-colors duration-200"
                  :class="{ 'text-primary': selectedRole === 'admin' }">
                  Pengurus
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">Akses Halaman Pengurus</div>
              </div>
            </button>

            <!-- Kartu: Anggota -->
            <button
              id="btn-masuk-anggota"
              @click="pilihRole('anggota')"
              :disabled="selectingRole"
              class="group relative flex flex-col items-center gap-4 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-600 hover:border-secondary dark:hover:border-secondary hover:shadow-lg hover:shadow-secondary/10 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2 dark:focus:ring-offset-gray-800"
              :class="{ 'border-secondary shadow-lg shadow-secondary/10 -translate-y-0.5': selectedRole === 'anggota' }"
            >
              <!-- Loading spinner overlay -->
              <div v-if="selectedRole === 'anggota' && selectingRole" class="absolute inset-0 rounded-xl flex items-center justify-center bg-white/70 dark:bg-gray-800/70">
                <svg class="animate-spin w-6 h-6 text-secondary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
              </div>

              <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-700 group-hover:bg-secondary/15 dark:group-hover:bg-secondary/25 flex items-center justify-center transition-colors duration-200"
                :class="{ 'bg-secondary/15 dark:bg-secondary/25': selectedRole === 'anggota' }">
                <svg class="w-7 h-7 text-gray-500 dark:text-gray-400 group-hover:text-secondary transition-colors duration-200"
                  :class="{ 'text-secondary': selectedRole === 'anggota' }"
                  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
              </div>

              <div class="text-center">
                <div class="font-bold text-gray-700 dark:text-gray-100 group-hover:text-secondary transition-colors duration-200"
                  :class="{ 'text-secondary': selectedRole === 'anggota' }">
                  Anggota
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">Akses Halaman Anggota</div>
              </div>
            </button>

          </div>

          <!-- Info hint -->
          <p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-500">
            Anda bisa berpindah mode kapan saja dengan logout terlebih dahulu.
          </p>

        </div>
      </div>
    </div>
  </AuthLayout>
</template>
