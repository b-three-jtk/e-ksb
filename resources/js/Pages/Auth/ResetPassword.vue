<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import BaseInput from '@/Components/Form/BaseInput.vue';
import Logo from '@/Components/Logo.vue';
import { toast } from 'vue3-toastify';

const props = defineProps({
    token: String,
    phone_number: String, 
})

const form = useForm({
    token: props.token,
    phone_number: props.phone_number ?? '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post('/auth/reset-password', {
        onSuccess: () => {
            toast.success('Password berhasil dibuat ulang. Silahkan lakukan login kembali.', {
                autoClose: 2500,
                position: 'bottom-right',
            })
        },
        onError: (errors) => {
            console.log(errors);
            toast.error('Gagal membuat ulang password. Periksa kembali data anda.', {
                autoClose: 3000,
                position: 'bottom-right',
            })
        },
    })
}
</script>

<template>
    <AuthLayout title="Reset Kata Sandi">
        <div class="w-full px-4 py-8">
            <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 border border-white/60 dark:border-gray-700 shadow-xl rounded-2xl backdrop-blur">
                <div class="p-12 space-y-8 flex flex-col">
                    <div class="mb-4 rounded-3xl mx-auto border border-stroke px-5 py-3 my-auto">
                        <Logo :titleIncluded="false" class="h-16 mx-auto" />
                    </div>

                    <div class="flex flex-col text-center">
                        <h1 class="card-title">Reset Password</h1>
                        <p class="text-gray-400 font-body px-6">Mohon masukan password baru anda di bawah ini.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-8">
                        <BaseInput
                            v-model="form.password"
                            label="Password Baru"
                            type="password"
                            :error="form.errors.password"
                            required
                        />

                        <BaseInput
                            v-model="form.password_confirmation"
                            label="Ulang Password Baru"
                            type="password"
                            :error="form.errors.password_confirmation"
                            required
                        />

                        <div class="space-y-4">
                            <button
                                type="submit"
                                class="w-full bg-secondary hover:bg-primary text-white py-3 rounded-xl transition"
                                :disabled="form.processing"
                            >
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