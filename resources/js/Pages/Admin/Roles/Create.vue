<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
import Button from '@/Components/Form/Button.vue'

const props = defineProps({
    permissions: Object,
})

const form = useForm({
    name:'',
    permissions: [],
})

const actionColumns = [
    { key: 'view', label: 'Membaca' },
    { key: 'create', label: 'Membuat' },
    { key: 'edit', label: 'Mengedit' },
    { key: 'approve', label: 'Memvalidasi' },
]

const permissionRows = computed(() => {
    return Object.entries(props.permissions).map(([module, items]) => {
        const actions = {
            view: null,
            create: null,
            edit: null,
            approve: null,
        }

        items.forEach((permission) => {
            const actionKey = permission.name.split('_')[0]
            if (actions.hasOwnProperty(actionKey)) {
                actions[actionKey] = permission.id
            }
        })

        return {
            module,
            actions,
        }
    })
})

const togglePermission = (permissionId) => {
    const index = form.permissions.indexOf(permissionId)
    if (index > -1) {
        form.permissions.splice(index, 1)
    } else {
        form.permissions.push(permissionId)
    }
}

const isChecked = (permissionId) => {
    return form.permissions.includes(permissionId)
}

const submitForm = () => {
    Swal.fire({
        title: 'Konfirmasi',
        text: 'Simpan peran dan hak akses baru ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, simpan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#009141',
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(`/admin/roles`, {
                preserveScroll: true,
                onSuccess: () => {
                    toast('Hak akses berhasil diperbarui.', {
                        type: 'success',
                        position: 'bottom-right',
                        transition: 'slide',
                        dangerouslyHTMLString: true,
                    })
                },
                onError: (errors) => {
                    const firstError = Object.values(errors)[0]
                    toast(firstError || 'Gagal membuat peran dan hak akses.', {
                        type: 'error',
                        position: 'bottom-right',
                        transition: 'slide',
                        dangerouslyHTMLString: true,
                    })
                },
            })
        }
    })
}

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Peran dan Akses', link: '/admin/roles' },
    { name: 'Buat Peran dan Akses' },
]
</script>

<template>
    <AdminLayout title="Buat Peran dan Akses">
        <PageBreadcrumb page-title="Buat Peran dan Akses" :items="breadcrumbItems" />

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100">Buat Peran dan Akses</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Atur peran sesuai hak akses yang tersedia.</p>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-6">
                <div class="grid gap-6">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            Nama Peran
                        </label>

                        <BaseInputAdmin
                            class="w-1/2"
                            v-model="form.name"
                            type="text"
                            placeholder="Masukkan nama peran"
                        />
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                        <table class="min-w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                                <tr>
                                    <th class="px-5 py-4 font-medium">No</th>
                                    <th class="px-5 py-4 font-medium">Modul</th>
                                    <th class="px-5 py-4 font-medium text-center">Membaca</th>
                                    <th class="px-5 py-4 font-medium text-center">Membuat</th>
                                    <th class="px-5 py-4 font-medium text-center">Mengedit</th>
                                    <th class="px-5 py-4 font-medium text-center">Memvalidasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, index) in permissionRows"
                                    :key="row.module"
                                    class="border-t border-gray-200 dark:border-gray-700"
                                >
                                    <td class="px-5 py-4 align-top">{{ index + 1 }}</td>
                                    <td class="px-5 py-4 align-top font-medium text-gray-800 dark:text-gray-100">{{ row.module }}</td>

                                    <td class="px-5 py-4 text-center">
                                        <template v-if="row.actions.view">
                                            <input
                                                type="checkbox"
                                                :value="row.actions.view"
                                                :checked="isChecked(row.actions.view)"
                                                @change="togglePermission(row.actions.view)"
                                                class="h-4 w-4 accent-green-600"
                                            />
                                        </template>
                                        <template v-else>
                                            <span class="text-gray-400">-</span>
                                        </template>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <template v-if="row.actions.create">
                                            <input
                                                type="checkbox"
                                                :value="row.actions.create"
                                                :checked="isChecked(row.actions.create)"
                                                @change="togglePermission(row.actions.create)"
                                                class="h-4 w-4 accent-green-600"
                                            />
                                        </template>
                                        <template v-else>
                                            <span class="text-gray-400">-</span>
                                        </template>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <template v-if="row.actions.edit">
                                           <input
                                                type="checkbox"
                                                :value="row.actions.edit"
                                                :checked="isChecked(row.actions.edit)"
                                                @change="togglePermission(row.actions.edit)"
                                                class="h-4 w-4 accent-green-600"
                                            />
                                        </template>
                                        <template v-else>
                                            <span class="text-gray-400">-</span>
                                        </template>
                                    </td>

                                    <td class="px-5 py-4 text-center">
                                        <template v-if="row.actions.approve">
                                           <input
                                                 type="checkbox"
                                                :value="row.actions.approve"
                                                :checked="isChecked(row.actions.approve)"
                                                @change="togglePermission(row.actions.approve)"
                                                class="h-4 w-4 accent-green-600"
                                            />
                                        </template>
                                        <template v-else>
                                            <span class="text-gray-400">-</span>
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-4">
                        <Button href="/admin/roles" variant="light">Batal</Button>
                        <Button type="submit" variant="secondary" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</Button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
