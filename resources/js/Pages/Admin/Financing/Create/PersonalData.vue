<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Button from '@/Components/Form/Button.vue'
import { ref } from 'vue'
import { useFormatter } from '@/Composables/Form/useFormatter'

const { normalizePhoneNumber } = useFormatter()

const props = defineProps({
    form: Object,
    searchQuery: String,
    isLoadingSearch: Boolean,
    isAnggotaSelected: Boolean,
    anggotaResults: Array,
    data: Object,
    errors: Object,
    onlyLetters: Function,
    onlyNumbers: Function,
})

const emit = defineEmits([
    'update:searchQuery',
    'selectAnggota',
    'addAhliWaris',
    'removeAhliWaris',
    'resetAnggotaSelection',
    'validate-field',
])

const heirInput = ref({
    nik_ahli_waris: '',
    nama_ahli_waris: '',
    hubungan: '',
    kontak_ahli_waris: '',
})

const sanitizeAhliWarisNik = (event) => {
    heirInput.value.nik_ahli_waris = event.target.value.replace(/[^0-9]/g, '')
}
const sanitizeAhliWarisName = (event) => {
    heirInput.value.nama_ahli_waris = event.target.value.replace(/[^a-zA-Z\s]/g, '')
}

const onFieldChange = (field) => emit('validate-field', field)
</script>

