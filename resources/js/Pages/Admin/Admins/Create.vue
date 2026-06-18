<script setup>
import { useForm } from '@inertiajs/vue3'
import Layout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify';
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import { useUserValidation } from '@/Composables/Validation/useUserValidation'
import Button from '@/Components/Form/Button.vue'
import { ref, computed } from 'vue'
import { useFormatter } from '@/Composables/Form/useFormatter'
import { useInputSanitizers } from '@/Composables/useInputSanitizers'

const form = useForm({
    user_id: '',
    email: '',
    nik: '',
    role_id: '',
    name: '',
    phone_number: '',
})

const props = defineProps({
    roles: { type: Array, required: true },
    members: { type: Array, required: true },
})

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengelolaan Pengurus', link: '/admin/pengurus' },
    { name: 'Tambah Pengurus' },
];

const { errors } = useUserValidation(form)

const searchQuery = ref('')
const searchResults = ref([])
const selectedMember = ref(null)

const { onlyNumbers } = useInputSanitizers()
const { normalizePhoneNumber } = useFormatter()

const isEditingExistingMember = computed(() => !!form.user_id)

const searchMembers = () => {
    if (searchQuery.value.length < 2) {
        searchResults.value = []
        return
    }

    const q = searchQuery.value.toLowerCase().trim()
    searchResults.value = props.members.filter(m =>
        m.name?.toLowerCase().includes(q) ||
        m.nik?.toLowerCase().includes(q) ||
        m.email?.toLowerCase().includes(q) ||
        m.user_code?.toLowerCase().includes(q)
    ).slice(0, 6)
}

const selectMember = (member) => {
    selectedMember.value = member
    form.user_id = member.id
    form.name = member.name
    form.nik = member.nik
    form.email = member.email
    form.phone_number = member.phone_number
    searchQuery.value = ''
    searchResults.value = []
}

const clearSelectedMember = () => {
    selectedMember.value = null
    form.user_id = ''
    form.name = ''
    form.nik = ''
    form.email = ''
    form.phone_number = ''
}

const submitForm = () => {
    Swal.fire({
        title: 'Konfirmasi',
        text: isEditingExistingMember.value
            ? 'Apakah Anda yakin ingin mempromosikan member ini menjadi pengurus?'
            : 'Apakah Anda yakin ingin menambahkan data admin ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#009141',
    }).then((result) => {
        if (result.isConfirmed) {
            form.post('/admin/pengurus/store', {
                onSuccess: () => {
                    toast(isEditingExistingMember.value ? "Member berhasil dipromosikan!" : "Pengurus berhasil ditambahkan!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    }).then(() => {
                        window.location.href = route('admin.admin.index')
                    })
                },
                onError: (errors) => {
                    const firstError = Object.values(errors || {})[0]
                    toast(firstError || 'Gagal menambahkan pengurus.', {
                        type: 'error',
                        position: 'bottom-right',
                        transition: 'slide',
                        dangerouslyHTMLString: true,
                    })
                }
            })
        }
    })
}
</script>

<template>
    <Layout title="Tambah Admin">
        <div class="flex flex-col">
            <PageBreadcrumb page-title="Tambah Pengurus" :items="breadcrumbItems" />
            <div class="card-layout flex flex-col px-0!">
                <div class="grid md:grid-cols-2 grid-cols-1 gap-6">
                    <div class="space-y-4 pl-6">
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tambahkan dari Anggota Aktif
                                (Opsional)</label>
                            <input v-if="!selectedMember" v-model="searchQuery" @input="searchMembers" type="text"
                                placeholder="Ketik nama, NIK, email, atau kode pengguna"
                                class="w-full px-4 py-2 border font-body text-sm shadow-theme-xs focus:outline-hidden focus:ring-3 placeholder:text-gray-400 rounded-lg border-gray-300 focus:border-brand-300 focus:ring-brand-500/10" />
                            <!-- Selected Member Info -->
                            <div v-else class="bg-green-50 border border-green-200 rounded-lg px-4 py-2">
                                <div class="flex justify-between items-center">
                                    <p class="font-semibold font-body text-md text-green-900">{{ selectedMember.user_code }}</p>
                                    <button class="text-primary flex items-center" @click="clearSelectedMember">
                                        <span class="icon-[tabler--x]"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Search Results Dropdown -->
                            <div v-if="searchResults.length > 0"
                                class="absolute z-100 top-full left-0 right-0 mt-1 border rounded-lg bg-white shadow-lg">
                                <div v-for="member in searchResults" :key="member.id" @click="selectMember(member)"
                                    class="px-4 py-3 border-b last:border-b-0 cursor-pointer hover:bg-gray-100">
                                    <div class="font-semibold">{{ member.name }}</div>
                                    <div class="text-sm text-gray-600">{{ member.user_code }} | NIK: {{ member.nik }}
                                    </div>
                                    <div class="text-sm text-gray-600">{{ member.email }}</div>
                                </div>
                            </div>

                            <!-- No Results Message -->
                            <div v-else-if="searchQuery.length >= 2 && searchResults.length === 0"
                                class="absolute z-10 top-full left-0 right-0 mt-1 border rounded-lg bg-white shadow-lg p-4">
                                <p class="text-gray-500 text-sm">Tidak ada member aktif yang ditemukan</p>
                            </div>
                        </div>

                    </div>
                    <div class="grid border-t gap-6 md:grid-cols-2 col-span-2 grid-cols-1 px-6 pt-6">
                        <!-- NIK -->
                        <BaseInputAdmin v-model="form.nik" label="NIK" type="text" required
                            placeholder="Masukkan 16 digit NIK" max="16" min="16" pattern="[0-9]*" :error="errors.nik"
                        >
                        </BaseInputAdmin>

                        <!-- Nama -->
                        <BaseInputAdmin v-model="form.name" label="Nama Lengkap" type="text" required
                            placeholder="Masukkan nama lengkap" :error="errors.name"
                        >
                        </BaseInputAdmin>

                        <!-- Posisi -->
                        <BaseInputAdmin v-model="form.role_id" label="Posisi" type="select" required
                            :selectables="roles.map(role => ({ value: role.id, text: role.name }))"
                            :error="errors.role_id">
                        </BaseInputAdmin>

                        <!-- Email -->
                        <BaseInputAdmin v-model="form.email" label="Email" type="email"
                            placeholder="Masukkan email" :error="errors.email">
                        </BaseInputAdmin>

                        <!-- No. Telp -->
                        <BaseInputAdmin v-model="form.phone_number" max="20" required label="Nomor Telepon" type="text"
                            @input="form.phone_number = normalizePhoneNumber(form.phone_number, onlyNumbers)"
                            placeholder="Masukkan nomor telepon" pattern="[0-9]*" :error="errors.phone_number"
                        >
                        </BaseInputAdmin>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-6 pb-6 mr-6">
                    <Button href="/admin/pengurus" variant="light">
                        Batal
                    </Button>
                    <Button @click="submitForm" variant="secondary"
                        :disabled="!form.role_id || form.processing || (isEditingExistingMember ? !selectedMember : !form.name || !form.nik || !form.email)">
                        <div v-if="form.processing" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" />
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </div>
            </div>
        </div>
    </Layout>
</template>
