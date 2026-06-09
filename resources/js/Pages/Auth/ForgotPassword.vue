<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import BaseInput from '@/Components/Form/BaseInput.vue'
import Logo from '@/Components/Logo.vue'
import { toast } from 'vue3-toastify'

const form = useForm({
    email: '',
})

const submit = () => {
    form.post('/auth/forgot-password', {
        onSuccess: () => {
            toast.success('Link reset password telah dikirim ke email Anda.', {
                autoClose: 2000,
                position: 'bottom-right',
            })
        },
        onError: () => {
            form.reset('email')
            toast.error('Gagal mengirim link reset password. Periksa kembali email Anda.', {
                autoClose: 3000,
                position: 'bottom-right',
            })
        },
        onFinish: () => form.reset('email'),
    })
}
</script>

<template>
    <AuthLayout title="Lupa Kata Sandi">
        <div class="w-full px-4 py-8">
            <div
                class="max-w-xl mx-auto bg-white dark:bg-gray-800 border border-white/60 dark:border-gray-700 shadow-xl rounded-2xl backdrop-blur">
                <div class="p-12 space-y-8 flex flex-col">
                    <div class="mb-4 rounded-3xl mx-auto border border-stroke px-5 py-3 my-auto">
                        <Logo :titleIncluded="false" class="h-16 mx-auto" />
                    </div>
                    <div class="flex flex-col text-center">
                        <h1 class="card-title">Lupa Password</h1>
                        <p class="text-gray-400 font-body px-6">Mohon masukkan email terdaftar Anda untuk menerima instruksi reset password.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-8">
                        <BaseInput v-model="form.email" label="Email" type="email" required
                            :error="form.errors.email" />

                        <div class="space-y-4">
                            <button type="submit"
                                class="mt-2 mb-6 w-full bg-secondary hover:bg-primary text-white font-semibold font-head py-3 rounded-xl shadow-sm transition disabled:opacity-60 disabled:cursor-not-allowed"
                                :disabled="form.processing">
                                <span v-if="form.processing">Memproses...</span>
                                <span v-else>Kirim</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
