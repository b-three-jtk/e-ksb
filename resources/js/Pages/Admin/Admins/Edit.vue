<script setup>
import { useForm } from '@inertiajs/vue3'
import Layout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import Swal from 'sweetalert2'
import { toast } from "vue3-toastify";
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import { useUserValidation } from '@/Composables/Validation/useUserValidation'
import Button from '../../../Components/Form/Button.vue';

const props = defineProps({
    admin: { type: Object, required: true },
    roles: { type: Array, required: true },
    educations: Array
})

const form = useForm({
    nik: props.admin.nik || '',
    name: props.admin.name || '',
    email: props.admin.email || '',
    role_id: props.admin.role_id || '',
    domicile_address: props.admin.domicile_address || '',
    phone_number: props.admin.phone_number || '',
    last_education: props.admin.last_education || '',
    birth_place: props.admin.birth_place || '',
    birth_date: props.admin.birth_date || '',
    gender: props.admin.gender || '',
    residential_address: props.admin.residential_address || '',
})

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Admin', link: '/admin/list' },
    { name: 'Edit Admin' },
];

const { errors } = useUserValidation(form)

const submitForm = () => {
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Apakah Anda yakin ingin memperbarui data admin ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, perbarui',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#007943',
    }).then((result) => {
        if (result.isConfirmed) {
            form.put(('/admin/update/' + props.admin.id), {
                onSuccess: () => {
                    toast("Admin berhasil diperbarui!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    }).then(() => {
                        window.location.href = route('admin.dashboard')
                    })
                },
                onError: () => {
                    toast("Gagal menambahkan admin.", {
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
    <Layout title="Edit Admin">
        <div class="flex flex-col">
            <PageBreadcrumb page-title="Edit Admin" :items="breadcrumbItems" />
            <div class="card-layout flex flex-col gap-10">
                <div class="grid md:grid-cols-2 grid-cols-1 gap-6">
                    <!-- NIK -->
                    <BaseInputAdmin v-model="form.nik" label="NIK" type="text" required
                        placeholder="Masukkan 16 digit NIK" max="16" min="16" pattern="[0-9]*" :error="errors.nik">
                    </BaseInputAdmin>

                    <!-- Nama -->
                    <BaseInputAdmin v-model="form.name" label="Nama Lengkap" type="text" required
                        placeholder="Masukkan nama lengkap" :error="errors.name"></BaseInputAdmin>

                    <!-- Jenis Kelamin -->
                    <BaseInputAdmin v-model="form.gender" label="Jenis Kelamin" type="radio" required :selectables="[
                        { value: 'Laki-laki', text: 'Laki-laki' },
                        { value: 'Perempuan', text: 'Perempuan' }
                    ]" :error="errors.gender">
                    </BaseInputAdmin>

                    <div class="flex gap-6">
                        <!-- Tempat Lahir -->
                        <BaseInputAdmin v-model="form.birth_place" max="150" label="Tempat Lahir" type="text"
                            placeholder="Masukkan tempat lahir" :error="errors.birth_place">
                        </BaseInputAdmin>
                        <!-- Tanggal Lahir -->
                        <BaseInputAdmin v-model="form.birth_date" label="Tanggal Lahir" type="date"
                            :error="errors.birth_date">
                        </BaseInputAdmin>
                    </div>

                    <!-- Pendidikan Terakhir -->
                    <BaseInputAdmin v-model="form.last_education" label="Pendidikan Terakhir" type="select"
                        :selectables="educations.map(unit => ({ value: unit, text: unit }))"
                        :error="errors.last_education">
                    </BaseInputAdmin>

                    <!-- Posisi -->
                    <BaseInputAdmin v-model="form.role_id" label="Posisi" type="select" required
                        :selectables="roles.map(role => ({ value: role.id, text: role.role_name }))" :error="errors.role_id">
                    </BaseInputAdmin>

                    <!-- Email -->
                    <BaseInputAdmin v-model="form.email" label="Email" type="email" required
                        placeholder="Masukkan email" :error="errors.email"></BaseInputAdmin>

                    <!-- No. Telp -->
                    <BaseInputAdmin v-model="form.phone_number" max="20" required label="Nomor Telepon" type="text"
                        placeholder="Masukkan nomor telepon" pattern="[0-9]*" :error="errors.phone_number">
                    </BaseInputAdmin>

                    <!-- Alamat (full width) -->
                    <BaseInputAdmin v-model="form.domicile_address" label="Alamat" type="textarea"
                        placeholder="Masukkan alamat lengkap sesuai KTP" rows="4" :error="errors.domicile_address">
                    </BaseInputAdmin>

                    <!-- Alamat Domisili (full width) -->
                    <BaseInputAdmin v-model="form.residential_address" label="Alamat Domisili" type="textarea"
                        placeholder="Masukkan alamat domisili" rows="4" :error="errors.residential_address">
                    </BaseInputAdmin>
                </div>

                <div class="flex items-center justify-end gap-6 pb-6">
                    <Button href="/admin/list" variant="light">
                        Batal
                    </Button>
                    <Button @click="submitForm" variant="secondary">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </div>
            </div>
        </div>
    </Layout>
</template>
