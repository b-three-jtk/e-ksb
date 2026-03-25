<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import BaseInput from '@/Components/Form/BaseInput.vue'
import Logo from '@/Components/Logo.vue'
import { toast } from 'vue3-toastify'

const form = useForm({
  member_number: '',
  password: '',
})

const submit = () => {
  form.post('/auth/login', {
    onSuccess: () => {
      toast.success('Login berhasil, Selamat Datang!', {
        autoClose: 2000,
        position: 'bottom-right',
      })
    },
    onError: () => {
      form.reset('password')
      toast.error('Login gagal. Periksa kembali nomor anggota dan password Anda.', {
        autoClose: 3000,
        position: 'bottom-right',
      })
    },
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <AuthLayout title="Masuk">
    <div class="w-full px-4 py-8">
      <div class="max-w-xl mx-auto bg-white/95 dark:bg-gray-800 border border-white/60 dark:border-gray-700 shadow-xl rounded-2xl backdrop-blur">
        <div class="p-8 space-y-8">
            <div class="flex justify-center mb-12">
                <Logo class="h-16 mx-auto" />
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <BaseInput
                  v-model="form.member_number"
                  label="Nomor Anggota"
                  type="text"
                  required
                  :error="form.errors.member_number"
                />

                <div class="space-y-4">
                  <BaseInput
                    v-model="form.password"
                    label="Password"
                    type="password"
                    required
                    :error="form.errors.password"
                  />
                </div>

                <div class="space-y-4">
                  <div class="flex justify-end">
                      <Link href="/auth/forgot-password"
                          class="text-gray-500 hover:text-accent hover:underline dark:text-white font-head">
                          Lupa password?
                      </Link>
                  </div>
                  
                  <button
                    type="submit"
                    class="mt-4 w-full bg-green-500 hover:bg-green-600 text-white font-semibold font-head py-3 rounded-xl shadow-sm transition disabled:opacity-60 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                    >
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else>Masuk</span>
                  </button>
                </div>
            </form>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>
