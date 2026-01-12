<script setup>
import { useForm } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import Layout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import Swal from 'sweetalert2'
import { toast } from "vue3-toastify";

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
    <Layout>
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
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                NIK<span class="text-red-500">*</span>
                            </label>
                            <input type="tel" v-model="form.nik" placeholder="Masukkan 16 digit NIK" maxlength="16" minlength="16" pattern="[0-9]*"
                                :class="['h-11 w-full rounded-lg border bg-transparent font-body px-4 py-2.5 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                                    form.errors.nik ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
                                ]"
                                class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                            <p v-if="form.errors.nik" class="text-red-500 text-xs mt-1">{{ form.errors.nik }}</p>
                        </div>

                        <!-- Posisi -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                Posisi<span class="text-red-500">*</span>
                            </label>
                            <div class="relative z-20 bg-transparent">
                                <select v-model="form.role_id" :class="['h-11 w-full appearance-none font-body rounded-lg border bg-transparent px-4 py-2.5 pr-11 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                                    form.errors.role_id ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
                                ]"
                                    class="dark:bg-dark-900 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">Pilih Posisi</option>
                                    <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}
                                    </option>
                                </select>
                                <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400"
                                    viewBox="0 0 20 20" fill="none">
                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <p v-if="form.errors.role_id" class="text-red-500 text-xs mt-1">{{ form.errors.role_id }}</p>
                        </div>

                        <!-- Nama -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                Nama<span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.name" autocomplete="off"
                                placeholder="Masukkan nama lengkap" :class="['h-11 w-full rounded-lg font-body border bg-transparent px-4 py-2.5 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                                    form.errors.name ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
                                ]"
                                class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>

                        <!-- Unit Kerja -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                Unit Kerja<span class="text-red-500">*</span>
                            </label>
                            <div class="relative z-20 bg-transparent">
                                <select v-model="form.work_unit_id" :class="['h-11 w-full font-body appearance-none rounded-lg border bg-transparent px-4 py-2.5 pr-11 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                                    form.errors.work_unit_id ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
                                ]"
                                    class="dark:bg-dark-900 text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="">Pilih Unit Kerja</option>
                                    <option v-for="unit in work_units" :key="unit.id" :value="unit.id">
                                        {{ unit.name }}
                                    </option>
                                </select>
                                <svg class="absolute z-30 right-4 top-1/2 -translate-y-1/2 pointer-events-none w-5 h-5 stroke-current text-gray-500 dark:text-gray-400"
                                    viewBox="0 0 20 20" fill="none">
                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <p v-if="form.errors.work_unit_id" class="text-red-500 text-xs mt-1">{{ form.errors.work_unit_id }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                Email<span class="text-red-500">*</span>
                            </label>
                            <input type="email" v-model="form.email" autocomplete="email" placeholder="nama@example.com" :class="['h-11 w-full rounded-lg font-body border bg-transparent px-4 py-2.5 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                                form.errors.email ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
                            ]"
                                class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                            <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                        </div>

                        <!-- Lembaga -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                Lembaga<span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.institution" placeholder="Masukkan lembaga" :class="['h-11 w-full rounded-lg font-body border bg-transparent px-4 py-2.5 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                                form.errors.institution ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
                            ]"
                                class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                            <p v-if="form.errors.institution" class="text-red-500 text-xs mt-1">{{ form.errors.institution }}
                            </p>
                        </div>

                        <!-- Alamat (full width) -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                Alamat
                            </label>
                            <textarea v-model="form.address" placeholder="Masukkan alamat lengkap" rows="4" :class="['w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                                form.errors.address ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
                            ]"
                                class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"></textarea>
                            <p v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</p>
                        </div>

                        <!-- No. Telp -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                No. Telp
                            </label>
                            <input type="tel" v-model="form.phone_number" placeholder="Masukkan nomor telepon" :class="['h-11 w-full rounded-lg font-body border bg-transparent px-4 py-2.5 text-sm shadow-theme-xs focus:outline-hidden focus:ring-3',
                                form.errors.phone_number ? 'border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
                            ]"
                                class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                            <p v-if="form.errors.phone_number" class="text-red-500 text-xs mt-1">{{ form.errors.phone_number }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-4 pt-10 px-8 pb-8">
                        <Link href="/admin/admins"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-8 py-2.5 text-gray-800 font-medium shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
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
