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
    { name: 'Pengelolaan Anggota', link: '/admin/users' },
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
    kode_pengguna: props.data.kode_pengguna || '',
    nik: props.data.nik || '',
    nama: props.data.nama || '',
    email: props.data.email || '',
    password: '',
    password_confirmation: '',
    no_telp: props.data.no_telp || '',
    jenis_kelamin: props.data.anggota.jenis_kelamin || '',
    tempat_lahir: props.data.anggota.tempat_lahir || '',
    tgl_lahir: props.data.anggota.tgl_lahir || '',
    pendidikan_terakhir: props.data.anggota.pendidikan_terakhir || '',
    status_pernikahan: props.data.anggota.status_pernikahan || '',
    alamat_domisili: props.data.anggota.alamat_domisili || '',
    alamat_ktp: props.data.anggota.alamat_ktp || '',
    jml_tanggungan: props.data.anggota.jml_tanggungan || '',

    kk: props.data.kk || '',
    ktp: props.data.ktp || '',

    kk_file: null,
    ktp_file: null,

    ahli_waris: (props.data.anggota.ahli_waris || []).map(h => ({
        nik_ahli_waris: h.nik_ahli_waris,
        nama_ahli_waris: h.nama_ahli_waris,
        kontak_ahli_waris: h.kontak_ahli_waris,
        hubungan: h.pivot ? h.pivot.hubungan : h.hubungan
    })),
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
    nik_ahli_waris: '',
    nama_ahli_waris: '',
    hubungan: '',
    kontak_ahli_waris: '',
})

const addAhliWaris = (heirData) => {
    if (!heirData.nik_ahli_waris || !heirData.nama_ahli_waris || !heirData.hubungan || !heirData.kontak_ahli_waris) {
        toast.error('Lengkapi semua field untuk menambahkan ahli waris!', { position: 'bottom-right' });
        return
    }

    if (heirData.nik_ahli_waris.length !== 16) {
        toast.error('NIK Ahli Waris harus terdiri dari 16 digit.', { position: 'bottom-right' });
        return;
    }

    if (form.ahli_waris.some(h => h.nik_ahli_waris === heirData.nik_ahli_waris)) {
        toast.error('Ahli waris dengan NIK ini sudah ditambahkan.', { position: 'bottom-right' });
        return;
    }

    if (!/^\d+$/.test(heirData.kontak_ahli_waris)) {
        toast.error('Kontak Ahli Waris harus terdiri dari angka.', { position: 'bottom-right' });
        return;
    }

    form.ahli_waris.push({
        nik_ahli_waris: heirData.nik_ahli_waris,
        nama_ahli_waris: heirData.nama_ahli_waris,
        hubungan: heirData.hubungan,
        kontak_ahli_waris: heirData.kontak_ahli_waris,
    })

    heirInput.value = {
        nik_ahli_waris: '',
        nama_ahli_waris: '',
        hubungan: '',
        kontak_ahli_waris: '',
    }
}

const removeAhliWaris = (index) => {
    form.ahli_waris.splice(index, 1)
}

