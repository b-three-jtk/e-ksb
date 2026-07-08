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
                <Info label="Nomor Anggota KSB" :value="data.anggota.kode_pengguna" />
                <Info label="Nama Lengkap" :value="data.anggota.name" />
                <Info label="NIK" :value="data.anggota.nik" />
                <Info label="Jenis Kelamin" :value="data.anggota.jenis_kelamin" />
                <Info label="Tempat, Tanggal Lahir" :value="`${data.anggota.tempat_lahir}, ${data.anggota.tgl_lahir}`" />
                <Info label="Alamat " :value="data.anggota.alamat_ktp" />
                <Info label="Alamat Domisili" :value="data.anggota.alamat_domisili" />
                <Info label="Pendidikan Terakhir" :value="data.anggota.pendidikan_terakhir" />
                <Info label="Status Perkawinan" :value="data.anggota.status_pernikahan" />
                <Info label="Jumlah Tanggungan Keluarga" :value="data.anggota.jml_tanggungan" />
                <Info label="Nomor Telepon" :value="data.anggota.no_telp" />
                <Info label="Email" :value="data.anggota.email" />
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
                <tbody v-if="data.anggota.ahli_waris.length > 0">
                    <tr v-for="(item, index) in data.anggota.ahli_waris" :key="index"
                        class="bg-white border-b text-dark-text dark:text-gray-300 dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-2 text-left pl-6">{{ item.nama_ahli_waris }}</td>
                        <td class="py-2 text-left pl-6">{{ item.nik_ahli_waris }}</td>
                        <td class="py-2 text-left pl-6">{{ item.hubungan }}</td>
                        <td class="py-2 text-left pl-6">{{ item.kontak_ahli_waris }}</td>
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
