<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
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
const selectedFile = ref(null)
const previewUrl = ref(props.user.photo_url || null)

const initialData = {
    name: props.user.name || '',
    nik: props.user.nik || '',
    birth_date: props.user.birth_date || '',
    gender: props.user.gender || '',
}

const hasDataChanged = computed(() => {
    return (
        form.name !== initialData.name ||
        form.nik !== initialData.nik ||
        form.birth_date !== initialData.birth_date ||
        form.gender !== initialData.gender ||
        selectedFile.value !== null
    )
})

const handleChangePicture = () => {
    fileInput.value.click()
}

const onFileChange = (event) => {
    const file = event.target.files[0]
    if (!file) return

    // Validate file type
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']
    if (!validTypes.includes(file.type)) {
        toast.error('Hanya file gambar (JPEG, PNG, JPG, GIF) yang diperbolehkan', {
            autoClose: 2000,
            position: 'bottom-right'
        })
        return
    }

    // Validate file size (max 2MB)
    if (file.size > 2048 * 1024) {
        toast.error('Ukuran file maksimal 2MB', {
            autoClose: 2000,
            position: 'bottom-right'
        })
        return
    }

    // Store file and create preview
    selectedFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
        previewUrl.value = e.target.result
    }
    reader.readAsDataURL(file)
}

const handleDeletePicture = () => {
    if (!selectedFile.value && !props.user.profile_picture) {
        toast.error('Tidak ada foto profil untuk dihapus', {
            autoClose: 2000,
            position: 'bottom-right'
        })
        return
    }

    Swal.fire({
        title: 'Hapus Foto Profil?',
        text: 'Apakah Anda yakin ingin menghapus foto profil?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            selectedFile.value = null
            previewUrl.value = null
        }
    })
}

const handleCancel = () => {
    if (hasDataChanged.value) {
        Swal.fire({
            title: 'Batalkan Perubahan?',
            text: 'Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin membatalkan?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Batalkan',
            cancelButtonText: 'Tetap di Halaman Ini'
        }).then((result) => {
            if (result.isConfirmed) {
                selectedFile.value = null
                previewUrl.value = props.user.photo_url || null
                router.visit('/user/profile')
            }
        })
    } else {
        router.visit('/user/profile')
    }
}

const submit = () => {
    Swal.fire({
        title: 'Simpan Perubahan?',
        text: 'Apakah Anda yakin ingin menyimpan perubahan profil?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Simpan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.put('/user/profile', {
                preserveScroll: true,
                onSuccess: () => {
                    // Upload photo after profile update
                    if (selectedFile.value) {
                        const formData = new FormData()
                        formData.append('profile_picture', selectedFile.value)
                        formData.append('_method', 'POST')

                        uploading.value = true
                        router.post('/user/profile/picture', formData, {
                            onSuccess: () => {
                                uploading.value = false
                                selectedFile.value = null
                                toast.success('Profil dan foto berhasil disimpan', {
                                    autoClose: 2000,
                                    position: 'bottom-right'
                                })
                                router.visit('/user/profile')
                            },
                            onError: () => {
                                uploading.value = false
                                toast.error('Data profil berhasil disimpan, tetapi foto profil gagal diunggah. Silakan coba unggah foto kembali.', {
                                    autoClose: 3000,
                                    position: 'bottom-right'
                                })
                            }
                        })
                    } else if (previewUrl.value === null && props.user.profile_picture) {
                        // Delete photo if user cleared preview
                        deleting.value = true
                        router.delete('/user/profile/picture', {
                            onSuccess: () => {
                                deleting.value = false
                                toast.success('Profil berhasil disimpan dan foto dihapus', {
                                    autoClose: 2000,
                                    position: 'bottom-right'
                                })
                                router.visit('/user/profile')
                            },
                            onError: () => {
                                deleting.value = false
                                toast.error('Data profil berhasil disimpan, tetapi foto profil gagal dihapus. Silakan coba hapus foto kembali.', {
                                    autoClose: 3000,
                                    position: 'bottom-right'
                                })
                            }
                        })
                    } else {
                        toast.success('Profil berhasil disimpan', {
                            autoClose: 2000,
                            position: 'bottom-right'
                        })
                        router.visit('/user/profile')
                    }
                },
                onError: () => {
                    toast.error('Gagal menyimpan profil', {
                        autoClose: 2000,
                        position: 'bottom-right'
                    })
                }
            })
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
                                <div v-if="previewUrl" class="w-32 h-32 rounded-full overflow-hidden border-4 border-gray-200">
                                    <img
                                        :src="previewUrl"
                                        :alt="'Profile picture of ' + (user.name || 'user')"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <div v-else class="w-32 h-32 rounded-full bg-gray-200 border-4 border-gray-200 flex items-center justify-center">
                                    <UserIcon class="w-16 h-16 text-gray-400" style="width: 64px; height: 64px;" />
                                </div>
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
                                    {{ uploading ? 'Mengunggah...' : 'Ubah foto' }}
                                </button>

                                <button
                                    type="button"
                                    @click="handleDeletePicture"
                                    :disabled="uploading || deleting || (!selectedFile && !user.profile_picture)"
                                    class="px-6 py-2.5 bg-red-100 text-red-600 text-sm font-semibold rounded-lg hover:bg-red-200 disabled:opacity-50"
                                >
                                    {{ deleting ? 'Menghapus...' : 'Hapus foto' }}
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

                        <div class="mt-8 flex justify-end gap-4">
                            <button
                                type="button"
                                @click="handleCancel"
                                :disabled="form.processing"
                                class="px-6 py-2.5 bg-gray-200 text-gray-700 text-base font-semibold rounded-lg hover:bg-gray-300 transition-colors disabled:opacity-50"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2.5 bg-blue-900 text-white text-base font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors flex items-center gap-2"
                            >
                                <span v-if="form.processing">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                                {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </Base>
</template>