const submitForm = () => {
    Swal.fire({
        title: 'Apakah Anda yakin ingin menyimpan perubahan?',
        text: "Perubahan akan disimpan ke database.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#009141',
        cancelButtonColor: 'gray',
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
                    const errorMessage = err && Object.values(err)[0] ? Object.values(err)[0] : "Terjadi kesalahan.";
                    toast("Gagal memperbarui data anggota. " + errorMessage, {
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
                        v-model="form.kode_pengguna" disabled :errors="errors.kode_pengguna" @input="onlyAlpha" />
                    <BaseInputAdmin label="Nama Lengkap" placeholder="Masukkan nama lengkap" v-model="form.nama"
                        required :errors="errors.nama" @input="onlyAlpha" />
                    <BaseInputAdmin label="NIK" placeholder="Masukkan NIK" v-model="form.nik" max="16" required
                        :errors="errors.nik" @input="onlyNumbers" inputmode="numeric" />
                    <BaseInputAdmin label="Email" placeholder="Masukkan email" v-model="form.email"
                        :errors="errors.email" type="email" />
                    
                    <div class="col-span-2 grid grid-cols-2 gap-4 border border-gray-200 dark:border-gray-700 p-4 rounded-xl mb-2 mt-2">
                        <div class="col-span-2">
                            <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300">Ubah Kata Sandi (Opsional)</h3>
                            <p class="text-xs text-gray-500">Kosongkan jika tidak ingin mengubah kata sandi.</p>
                        </div>
                        <BaseInputAdmin label="Kata Sandi Baru" placeholder="Masukkan kata sandi baru" v-model="form.password"
                            :errors="errors.password" type="password" />
                        <BaseInputAdmin label="Konfirmasi Kata Sandi" placeholder="Masukkan ulang kata sandi" v-model="form.password_confirmation"
                            :errors="errors.password_confirmation" type="password" />
                    </div>
                    <BaseInputAdmin label="Nomor Telepon" required placeholder="Masukkan nomor telepon" max="14"
                        v-model="form.no_telp" :errors="errors.no_telp" @input="onlyNumbers"
                        inputmode="numeric" />
                    <BaseInputAdmin v-model="form.jenis_kelamin" label="Jenis Kelamin" type="radio" required :selectables="[
                        { value: 'Laki-laki', text: 'Laki-laki' },
                        { value: 'Perempuan', text: 'Perempuan' }
                    ]" :error="errors.jenis_kelamin">
                    </BaseInputAdmin>
                    <BaseInputAdmin label="Tempat Lahir" required v-model="form.tempat_lahir" :error="errors.tempat_lahir"
                        placeholder="Masukkan tempat lahir" @input="onlyAlpha" />
                    <BaseInputAdmin label="Tanggal Lahir" required type="date" v-model="form.tgl_lahir"
                        :maxDate="maxBirthDate"
                        :error="errors.tgl_lahir" />
                    <BaseInputAdmin v-model="form.alamat_ktp" label="Alamat KTP" type="textarea"
                        placeholder="Masukkan alamat lengkap sesuai KTP" rows="4" :error="errors.alamat_ktp"
                        @input="onlyAlphaNumericDash" />
                    <BaseInputAdmin v-model="form.alamat_domisili" label="Alamat Domisili" type="textarea"
                        placeholder="Masukkan alamat domisili" rows="4" :error="errors.alamat_domisili"
                        @input="onlyAlphaNumericDash" />
                    <BaseInputAdmin v-model="form.pendidikan_terakhir" required label="Pendidikan Terakhir" type="select"
                        :selectables="props.opsiPendidikan.map((item) => ({ value: item.value, text: item.text }))" :error="errors.pendidikan_terakhir" />
                    <BaseInputAdmin v-model="form.status_pernikahan" required label="Status Perkawinan" type="select"
                        :selectables="props.opsiStatusPerkawinan" />
                    <BaseInputAdmin v-model="form.jml_tanggungan" label="Jumlah Tanggungan Keluarga" type="number"
                        @input="onlyNumbers" inputmode="numeric" min="0" />
                </div>

                <div class="flex flex-col gap-4 w-3/4 py-4 col-span-2">
                    <div class="flex gap-4 w-full items-end">
                        <BaseInputAdmin label="Data Ahli Waris" max="16" pattern="[0-9]{16}"
                            placeholder="Masukkan NIK Ahli Waris" v-model="heirInput.nik_ahli_waris" @input="onlyNumbers"
                            inputmode="numeric" />
                        <BaseInputAdmin v-model="heirInput.nama_ahli_waris" placeholder="Nama Ahli Waris"
                            @input="onlyAlpha" />
                        <BaseInputAdmin v-model="heirInput.hubungan" type="select"
                            :selectables="props.opsiHubunganKeluarga" placeholder="Hubungan dengan anggota" />
                        <BaseInputAdmin v-model="heirInput.kontak_ahli_waris" max="20" placeholder="Nomor Kontak"
                            @input="onlyNumbers" inputmode="numeric" />
                        <Button variant="primary" @click="addAhliWaris(heirInput)">
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
                        <tbody v-if="form.ahli_waris.length > 0">
                            <tr v-for="(item, index) in form.ahli_waris" :key="index"
                                class="bg-white border-b text-dark-text dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
                                <td class="py-2 text-left pl-6">{{ item.nik_ahli_waris }}</td>
                                <td class="py-2 text-right pr-6">{{ item.nama_ahli_waris }}</td>
                                <td class="py-2 text-right pr-6">{{ item.hubungan }}</td>
                                <td class="py-2 text-right pr-6">{{ item.kontak_ahli_waris }}</td>
                                <td class="py-2 text-center flex justify-center">
                                    <Button size="small" variant="light" @click="removeAhliWaris(index)">
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
