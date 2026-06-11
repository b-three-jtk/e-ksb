<script setup>
import { useForm } from '@inertiajs/vue3'
import Layout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import Swal from 'sweetalert2'
import { toast } from "vue3-toastify";
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import { useUserValidation } from '@/Composables/Validation/useUserValidation'
import Button from '../../../Components/Form/Button.vue';
import { useFormatter } from '@/Composables/Form/useFormatter'
import { useInputSanitizers } from '@/Composables/useInputSanitizers'

const props = defineProps({
    admin: { type: Object, required: true },
    roles: { type: Array, required: true },
    educations: Array
})

const { onlyNumbers } = useInputSanitizers()
const { normalizePhoneNumber } = useFormatter()

const form = useForm({
    nik: props.admin.nik || '',
    name: props.admin.name || '',
    email: props.admin.email || '',
    role_id: props.admin.roles[0]?.id || '',
    phone_number: props.admin.phone_number || '',
})

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengurus', link: '/admin/list' },
    { name: 'Edit Pengurus' },
];

const { errors } = useUserValidation(form)

const submitForm = () => {
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin memperbarui data pengurus ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, perbarui',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#007943',
    }).then((result) => {
        if (result.isConfirmed) {
            form.put(('/admin/update/' + props.admin.id), {
                onSuccess: () => {
                    toast("Pengurus berhasil diperbarui!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    }).then(() => {
                        window.location.href = route('admin.dashboard')
                    })
                },
                onError: () => {
                    toast("Gagal memperbarui pengurus.", {
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
</script>

<template>
    <Layout title="Edit Pengurus">
        <div class="flex flex-col">
            <PageBreadcrumb page-title="Edit Pengurus" :items="breadcrumbItems" />
            <div class="card-layout flex flex-col gap-10">
                <div class="grid md:grid-cols-2 grid-cols-1 gap-6">
                    <!-- NIK -->
                    <BaseInputAdmin v-model="form.nik" label="NIK" type="text" required
                        placeholder="Masukkan 16 digit NIK" max="16" min="16" pattern="[0-9]*" :error="errors.nik">
                    </BaseInputAdmin>

                    <!-- Nama -->
                    <BaseInputAdmin v-model="form.name" label="Nama Lengkap" type="text" required
                        placeholder="Masukkan nama lengkap" :error="errors.name"></BaseInputAdmin>

                    <!-- Posisi -->
                    <BaseInputAdmin v-model="form.role_id" label="Posisi" type="select" required
                        :selectables="roles.map(role => ({ value: role.id, text: role.name }))" :error="errors.role_id">
                    </BaseInputAdmin>

                    <!-- Email -->
                    <BaseInputAdmin v-model="form.email" label="Email" type="email"
                        placeholder="Masukkan email" :error="errors.email"></BaseInputAdmin>

                    <!-- No. Telp -->
                    <BaseInputAdmin v-model="form.phone_number" max="20" required label="Nomor Telepon" type="text"
                            @input="form.phone_number = normalizePhoneNumber(form.phone_number, onlyNumbers)"
                            placeholder="Masukkan nomor telepon" pattern="[0-9]*" :error="errors.phone_number"
                        >
                    </BaseInputAdmin>
                </div>

                <div class="flex items-center justify-end gap-6 pb-6">
                    <Button href="/admin/list" variant="light">
                        Batal
                    </Button>
                    <Button @click="submitForm" :disabled="form.processing" variant="secondary">
                        <div v-if="form.processing" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </div>
            </div>
        </div>
    </Layout>
</template>