<template>
    <section>
        <div class="border-b border-gray-200 px-8 pb-4">
            <h1 class="card-title">Identitas Pribadi & Ahli Waris</h1>
        </div>

        <!-- Warning eligibility -->
        <Transition name="fade"
            v-if="form.anggota.is_have_eligible_saving === false || form.anggota.is_have_no_obligation === false"
            class="bg-yellow-100 mx-4 mt-4 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg relative">
            <div class="flex flex-col gap-2">
                <p>Pemohon tidak memenuhi syarat mengajukan pembiayaan murabahah:</p>
                <ul class="list-disc list-inside mt-2">
                    <li v-if="form.anggota.is_have_eligible_saving === false">
                        Memiliki tabungan anggota yang sudah berjalan selama 1 bulan
                    </li>
                    <li v-if="form.anggota.is_have_no_obligation === false">
                        Tidak memiliki kewajiban atau permohonan pembiayaan aktif
                    </li>
                </ul>
            </div>
        </Transition>

        <div class="grid grid-cols-2 gap-6 p-4 border-b">

            <!-- Nomor Anggota -->
            <div class="col-span-1 relative">
                <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300">
                    Nomor Anggota <span class="text-red-500">*</span>
                </label>

                <div v-if="!isAnggotaSelected" class="flex gap-2">
                    <input
                        :value="searchQuery"
                        @input="$emit('update:searchQuery', $event.target.value)"
                        type="text"
                        placeholder="Cari nomor anggota aktif..."
                        :class="[
                            'flex-1 px-4 font-body text-sm py-2.5 border rounded-lg focus:ring-3 shadow-theme-xs focus:outline-hidden',
                            errors?.kode_pengguna
                                ? 'border-red-400 focus:border-red-400 focus:ring-red-500/10'
                                : 'border-gray-300 focus:border-brand-300 focus:ring-brand-500/10'
                        ]"
                        class="dark:bg-dark-900 text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    />
                    <div v-if="isLoadingSearch" class="absolute right-5 top-10">
                        <div class="animate-spin w-5 h-5 border-2 border-primary border-t-transparent rounded-full" />
                    </div>
                </div>

                <div v-else class="flex items-center justify-between bg-light-bg border border-green-200 rounded-lg p-2.5">
                    <p class="text-sm text-green-600">{{ form.anggota.kode_pengguna }}</p>
                    <button class="text-primary" @click="$emit('resetAnggotaSelection')">
                        <span class="icon-[tabler--x]"></span>
                    </button>
                </div>

                <p v-if="errors?.kode_pengguna" class="mt-1 text-xs text-red-500">{{ errors.kode_pengguna }}</p>

                <div v-if="anggotaResults?.length > 0 && !isAnggotaSelected"
                    class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 dark:border-gray-600 border border-gray-300 rounded-lg shadow-lg z-10">
                    <div v-for="anggota in anggotaResults" :key="anggota.id"
                        @click="$emit('selectAnggota', anggota)"
                        class="px-4 py-3 hover:bg-gray-100 hover:dark:bg-gray-700 cursor-pointer border-b last:border-0">
                        <div class="font-medium text-dark-text dark:text-gray-300">{{ anggota.user.nama }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ anggota.user.kode_pengguna }} | {{ anggota.user.email }}</div>
                    </div>
                </div>

                <div v-else-if="searchQuery && !isLoadingSearch && !isAnggotaSelected"
                    class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 border border-gray-300 rounded-lg p-2.5 text-center text-gray-500 z-10">
                    Anggota tidak ditemukan
                </div>
            </div>

            <BaseInputAdmin
                label="Nama Lengkap"
                placeholder="Masukkan nama lengkap"
                v-model="form.anggota.nama"
                required
                :error="errors?.nama"
            />
            <BaseInputAdmin
                label="NIK"
                placeholder="Masukkan NIK (16 digit)"
                v-model="form.anggota.nik"
                max="16"
                required
                :error="errors?.nik"
                inputmode="numeric"
            />
            <BaseInputAdmin
                label="Email"
                placeholder="Masukkan email"
                v-model="form.anggota.email"
                :error="errors?.email"
                type="email"
                @input="onFieldChange('email')"
                @blur="onFieldChange('email')"
            />
            <BaseInputAdmin
                label="Nomor Telepon"
                required
                placeholder="Masukkan nomor telepon"
                max="20"
                v-model="form.anggota.no_telp"
                :error="errors?.no_telp"
                @input="form.anggota.no_telp = normalizePhoneNumber(form.anggota.no_telp, props.onlyNumbers)"
                inputmode="numeric"
            />
            <BaseInputAdmin
                v-model="form.anggota.jenis_kelamin"
                label="Jenis Kelamin"
                type="radio"
                required
                :selectables="[
                    { value: 'Laki-laki', text: 'Laki-laki' },
                    { value: 'Perempuan', text: 'Perempuan' }
                ]"
                :error="errors?.jenis_kelamin"
                @change="onFieldChange('jenis_kelamin')"
            />
            <BaseInputAdmin
                label="Tempat Lahir"
                v-model="form.anggota.tempat_lahir"
                :error="errors?.tempat_lahir"
                placeholder="Masukkan tempat lahir"
                @input="onlyAlpha"
            />
            <BaseInputAdmin
                label="Tanggal Lahir"
                type="date"
                v-model="form.anggota.tgl_lahir"
                :error="errors?.tgl_lahir"
            />
            <BaseInputAdmin
                v-model="form.anggota.alamat_ktp"
                label="Alamat"
                type="textarea"
                placeholder="Masukkan alamat lengkap sesuai KTP"
                rows="4"
                :error="errors?.alamat_ktp"
            />
            <BaseInputAdmin
                v-model="form.anggota.alamat_domisili"
                label="Alamat Domisili"
                type="textarea"
                placeholder="Masukkan alamat domisili"
                rows="4"
                :error="errors?.alamat_domisili"
            />
            <BaseInputAdmin
                v-model="form.anggota.pendidikan_terakhir"
                label="Pendidikan Terakhir"
                type="select"
                :selectables="data.educations.map(unit => ({ value: unit, text: unit }))"
                :error="errors?.pendidikan_terakhir"
            />
            <BaseInputAdmin
                v-model="form.anggota.status_pernikahan"
                label="Status Perkawinan"
                type="select"
                :selectables="data.marriageStatuses.map(unit => ({ value: unit, text: unit }))"
            />
            <BaseInputAdmin
                v-model="form.anggota.jml_tanggungan"
                label="Jumlah Tanggungan Keluarga"
                type="number"
                inputmode="numeric"
                min="0"
                :error="errors?.jml_tanggungan"
            />
        </div>

        <!-- AhliWariss section -->
        <div class="flex flex-col gap-4 w-full p-4 border-b border-gray-200">
            <div class="flex gap-4 w-full items-end">
                <BaseInputAdmin
                    label="Data Ahli Waris"
                    required
                    max="16"
                    placeholder="NIK Ahli Waris"
                    v-model="heirInput.nik_ahli_waris"
                    inputmode="numeric"
                    @input="sanitizeAhliWarisNik"
                />
                <BaseInputAdmin
                    v-model="heirInput.nama_ahli_waris"
                    placeholder="Nama Ahli Waris"
                    @input="sanitizeAhliWarisName"
                />
                <BaseInputAdmin
                    v-model="heirInput.hubungan"
                    type="select"
                    :selectables="data.hubungans.map(unit => ({ value: unit, text: unit }))"
                    placeholder="Hubungan"
                />
                <BaseInputAdmin
                    v-model="heirInput.kontak_ahli_waris"
                    max="20"
                    placeholder="Nomor Kontak"
                    inputmode="numeric"
                    @input="heirInput.kontak_ahli_waris = normalizePhoneNumber(heirInput.kontak_ahli_waris, props.onlyNumbers)"
                />
                <Button variant="primary" @click="$emit('addAhliWaris', heirInput); onFieldChange('ahli_waris')">
                    Tambah
                </Button>
            </div>

            <p v-if="errors?.ahli_waris" class="text-xs text-red-500 -mt-2">{{ errors.ahli_waris }}</p>

            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-gray-400 border-y">
                    <tr>
                        <th class="py-4 text-left pl-6">NIK</th>
                        <th class="py-4 text-right pr-6">Nama</th>
                        <th class="py-4 text-right pr-6">Hubungan</th>
                        <th class="py-4 text-right pr-6">Kontak</th>
                        <th class="py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody v-if="form.anggota.ahli_waris.length > 0">
                    <tr v-for="(item, index) in form.anggota.ahli_waris" :key="index"
                        class="bg-transparent border-b text-dark-text dark:text-gray-300">
                        <td class="py-2 text-left pl-6">{{ item.nik_ahli_waris }}</td>
                        <td class="py-2 text-right pr-6">{{ item.nama_ahli_waris }}</td>
                        <td class="py-2 text-right pr-6">{{ item.hubungan }}</td>
                        <td class="py-2 text-right pr-6">{{ item.kontak_ahli_waris }}</td>
                        <td class="py-2 text-center flex justify-center">
                            <Button size="small" variant="light" @click="$emit('removeAhliWaris', index)">-</Button>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr class="bg-transparent border-b text-dark-text">
                        <td colspan="5" class="py-4 text-center text-gray-400">Belum ada data ahli waris</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
