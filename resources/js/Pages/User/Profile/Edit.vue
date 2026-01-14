<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Base from '../../../Layouts/Base.vue'
import BaseInput from '@/Components/Form/BaseInput.vue'
import BaseSelect from '@/Components/Form/BaseSelect.vue'
import ReadonlyField from '@/Components/Form/ReadonlyField.vue'
import UserIcon from '@/Icons/UserIcon.vue'

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
})

const genderOptions = [
    { value: 'Laki-laki', label: 'Laki-laki' },
    { value: 'Perempuan', label: 'Perempuan' }
]

const form = useForm({
    name: props.user.name || '',
    nik: props.user.nik || '',
    birth_date: props.user.birth_date || '',
    gender: props.user.gender || '',
})

const fileInput = ref(null)
const uploading = ref(false)
const deleting = ref(false)

const handleChangePicture = () => {
    fileInput.value.click()
}

const onFileChange = (event) => {
    const file = event.target.files[0]
    if (!file) return

    // Validate file type
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']
    if (!validTypes.includes(file.type)) {
        alert('Hanya file gambar (JPEG, PNG, JPG, GIF) yang diperbolehkan')
        return
    }

    // Validate file size (max 2MB)
    if (file.size > 2048 * 1024) {
        alert('Ukuran file maksimal 2MB')
        return
    }

    uploading.value = true

    const formData = new FormData()
    formData.append('profile_picture', file)
    formData.append('_method', 'POST')

    router.post('/user/profile/picture', formData, {
        preserveScroll: true,
        onSuccess: () => {
            uploading.value = false
            fileInput.value.value = null
        },
        onError: (errors) => {
            uploading.value = false
            alert('Gagal mengupload foto: ' + (errors.profile_picture || 'Terjadi kesalahan'))
        }
    })
}

const handleDeletePicture = () => {
    if (!props.user.profile_picture) {
        alert('Tidak ada foto profil untuk dihapus')
        return
    }

    if (!confirm('Apakah Anda yakin ingin menghapus foto profil?')) {
        return
    }

    deleting.value = true

    router.delete('/user/profile/picture', {
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = false
        },
        onError: () => {
            deleting.value = false
            alert('Gagal menghapus foto profil')
        }
    })
}

const submit = () => {
    form.put('/user/profile', {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/user/profile')
        }
    })
}
</script>

<template>
<Base title="Edit Profil Anggota">
    <div class="min-h-screen bg-white dark:bg-gray-900 pt-20 pb-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold font-head text-blue-900 dark:text-blue-600 mt-24 mb-8">Edit Profil Anggota</h1>

            <form @submit.prevent="submit">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
                    <div class="flex flex-col sm:flex-row items-center gap-6 pb-8 border-b border-gray-200">
                        <div class="relative flex-shrink-0">
                            <img
                                :src="user.photo_url || '/images/default-avatar.png'"
                                :alt="'Profile picture of ' + (user.name || 'user')"
                                class="w-32 h-32 rounded-full object-cover border-4 border-gray-200"
                            />
                            <div v-if="uploading || deleting" class="absolute inset-0 bg-black bg-opacity-50 rounded-full flex items-center justify-center">
                                <svg class="animate-spin h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="mt-6 pl-10 flex gap-4">
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/jpeg,image/png,image/jpg,image/gif"
                                @change="onFileChange"
                                class="hidden"
                            >

                            <button
                                type="button"
                                @click="handleChangePicture"
                                :disabled="uploading || deleting"
                                class="px-6 py-2.5 bg-blue-900 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{ uploading ? 'Uploading...' : 'Ubah foto' }}
                            </button>

                            <button
                                type="button"
                                @click="handleDeletePicture"
                                :disabled="uploading || deleting || !user.profile_picture"
                                class="px-6 py-2.5 bg-red-100 text-red-600 text-sm font-semibold rounded-lg hover:bg-red-200 disabled:opacity-50"
                            >
                                {{ deleting ? 'Deleting...' : 'Hapus foto' }}
                            </button>
                        </div>

                    </div>

                    <div class="mt-12">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <BaseInput
                                label="Nama Anggota"
                                v-model="form.name"
                                :disabled="form.processing"
                                :error="form.errors.name"
                                required
                            />
                            <BaseInput
                                label="NIK"
                                v-model="form.nik"
                                :disabled="form.processing"
                                :error="form.errors.nik"
                                required
                            />
                            <BaseInput
                                label="Tanggal Lahir"
                                v-model="form.birth_date"
                                type="date"
                                :disabled="form.processing"
                                :error="form.errors.birth_date"
                                required
                            />
                            <BaseSelect
                                label="Jenis Kelamin"
                                v-model="form.gender"
                                :disabled="form.processing"
                                :error="form.errors.gender"
                                required
                            >
                                <option v-for="option in genderOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </BaseSelect>
                            <ReadonlyField label="Unit Kerja" :model-value="user.work_unit || '-'" />
                            <ReadonlyField label="Nama Lembaga" :model-value="user.institution || '-'" />
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-4 relative">
                        <!-- Loading overlay during submit -->
                        <div v-if="form.processing" class="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center rounded-lg">
                            <svg class="animate-spin h-6 w-6 text-blue-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="ml-2 text-blue-900 font-semibold">Menyimpan...</span>
                        </div>
                        <button
                            type="button"
                            @click="router.visit(`/user/profile/${user.member_number}`)"
                            class="px-8 py-3 bg-gray-200 text-gray-700 text-base font-semibold rounded-lg hover:bg-gray-300 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 bg-blue-900 text-white text-base font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</Base>
</template>
