<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import BaseInput from '@/Components/Form/BaseInput.vue'

const form = useForm({
  member_number: '',
  password: '',
})

const submit = () => {
  form.post('/auth/login', {
    onError: () => form.reset('password'),
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <AuthLayout>
    <div class="w-full px-4 py-8">
      <div class="max-w-xl mx-auto bg-white/95 border border-white/60 shadow-xl rounded-2xl backdrop-blur">
        <div class="p-10 space-y-8">
            <div class="text-center mb-12 mt-4">
                <h1 class="text-2xl font-semibold text-blue-900 font-head">Logo KopSy-Kampus</h1>
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
                    <span class="text-gray-500 font-body">Lupa password?</span>
                    <button
                    type="submit"
                    class="mt-2 w-full bg-blue-900 hover:bg-blue-800 text-white font-semibold font-body py-3 rounded-xl shadow-sm transition disabled:opacity-60 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                    >
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else>Masuk</span>
                    </button>
                </div>

                <p class="text-center text-md text-gray-600 font-body">
                Tidak punya akun?
                <Link href="/auth/register" class="text-orange-500 font-semibold">Daftar sekarang</Link>
                </p>
            </form>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>
