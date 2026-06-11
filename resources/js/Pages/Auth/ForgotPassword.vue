<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import Logo from '@/Components/Logo.vue'
import { toast } from 'vue3-toastify'
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import { useUserValidation } from '@/Composables/Validation/useUserValidation'
import { useFormatter } from '@/Composables/Form/useFormatter'
import { useInputSanitizers } from '@/Composables/useInputSanitizers'
import Button from '@/Components/Form/Button.vue'

const form = useForm({
    phone_number: '',
})

const { onlyNumbers } = useInputSanitizers()
const { errors } = useUserValidation(form)
const { normalizePhoneNumber } = useFormatter()

const submit = () => {
    form.post('/auth/forgot-password', {
        onSuccess: () => {
            toast.success('Link reset password telah dikirim ke nomor telepon Anda.', {
                autoClose: 2000,
                position: 'bottom-right',
            })
        },
        onError: (errors) => {
            console.error(errors)
            form.reset('phone_number')
            toast.error('Gagal mengirim link reset password. Periksa kembali nomor telepon Anda.', {
                autoClose: 3000,
                position: 'bottom-right',
            })
        },
        onFinish: () => form.reset('phone_number'),
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
                        <p class="text-gray-400 font-body px-6">Mohon masukkan nomor telepon terdaftar Anda untuk
                            menerima instruksi reset password.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-8">
                        <BaseInputAdmin v-model="form.phone_number"
                            placeholder="Masukkan nomor telepon. contoh: 628XXXXXXX" label="Nomor Telepon"
                            @input="form.phone_number = normalizePhoneNumber(form.phone_number, onlyNumbers)"
                            type="text" required :error="errors.phone_number" />

                        <div class="space-y-4">
                            <Button type="primary" :disabled="form.processing" full>
                                <div class="flex items-center justify-center gap-2" v-if="form.processing">
                                    <div class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" />
                                    Memproses...
                                </div>
                                <span v-else>Kirim</span>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
