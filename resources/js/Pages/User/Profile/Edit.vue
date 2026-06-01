<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
import Base from '../../../Layouts/Base.vue'
import BaseInput from '@/Components/Form/BaseInput.vue'
import BaseSelect from '@/Components/Form/BaseSelect.vue'
import Button from '@/Components/Form/Button.vue'
import EyeIcon from '@/Icons/EyeIcon.vue'
import UserIcon from '@/Icons/UserIcon.vue'

const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    educationOptions: {
        type: Array,
        default: () => []
    },
})

const user = computed(() => props.user || {})

const form = useForm({
    name: props.user.name || '',
    nik: props.user.nik || '',
    email: props.user.email || '',
    phone_number: props.user.phone_number || '',
    last_education: props.user.member?.last_education || '',
    residential_address: props.user.member?.residential_address || '',
})

const fileInput = ref(null)
const uploading = ref(false)
const deleting = ref(false)
const selectedFile = ref(null)
const previewUrl = ref(props.user.photo_url || null)
const isDocPreviewOpen = ref(false)
const previewDocument = ref({ title: '', url: '', kind: 'image' })

const initialData = {
    name: props.user.name || '',
    nik: props.user.nik || '',
    email: props.user.email || '',
    phone_number: props.user.phone_number || '',
    last_education: props.user.member?.last_education || '',
    residential_address: props.user.member?.residential_address || '',
}

const hasDataChanged = computed(() => {
    return (
        form.name !== initialData.name ||
        form.nik !== initialData.nik ||
        form.email !== initialData.email ||
        form.phone_number !== initialData.phone_number ||
        form.last_education !== initialData.last_education ||
        form.residential_address !== initialData.residential_address ||
        selectedFile.value !== null
    )
})

const member = computed(() => props.user.member || {})
const documents = computed(() => member.value.documents || {})

const identityFields = computed(() => [
    { label: 'NIK', value: user.value.nik || '', disabled: true },
    { label: 'Jenis Kelamin', value: member.value.gender || '', disabled: true },
    { label: 'Tanggal Lahir', value: member.value.birth_date || '', disabled: true },
    { label: 'Tempat Lahir', value: member.value.birth_place || '', disabled: true },
    { label: 'Pendidikan Terakhir', value: form.last_education || '', disabled: false },
    { label: 'Status Pernikahan', value: member.value.marital_status || '', disabled: true },
])

const documentState = computed(() => [
    {
        label: 'Foto KTP',
        title: 'KTP',
        url: documents.value.ktp || null,
    },
    {
        label: 'Foto KK',
        title: 'KK',
        url: documents.value.kk || null,
    },
])

const heirFields = computed(() => member.value.heirs || [])

const handleChangePicture = () => {
    fileInput.value.click()
}

const openDocumentPreview = (title, url) => {
    if (!url) return

    const kind = /\.pdf($|\?)/i.test(url) ? 'pdf' : 'image'
    previewDocument.value = { title, url, kind }
    isDocPreviewOpen.value = true
}

const closeDocumentPreview = () => {
    isDocPreviewOpen.value = false
    previewDocument.value = { title: '', url: '', kind: 'image' }
}

const updatePhoneNumber = (value) => {
    form.phone_number = String(value ?? '').replace(/\D/g, '')
}

const handlePhoneKeydown = (event) => {
    const allowedKeys = [
        'Backspace',
        'Delete',
        'Tab',
        'Enter',
        'Escape',
        'ArrowLeft',
        'ArrowRight',
        'ArrowUp',
        'ArrowDown',
        'Home',
        'End',
    ]

    if (allowedKeys.includes(event.key) || event.ctrlKey || event.metaKey) {
        return
    }

    if (!/^[0-9]$/.test(event.key)) {
        event.preventDefault()
    }
}

