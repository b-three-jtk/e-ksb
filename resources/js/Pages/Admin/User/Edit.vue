<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue';
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue';
import { useForm } from '@inertiajs/vue3';
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue';
import { useUserValidation } from '@/Composables/Validation/useUserValidation';
import { ref, watch } from 'vue';
import Swal from 'sweetalert2';
import { toast } from "vue3-toastify";
import Button from '@/Components/Form/Button.vue';

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Pengelolaan Anggota', link: '/admin/users/list' },
    { name: 'Edit Anggota' },
];

const props = defineProps({
    data: Object,
    opsiPendidikan: Array,
    opsiStatusPerkawinan: Array,
    opsiHubunganKeluarga: Array,
});

const form = useForm({
    _method: 'put',
    id: props.data.id,
    user_code: props.data.user_code || '',
    nik: props.data.nik || '',
    name: props.data.name || '',
    email: props.data.email || '',
    phone_number: props.data.phone_number || '',
    gender: props.data.member.gender || '',
    birth_place: props.data.member.birth_place || '',
    birth_date: props.data.member.birth_date || '',
    last_education: props.data.member.last_education || '',
    marital_status: props.data.member.marital_status || '',
    domicile_address: props.data.member.domicile_address || '',
    residential_address: props.data.member.residential_address || '',
    dependents: props.data.member.dependents || [],

    kk: props.data.kk || '',
    ktp: props.data.ktp || '',

    kk_file: null,
    ktp_file: null,

    heirs: props.data.member.heirs || [],
});

const { errors } = useUserValidation(form)

watch(() => form.ktp_file, (file) => {
    if (!file) return
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg']
    if (!allowedTypes.includes(file.type)) {
        toast.error('Format file KTP tidak didukung. Hanya diperbolehkan JPG, JPEG, atau PNG.', {
            position: 'bottom-right',
            transition: 'slide'
        })
        form.ktp_file = null
        return
    }
    const maxSizeBytes = 2 * 1024 * 1024
    if (file.size > maxSizeBytes) {
        toast.error('Ukuran file KTP melebihi batas maksimum 2 MB.', {
            position: 'bottom-right',
            transition: 'slide'
        })
        form.ktp_file = null
    }
})

watch(() => form.kk_file, (file) => {
    if (!file) return
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg']
    if (!allowedTypes.includes(file.type)) {
        toast.error('Format file KK tidak didukung. Hanya diperbolehkan JPG, JPEG, atau PNG.', {
            position: 'bottom-right',
            transition: 'slide'
        })
        form.kk_file = null
        return
    }
    const maxSizeBytes = 2 * 1024 * 1024
    if (file.size > maxSizeBytes) {
        toast.error('Ukuran file KK melebihi batas maksimum 2 MB.', {
            position: 'bottom-right',
            transition: 'slide'
        })
        form.kk_file = null
    }
})

const onlyNumbers = (event) => {
    const input = event.target;
    input.value = input.value.replace(/[^0-9]/g, '');
}

const onlyAlpha = (event) => {
    const input = event.target;
    input.value = input.value.replace(/[^a-zA-Z\s]/g, '');
}

const onlyAlphaNumericDash = (event) => {
    const input = event.target;
    input.value = input.value.replace(/[^a-zA-Z0-9\s\-.,]/g, '');
}

const heirInput = ref({
    heir_nik: '',
    heir_name: '',
    relationship: '',
    heir_contact: '',
})

const addHeir = (heirData) => {
    if (!heirData.heir_nik || !heirData.heir_name || !heirData.relationship || !heirData.heir_contact) {
        alert('Lengkapi semua field untuk menambahkan ahli waris!')
        return
    }

    form.heirs.push({
        heir_nik: heirData.heir_nik,
        heir_name: heirData.heir_name,
        relationship: heirData.relationship,
        heir_contact: heirData.heir_contact,
    })
}

