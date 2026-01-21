<script setup>
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import Layout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import Swal from 'sweetalert2'
import { toast } from "vue3-toastify";
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import { useCreateAdminValidation } from '@/Composables/Validation/useCreateAdminValidation'


const props = defineProps({
    admin: { type: Object, required: true },
    roles: { type: Array, required: true },
    work_units: { type: Array, required: true },
})

const form = useForm({
    nik: props.admin.nik || '',
    name: props.admin.name || '',
    email: props.admin.email || '',
    role_id: props.admin.role_id || '',
    work_unit_id: props.admin.work_unit_id || '',
    institution: props.admin.institution || '',
    address: props.admin.address || '',
    phone_number: props.admin.phone_number || '',
})

const { errors } = useCreateAdminValidation(form)

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
        <div class="flex flex-col px-20">
            <PageBreadcrumb page-title="Edit Admin" />
            <div class="card-layout px-0!">
                <div class="flex flex-col px-8 border-b-2 border-gray-200 dark:border-gray-700 pb-4 mb-4">
                    <h1 class="card-title">Edit Admin</h1>
                    <p class="text-sm text-gray-400">Perbarui data admin di sini.</p>
                </div>

                <div class="flex flex-col">
                    <div class="grid md:grid-cols-2 grid-cols-1 px-8 gap-6">
                        <!-- NIK -->
                        <BaseInputAdmin v-model="form.nik" label="NIK" type="text" required
                            placeholder="Masukkan 16 digit NIK" max="16" min="16" pattern="[0-9]*" :error="errors.nik">
                        </BaseInputAdmin>

                        <!-- Posisi -->
                        <BaseInputAdmin v-model="form.role_id" label="Posisi" type="select" required
                            :selectables="roles.map(role => ({ value: role.id, text: role.name }))"
                            :error="errors.role_id"></BaseInputAdmin>

                        <!-- Nama -->
                        <BaseInputAdmin v-model="form.name" label="Nama Lengkap" type="text" required
                            placeholder="Masukkan nama lengkap" :error="errors.name"></BaseInputAdmin>

                        <!-- Unit Kerja -->
                        <BaseInputAdmin v-model="form.work_unit_id" label="Unit Kerja" type="select" required
                            :selectables="work_units.map(unit => ({ value: unit.id, text: unit.name }))"
                            :error="errors.work_unit_id"></BaseInputAdmin>

                        <!-- Email -->
                        <BaseInputAdmin v-model="form.email" label="Email" type="email" required
                            placeholder="Masukkan email" :error="errors.email"></BaseInputAdmin>

                        <!-- Lembaga -->
                        <BaseInputAdmin v-model="form.institution" label="Lembaga" type="text" required
                            placeholder="Masukkan nama lembaga" :error="errors.institution"></BaseInputAdmin>

                        <!-- Alamat (full width) -->
                        <BaseInputAdmin v-model="form.address" label="Alamat" type="textarea"
                            placeholder="Masukkan alamat lengkap" rows="4" :error="errors.address"></BaseInputAdmin>

                        <!-- No. Telp -->
                        <BaseInputAdmin v-model="form.phone_number" label="Nomor Telepon" type="text"
                            placeholder="Masukkan nomor telepon" pattern="[0-9]*" :error="errors.phone_number">
                        </BaseInputAdmin>
                    </div>

                    <div class="flex items-center justify-center gap-4 pt-10 px-8 pb-8">
                        <Link href="/admin/list"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-8 py-2.5 text-gray-800 font-medium shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3 dark:hover:text-gray-200">
                            Batal
                        </Link>
                        <button @click="submitForm()" :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-xl border border-secondary bg-secondary px-8 py-2.5 text-white font-medium shadow-theme-xs hover:bg-secondary/90 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