const finishSaveSuccess = () => {
    toast.success('Profil berhasil diperbarui', {
        autoClose: 2000,
        position: 'bottom-right'
    })

    setTimeout(() => {
        router.visit('/user/profile')
    }, 150)
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
        confirmButtonColor: '#008E43',
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
                                finishSaveSuccess()
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
                                finishSaveSuccess()
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
                        finishSaveSuccess()
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
        <div class="min-h-screen bg-[linear-gradient(180deg,#f8fafc_0%,#ffffff_100%)] pt-24 pb-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 mt-2">
                    <h1 class="text-3xl font-bold text-[#007031]">Edit Profil Anggota</h1>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="card-layout flex flex-col xl:flex-row justify-between gap-4 items-center">
                        <div class="flex flex-col xl:flex-row justify-center items-center text-center xl:text-left gap-6">
                            <div class="relative flex-shrink-0">
                                <div v-if="previewUrl" class="w-20 h-20 rounded-full overflow-hidden border border-stroke bg-white">
                                    <img
                                        :src="previewUrl"
                                        :alt="'Profile picture of ' + (user.name || 'user')"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <div v-else class="w-20 h-20 rounded-full bg-white border border-stroke flex items-center justify-center text-gray-500">
                                    <UserIcon />
                                </div>
                                <div v-if="uploading || deleting" class="absolute inset-0 bg-black/50 rounded-full flex items-center justify-center">
                                    <svg class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>

                            <div class="flex flex-col justify-center gap-1">
                                <div class="flex gap-2 items-center flex-wrap">
                                    <h1 class="card-title">{{ user.name || '-' }}</h1>
                                </div>
                                <p class="text-gray-500">{{ user.user_code || '-' }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
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
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-gray-400 px-5 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-gray-500 disabled:opacity-50"
                            >
                                {{ uploading ? 'Mengunggah...' : 'Ubah Foto' }}
                            </button>
                            <button
                                type="button"
                                @click="handleDeletePicture"
                                :disabled="uploading || deleting || (!selectedFile && !user.profile_picture)"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-red-500 px-5 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-red-600 disabled:opacity-50"
                            >
                                {{ deleting ? 'Menghapus...' : 'Hapus Foto' }}
                            </button>
                        </div>
                    </div>

                    <div class="card-layout grid gap-5">
                        <div class="card-layout py-0! grid xl:grid-cols-2 grid-cols-1 xl:gap-x-10">
                            <div class="grid grid-cols-1 gap-8 py-6 xl:pr-8">
                                <h1 class="card-title">Identitas Pribadi</h1>
                                <div class="grid xl:grid-cols-2 grid-cols-1 gap-4">
                                    <div
                                        v-for="field in identityFields"
                                        :key="field.label"
                                        class="flex flex-col gap-2"
                                    >
                                        <BaseSelect
                                            v-if="field.label === 'Pendidikan Terakhir'"
                                            v-model="form.last_education"
                                            :label="field.label"
                                            :disabled="form.processing"
                                            :error="form.errors.last_education"
                                        >
                                            <option
                                                v-for="education in props.educationOptions"
                                                :key="education"
                                                :value="education"
                                            >
                                                {{ education }}
                                            </option>
                                        </BaseSelect>
                                        <BaseInput
                                            v-else
                                            :label="field.label"
                                            :model-value="field.value"
                                            :disabled="field.disabled"
                                            :locked="field.disabled"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex flex-col gap-8 border-t-2 border-t-stroke xl:border-0 xl:border-l-2 xl:border-l-stroke xl:dark:border-l-gray-700 xl:pl-10 py-6">
                                <h1 class="card-title">Berkas Pendukung</h1>
                                <div class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                                    <div
                                        v-for="field in documentState"
                                        :key="field.label"
                                        class="rounded-2xl border border-stroke bg-white p-5 shadow-sm"
                                    >
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex flex-col gap-1">
                                                <span class="text-sm text-gray-500 dark:text-gray-300">{{ field.label }}</span>
                                            </div>
                                        </div>

                                        <div class="mt-5">
                                            <Button
                                                type="button"
                                                variant="gray"
                                                :disabled="!field.url"
                                                @click="openDocumentPreview(field.title, field.url)"
                                            >
                                                <EyeIcon />
                                                Lihat
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-layout py-0! grid xl:grid-cols-2 grid-cols-1 xl:gap-x-10">
                            <div class="grid grid-cols-1 gap-8 py-6 xl:pr-8">
                                <h1 class="card-title">Kontak dan Alamat</h1>
                                <ul class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                                    <li class="flex flex-col gap-2">
                                        <BaseInput
                                            v-model="form.phone_number"
                                            label="Nomor Telepon"
                                            type="text"
                                            inputmode="numeric"
                                            maxlength="20"
                                            :disabled="form.processing"
                                            :error="form.errors.phone_number"
                                            @keydown="handlePhoneKeydown"
                                            @update:model-value="updatePhoneNumber"
                                        />
                                    </li>
                                    <li class="flex flex-col gap-2">
                                        <BaseInput
                                            v-model="form.email"
                                            label="Email"
                                            type="email"
                                            :disabled="form.processing"
                                            :error="form.errors.email"
                                        />
                                    </li>
                                    <li class="flex flex-col gap-2">
                                        <BaseInput
                                            :model-value="member.domicile_address || ''"
                                            label="Alamat Sesuai KTP"
                                            type="text"
                                            multiline
                                            :rows="3"
                                            disabled
                                            locked
                                        />
                                    </li>
                                    <li class="flex flex-col gap-2">
                                        <BaseInput
                                            v-model="form.residential_address"
                                            label="Alamat Domisili"
                                            type="text"
                                            multiline
                                            :rows="3"
                                            :disabled="form.processing"
                                            :error="form.errors.residential_address"
                                        />
                                    </li>
                                </ul>
                            </div>

                            <div class="flex flex-col gap-8 border-t-2 border-t-stroke xl:border-0 xl:border-l-2 xl:border-l-stroke xl:dark:border-l-gray-700 xl:pl-10 py-6">
                                <h1 class="card-title">Ahli Waris</h1>
                                <div v-if="heirFields.length" class="grid gap-6">
                                    <div v-for="heir in heirFields" :key="heir.heir_nik" class="grid xl:grid-cols-2 grid-cols-1 gap-4 rounded-2xl border border-stroke p-4 bg-white">
                                        <BaseInput label="Nama Ahli Waris" :model-value="heir.heir_name || ''" disabled />
                                        <BaseInput label="NIK" :model-value="heir.heir_nik || ''" disabled locked />
                                        <BaseInput label="Hubungan Keluarga" :model-value="heir.relationship || ''" disabled locked />
                                        <BaseInput label="Kontak Ahli Waris" :model-value="heir.heir_contact || ''" disabled locked />
                                    </div>
                                </div>
                                <p v-else class="rounded-2xl border border-dashed border-stroke px-4 py-5 text-sm text-gray-500">
                                    Belum ada data ahli waris.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <button
                            type="button"
                            @click="handleCancel"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-gray-200 px-5 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-300 disabled:opacity-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-[#008e43] px-5 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-[#00783a] disabled:opacity-50"
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
                </form>
            </div>
        </div>

        <Transition name="fade">
            <div v-if="isDocPreviewOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="closeDocumentPreview">
                <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h4 class="text-base font-semibold text-slate-900">{{ previewDocument.title || 'Pratinjau Dokumen' }}</h4>
                            <p class="text-sm text-slate-500">Dokumen pendukung anggota</p>
                        </div>
                        <button type="button" @click="closeDocumentPreview" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 10-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </button>
                    </div>
                    <div class="bg-slate-50 p-5">
                        <img
                            v-if="previewDocument.url && previewDocument.kind === 'image'"
                            :src="previewDocument.url"
                            :alt="previewDocument.title"
                            class="mx-auto max-h-[70vh] rounded-xl object-contain"
                        >
                        <iframe
                            v-else-if="previewDocument.url && previewDocument.kind === 'pdf'"
                            :src="previewDocument.url"
                            class="h-[70vh] w-full rounded-xl bg-white"
                        ></iframe>
                    </div>
                </div>
            </div>
        </Transition>
    </Base>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.18s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
