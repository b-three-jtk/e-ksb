<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue';
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import UserIcon from '@/Icons/UserIcon.vue';
import Button from '@/Components/Form/Button.vue'
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import { useUserValidation } from '@/Composables/Validation/useUserValidation.ts'
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify';
import { computed } from 'vue';
import { useFormatter } from '@/Composables/Form/useFormatter'
import { useInputSanitizers } from '@/Composables/useInputSanitizers'

const props = defineProps({
    user: Object,
    educations: Array
});

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Profil', link: '/admin/profile' },
    { name: 'Edit Profil' }
];

const form = useForm({
    nik: props.user.nik || '',
    name: props.user.name || '',
    email: props.user.email || '',
    phone_number: props.user.phone_number || '',
    profile_picture: props.user.profile_picture,
    profile_picture_file: null
})

const fileInput = ref(null)

const { errors } = useUserValidation(form)

const uploadProfilePicture = () => {
    fileInput.value.click()
}

const onFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.profile_picture_file = file;
        form.profile_picture = URL.createObjectURL(file);
    }
}

const { onlyNumbers } = useInputSanitizers()
const { normalizePhoneNumber } = useFormatter()

const submitForm = () => {
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin mengubah data profil ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, ubah profil',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#009141',
    }).then((result) => {
        if (result.isConfirmed) {
            form.transform((data) => ({
                ...data,
                _method: 'put',
            })).post('/admin/profile/update', {
                forceFormData: true,
                onSuccess: () => {
                    toast("Profil berhasil diubah!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    }).then(() => {
                        window.location.href = route('admin.profile.show')
                    })
                },

                onError: (errors) => {
                    toast(("Gagal mengubah profil:" + Object.values(errors).flat().join(' ')), {
                        "type": "error",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    })
                }
            })
        }
    })
}

const photoUrl = computed(() => {
    if (form.profile_picture && !form.profile_picture_file) {
        return `/storage/${form.profile_picture}`
    }
    return form.profile_picture || null
})
</script>

<template>
    <AdminLayout title="Edit Profil">
        <PageBreadcrumb page-title="Edit Profil" :items="breadcrumbItems" />
        <div class="flex flex-col gap-6">
            <div class="card-layout flex items-center gap-4">
                <div v-if="photoUrl">
                    <img class="w-20 h-20 rounded-full object-cover bg-gray-400" :src="photoUrl" alt="User avatar">
                </div>
                <div v-else
                    class="w-20 h-20 rounded-full bg-white border border-stroke flex items-center justify-center text-gray-500">
                    <UserIcon />
                </div>
                <div>
                    <input type="file" class="hidden" ref="fileInput" accept="image/jpeg,image/png,image/jpg,image/gif"
                        @change="onFileChange($event)" />
                    <Button @click.prevent="uploadProfilePicture()" variant="light">Unggah Foto Baru</Button>
                </div>
                <p class="w-sm text-gray-400 font-manrope">Direkomendasikan gambar ukuran 800x800, Hanya diperbolehkan
                    format JPG atau PNG, Maksimal Ukuran Gambar 1MB</p>
                <p class="text-error-500 text-sm">{{ errors.profile_picture }}</p>
            </div>
            <div class="card-layout flex flex-col gap-4">
                <h1 class="card-title">Identitas Pribadi</h1>
                <ul class="grid md:grid-cols-2 grid-cols-1 gap-4">
                    <li>
                        <BaseInputAdmin v-model="form.name" label="Nama Lengkap" type="text" required
                            placeholder="Masukkan nama lengkap" :error="errors.name">
                        </BaseInputAdmin>
                    </li>
                    <li>
                        <BaseInputAdmin v-model="form.nik" label="NIK" type="text" required
                            placeholder="Masukkan 16 digit NIK" max="16" min="16" pattern="[0-9]*" :error="errors.nik">
                        </BaseInputAdmin>
                    </li>
                    <li>
                        <BaseInputAdmin v-model="form.email" label="Email" type="email" placeholder="Masukkan email"
                            :error="errors.email">
                        </BaseInputAdmin>
                    </li>
                    <li>
                        <BaseInputAdmin v-model="form.phone_number" label="Nomor Telepon" type="text"
                            placeholder="Masukkan nomor telepon" max="16" min="16" pattern="[0-9]*"
                            @input="form.phone_number = normalizePhoneNumber(form.phone_number, onlyNumbers)"
                            :error="errors.phone_number">
                        </BaseInputAdmin>
                    </li>
                </ul>
            </div>
            <div class="flex items-center justify-center gap-4">
                <Button href="/admin/profile" variant="light">
                    Batal
                </Button>
                <Button @click="submitForm" :disabled="form.processing" variant="secondary">
                    <div v-if="form.processing"
                        class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </Button>
            </div>
        </div>
    </AdminLayout>
</template>
