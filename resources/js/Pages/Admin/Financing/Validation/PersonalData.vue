<script setup>
import Info from '@/Components/Form/Info.vue'

defineProps({
    data: Object,
})
</script>

<template>
    <div class="gap-6 flex flex-col">
        <div class="card-layout">
            <h1 class="card-title">Identitas Pemohon</h1>
            <div class="grid grid-cols-2 gap-6 mt-8">
                <Info label="Nomor Anggota KSB" :value="data.member.user_code" />
                <Info label="Nama Lengkap" :value="data.member.name" />
                <Info label="NIK" :value="data.member.nik" />
                <Info label="Jenis Kelamin" :value="data.member.gender" />
                <Info label="Tempat, Tanggal Lahir" :value="`${data.member.birth_place}, ${data.member.birth_date}`" />
                <Info label="Alamat " :value="data.member.residential_address" />
                <Info label="Alamat Domisili" :value="data.member.domicile_address" />
                <Info label="Pendidikan Terakhir" :value="data.member.last_education" />
                <Info label="Status Perkawinan" :value="data.member.marital_status" />
                <Info label="Jumlah Tanggungan Keluarga" :value="data.member.dependents" />
                <Info label="Nomor Telepon" :value="data.member.phone_number" />
                <Info label="Email" :value="data.member.email" />
            </div>
        </div>
        <div class="card-layout">
            <h1 class="card-title">Ahli Waris</h1>
            <table class="w-full text-sm text-left mt-8 text-gray-500 dark:text-gray-400">
                <thead class="text-gray-400 border-y dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="py-4 text-left pl-6">Nama Ahli Waris</th>
                        <th class="py-4 text-left pl-6">NIK</th>
                        <th class="py-4 text-left pl-6">Hubungan Keluarga</th>
                        <th class="py-4 text-left pl-6">Kontak</th>
                    </tr>
                </thead>
                <tbody v-if="data.member.heirs.length > 0">
                    <tr v-for="(item, index) in data.member.heirs" :key="index"
                        class="bg-white border-b text-dark-text dark:text-gray-300 dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-2 text-left pl-6">{{ item.heir_name }}</td>
                        <td class="py-2 text-left pl-6">{{ item.heir_nik }}</td>
                        <td class="py-2 text-left pl-6">{{ item.relationship }}</td>
                        <td class="py-2 text-left pl-6">{{ item.heir_contact }}</td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="3" class="py-2 text-center">Tidak ada data ahli waris</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
