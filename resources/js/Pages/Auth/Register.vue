<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import RegisterStepOne from '@/Components/Auth/RegisterStepOne.vue'
import RegisterStepTwo from '@/Components/Auth/RegisterStepTwo.vue'

const step = ref(1)

const form = useForm({
  email: '',
  nama_lengkap: '',
  nik: '',
  work_unit_id: '',
  nama_lembaga: '',
  password: '',
  password_confirmation: '',
  foto_pribadi: null,
  foto_ktp: null,
})

const next = () => (step.value = 2)
const submit = () => {
  form.post('/auth/register', {
    forceFormData: true,
    onSuccess: () => {
      console.log('Registration successful')
    },
    onError: (errors) => {
      console.error('Registration failed:', errors)
      alert('Terjadi kesalahan saat mendaftar. Silakan cek kembali data Anda.')
    }
  })
}

const stepOneValid = computed(() => {
  return (
    form.email &&
    form.nama_lengkap &&
    form.nik &&
    form.work_unit_id &&
    form.nama_lembaga &&
    form.password &&
    form.password_confirmation
  )
})

const stepTwoValid = computed(() => {
  return !!form.foto_pribadi && !!form.foto_ktp
})

const props = defineProps({
  workUnits: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <AuthLayout title="Daftar">
    <div class="w-full px-4">
      <div class="text-center mb-24">
        <h1 class="text-xl font-semibold text-white font-body">
          Logo KopSy-Kampus
        </h1>
      </div>

      <div class="mb-12 mx-auto max-w-3xl bg-white rounded-xl shadow-lg overflow-hidden font-body">
        <div class="flex h-2">
          <div class="flex-1" :class="step >= 1 ? 'bg-blue-900' : 'bg-gray-300'"></div>
          <div class="flex-1" :class="step >= 2 ? 'bg-blue-900' : 'bg-gray-300'"></div>
        </div>

        <div class="p-8">
          <div class="flex justify-center mb-12">
            <div class="flex items-center gap-3 mr-24">
              <div
                class="w-8 h-8 rounded-md flex items-center justify-center text-sm font-semibold"
                :class="step >= 1 ? 'bg-blue-900 text-white' : 'bg-gray-300 text-gray-500'"
              >
                1
              </div>
              <span :class="step >= 1 ? 'text-blue-900 font-semibold' : 'text-gray-400'">
                Data Anggota
              </span>
            </div>

            <div class="flex items-center gap-3 ml-24">
              <div
                class="w-8 h-8 rounded-md flex items-center justify-center text-sm font-semibold"
                :class="step >= 2 ? 'bg-blue-900 text-white' : 'bg-gray-300 text-gray-500'"
              >
                2
              </div>
              <span :class="step >= 2 ? 'text-blue-900 font-semibold' : 'text-gray-400'">
                Upload Foto & KTP
              </span>
            </div>
          </div>

          <RegisterStepOne v-if="step === 1" :form="form" :workUnits="props.workUnits" />
          <RegisterStepTwo v-if="step === 2" :form="form" />

          <div class="mt-12 flex flex-col items-center gap-6">
            <button
              v-if="step === 1"
              type="button"
              @click="next"
              :disabled="!stepOneValid"
              class="w-full max-w-md py-2 rounded-lg
                     bg-blue-900 text-white font-medium font-body
                     disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Selanjutnya
            </button>

            <button
              v-if="step === 2"
              type="button"
              @click="submit"
              :disabled="!stepTwoValid || form.processing"
              class="w-full max-w-md py-2 rounded-lg
                     bg-blue-900 text-white font-medium font-body
                     disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="form.processing">Mendaftar...</span>
              <span v-else>Daftar</span>
            </button>

            <p class="text-center text-md text-gray-500 font-body">
              Sudah punya akun?
              <a href="/auth/login" class="text-orange-500 font-medium font-body">
                Masuk sekarang
              </a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>