const removeHeir = (index) => {
    form.heirs.splice(index, 1)
}

const submitForm = () => {
    Swal.fire({
        title: 'Apakah Anda yakin ingin menyimpan perubahan?',
        text: "Perubahan akan disimpan ke database.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#008E43',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, simpan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(`/admin/users/${form.id}/update`, {
                onSuccess: () => {
                    toast("Data anggota berhasil diperbarui!", {
                        "type": "success",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    })
                },
                onError: (err) => {
                    toast("Gagal memperbarui data anggota.", {
                        "type": "error",
                        "position": "bottom-right",
                        "transition": "slide",
                        "dangerouslyHTMLString": true
                    })
                }
            });
        }
    })
}
</script>

<template>
    <AdminLayout title="Edit Anggota">
        <div class="flex flex-col">
            <PageBreadcrumb :page-title="'Edit Anggota'" :items="breadcrumbItems" />
            <div class="card-layout flex flex-col gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <BaseInputAdmin label="Nomor Anggota" placeholder="Masukkan nomor anggota"
                        v-model="form.user_code" disabled :errors="errors.user_code" @input="onlyAlpha" />
                    <BaseInputAdmin label="Nama Lengkap" placeholder="Masukkan nama lengkap" v-model="form.name"
                        required :errors="errors.name" @input="onlyAlpha" />
                    <BaseInputAdmin label="NIK" placeholder="Masukkan NIK" v-model="form.nik" max="16" required
                        :errors="errors.nik" @input="onlyNumbers" inputmode="numeric" />
                    <BaseInputAdmin label="Email" placeholder="Masukkan email" v-model="form.email"
                        :errors="errors.email" type="email" />
                    <BaseInputAdmin label="Nomor Telepon" required placeholder="Masukkan nomor telepon" max="14"
                        v-model="form.phone_number" :errors="errors.phone_number" @input="onlyNumbers"
                        inputmode="numeric" />
                    <BaseInputAdmin v-model="form.gender" label="Jenis Kelamin" type="radio" required :selectables="[
                        { value: 'Laki-laki', text: 'Laki-laki' },
                        { value: 'Perempuan', text: 'Perempuan' }
                    ]" :error="errors.gender">
                    </BaseInputAdmin>
                    <BaseInputAdmin label="Tempat Lahir" v-model="form.birth_place" :error="errors.birth_place"
                        placeholder="Masukkan tempat lahir" @input="onlyAlpha" />
                    <BaseInputAdmin label="Tanggal Lahir" type="date" v-model="form.birth_date"
                        :error="errors.birth_date" />
                    <BaseInputAdmin v-model="form.residential_address" label="Alamat" type="textarea"
                        placeholder="Masukkan alamat lengkap sesuai KTP" rows="4" :error="errors.residential_address"
                        @input="onlyAlphaNumericDash" />
                    <BaseInputAdmin v-model="form.domicile_address" label="Alamat Domisili" type="textarea"
                        placeholder="Masukkan alamat domisili" rows="4" :error="errors.domicile_address"
                        @input="onlyAlphaNumericDash" />
                    <BaseInputAdmin v-model="form.last_education" label="Pendidikan Terakhir" type="select"
                        :selectables="props.opsiPendidikan.map((item) => ({ value: item.value, text: item.text }))" :error="errors.last_education" />
                    <BaseInputAdmin v-model="form.marital_status" label="Status Perkawinan" type="select"
                        :selectables="props.opsiStatusPerkawinan" />
                    <BaseInputAdmin v-model="form.dependents" label="Jumlah Tanggungan Keluarga" type="number"
                        @input="onlyNumbers" inputmode="numeric" min="0" />
                </div>

                <div class="flex flex-col gap-4 w-3/4 py-4 col-span-2">
                    <div class="flex gap-4 w-full items-end">
                        <BaseInputAdmin label="Data Ahli Waris" max="16" pattern="[0-9]{16}"
                            placeholder="Masukkan NIK Ahli Waris" v-model="heirInput.heir_nik" @input="onlyNumbers"
                            inputmode="numeric" />
                        <BaseInputAdmin v-model="heirInput.heir_name" placeholder="Nama Ahli Waris"
                            @input="onlyAlpha" />
                        <BaseInputAdmin v-model="heirInput.relationship" type="select"
                            :selectables="props.opsiHubunganKeluarga" placeholder="Hubungan dengan anggota" />
                        <BaseInputAdmin v-model="heirInput.heir_contact" max="20" placeholder="Nomor Kontak"
                            @input="onlyNumbers" inputmode="numeric" />
                        <Button variant="primary" @click="addHeir(heirInput)">
                            Tambah
                        </Button>
                    </div>

                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-gray-400 border-y dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="py-4 text-left pl-6">NIK</th>
                                <th class="py-4 text-right pr-6">Nama</th>
                                <th class="py-4 text-right pr-6">Hubungan</th>
                                <th class="py-4 text-right pr-6">Kontak</th>
                                <th class="py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody v-if="form.heirs.length > 0">
                            <tr v-for="(item, index) in form.heirs" :key="index"
                                class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                                <td class="py-2 text-left pl-6">{{ item.heir_nik }}</td>
                                <td class="py-2 text-right pr-6">{{ item.heir_name }}</td>
                                <td class="py-2 text-right pr-6">{{ item.relationship }}</td>
                                <td class="py-2 text-right pr-6">{{ item.heir_contact }}</td>
                                <td class="py-2 text-center flex justify-center">
                                    <Button size="small" variant="light" @click="removeHeir(index)">
                                        -
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                                <td colspan="5" class="py-4 text-center text-gray-400">Belum ada data ahli waris</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="w-1/2 flex flex-col gap-4">
                    <div class="flex flex-col gap-1 col-span-2 md:col-span-1">
                        <div class="flex justify-between items-center mb-1">
                        </div>
                        <div class="flex gap-4 items-end">
                            <div class="grow">
                                <BaseInputAdmin type="file" label="Kartu Tanda Penduduk (KTP)" v-model="form.ktp_file"
                                    accept="image/png,image/jpeg,image/jpg" :required="!form.ktp" />
                            </div>
                            <a v-if="form.ktp" :href="`${form.ktp}`" target="_blank"
                                class="h-11 px-4 flex items-center justify-center rounded-lg border border-primary text-primary hover:bg-primary hover:text-white transition-colors text-sm font-semibold whitespace-nowrap">
                                Lihat KTP Saat Ini
                            </a>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <p>Format: JPG, JPEG, PNG (Maks. 2 MB)</p>
                            <p v-if="form.ktp" class="text-amber-500 italic">*Abaikan jika tidak ingin mengganti KTP</p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 col-span-2 md:col-span-1">
                        <div class="flex gap-4 items-end">
                            <div class="grow">
                                <BaseInputAdmin type="file" label="Kartu Keluarga (KK)" v-model="form.kk_file"
                                    accept="image/png,image/jpeg,image/jpg" :required="!form.kk" />
                            </div>
                            <a v-if="form.kk" :href="`${form.kk}`" target="_blank"
                                class="h-11 px-4 flex items-center justify-center rounded-lg border border-primary text-primary hover:bg-primary hover:text-white transition-colors text-sm font-semibold whitespace-nowrap">
                                Lihat KK Saat Ini
                            </a>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <p>Format: JPG, JPEG, PNG (Maks. 2 MB)</p>
                            <p v-if="form.kk" class="text-amber-500 italic">*Abaikan jika tidak ingin mengganti KK</p>
                        </div>
                    </div>
                </div>
                <Button variant="secondary" class="self-end" @click="submitForm">
                    Simpan
                </Button>
            </div>
        </div>
    </AdminLayout>
</template>
