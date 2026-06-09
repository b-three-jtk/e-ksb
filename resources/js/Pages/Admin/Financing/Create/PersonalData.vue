<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Button from '@/Components/Form/Button.vue'
import { ref } from 'vue'

const props = defineProps({
    form: Object,
    searchQuery: String,
    isLoadingSearch: Boolean,
    isMemberSelected: Boolean,
    memberResults: Array,
    data: Object,
    errors: Object,
})

const emit = defineEmits(['update:searchQuery', 'selectMember', 'addHeir', 'removeHeir', 'resetSearch'])

const heirInput = ref({
    heir_nik: '',
    heir_name: '',
    relationship: '',
    heir_contact: '',
})

console.log('ini member:', props.form.member);

// Validator functions
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
</script>

<template>
    <section>
        <div class="border-b border-gray-200 px-8 pb-4">
            <h1 class="card-title">Identitas Pribadi & Ahli Waris</h1>
        </div>
        <!-- kalau dia gak eligible -->
        <Transition name="fade"
            v-if="form.member.is_have_eligible_saving === false || form.member.is_have_no_obligation === false"
            class="bg-yellow-100 mx-4 mt-4 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg relative">
            <div class="flex flex-col gap-2">
                <p>Pemohon tidak memenuhi syarat mengajukan pembiayaan murabahah:</p>
                <ul class="list-disc list-inside mt-2">
                    <li v-if="form.member.is_have_eligible_saving === false">
                        Memiliki tabungan anggota yang sudah berjalan selama 1 bulan
                    </li>
                    <li v-if="form.member.is_have_no_obligation === false">
                        Tidak memiliki kewajiban atau permohonan pembiayaan aktif
                    </li>
                </ul>
            </div>
        </Transition>

        <div class="grid grid-cols-2 gap-6 p-4 border-b">
            <!-- Member search input -->
            <div class="col-span-1 relative">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nomor Anggota <span class="text-red-500">*</span>
                </label>

                <div v-if="!isMemberSelected" class="flex gap-2">
                    <input :value="searchQuery" @input="$emit('update:searchQuery', $event.target.value)" type="text"
                        placeholder="Cari nomor anggota aktif..."
                        class="flex-1 px-4 font-body text-sm py-2.5 border border-gray-300 rounded-lg focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 shadow-theme-xs focus:outline-hidden" />

                    <!-- Loading indicator -->
                    <div v-if="isLoadingSearch" class="absolute right-5 top-10">
                        <div class="animate-spin w-5 h-5 border-2 border-primary border-t-transparent rounded-full">
                        </div>
                    </div>
                </div>

                <!-- Selected member display -->
                <div v-else
                    class="flex items-center justify-between bg-light-bg border border-green-200 rounded-lg p-2.5">
                    <div>
                        <p class="text-sm text-green-600">{{ form.member.user_code }}</p>
                    </div>
                    <button class="text-primary" @click="$emit('resetMemberSelection')">
                        <span class="icon-[tabler--x]"></span>
                    </button>
                </div>

                <!-- Search results dropdown -->
                <div v-if="memberResults.length > 0 && !isMemberSelected"
                    class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg z-10">
                    <div v-for="member in memberResults" :key="member.id" @click="$emit('selectMember', member)"
                        class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b last:border-0">
                        <div class="font-medium text-dark-text">{{ member.user.name }}</div>
                        <div class="text-sm text-gray-500">{{ member.user.user_code }} | {{ member.user.email }}</div>
                    </div>
                </div>

                <!-- No results message -->
                <div v-else-if="searchQuery && !isLoadingSearch && !isMemberSelected"
                    class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg p-2.5 text-center text-gray-500 z-10">
                    Anggota tidak ditemukan
                </div>
            </div>

            <!-- Form fields -->
            <BaseInputAdmin label="Nama Lengkap" placeholder="Masukkan nama lengkap" v-model="form.member.name" required
                :errors="errors.name" @input="onlyAlpha" />
            <BaseInputAdmin label="NIK" placeholder="Masukkan NIK" v-model="form.member.nik" max="16" required
                :errors="errors.nik" @input="onlyNumbers" inputmode="numeric" />
            <BaseInputAdmin label="Email" placeholder="Masukkan email" v-model="form.member.email" required
                :errors="errors.email" type="email" />
            <BaseInputAdmin label="Nomor Telepon" required placeholder="Masukkan nomor telepon" max="13"
                v-model="form.member.phone_number" :errors="errors.phone_number" @input="onlyNumbers"
                inputmode="numeric" />
            <BaseInputAdmin v-model="form.member.gender" label="Jenis Kelamin" type="radio" required :selectables="[
                { value: 'Laki-laki', text: 'Laki-laki' },
                { value: 'Perempuan', text: 'Perempuan' }
            ]" :error="errors.gender">
            </BaseInputAdmin>
            <BaseInputAdmin label="Tempat Lahir" v-model="form.member.birth_place" :error="errors.birth_place"
                placeholder="Masukkan tempat lahir" @input="onlyAlpha" />
            <BaseInputAdmin label="Tanggal Lahir" type="date" v-model="form.member.birth_date"
                :error="errors.birth_date" />
            <BaseInputAdmin v-model="form.member.residential_address" label="Alamat" type="textarea"
                placeholder="Masukkan alamat lengkap sesuai KTP" rows="4" :error="errors.residential_address"
                @input="onlyAlphaNumericDash" />
            <BaseInputAdmin v-model="form.member.domicile_address" label="Alamat Domisili" type="textarea"
                placeholder="Masukkan alamat domisili" rows="4" :error="errors.domicile_address"
                @input="onlyAlphaNumericDash" />
            <BaseInputAdmin v-model="form.member.last_education" label="Pendidikan Terakhir" type="select"
                :selectables="data.educations.map(unit => ({ value: unit, text: unit }))"
                :error="errors.last_education" />
            <BaseInputAdmin v-model="form.member.marital_status" label="Status Perkawinan" type="select"
                :selectables="data.marriageStatuses.map(unit => ({ value: unit, text: unit }))" />
            <BaseInputAdmin v-model="form.member.dependents" label="Jumlah Tanggungan Keluarga" type="number"
                @input="onlyNumbers" inputmode="numeric" min="0" />
        </div>

        <!-- Heirs section -->
        <div class="flex flex-col gap-4 w-full p-4 border-b border-gray-200">
            <div class="flex gap-4 w-full items-end">
                <BaseInputAdmin label="Data Ahli Waris" required max="16" pattern="[0-9]{16}"
                    placeholder="Masukkan NIK Ahli Waris" v-model="heirInput.heir_nik" @input="onlyNumbers"
                    inputmode="numeric" />
                <BaseInputAdmin v-model="heirInput.heir_name" placeholder="Nama Ahli Waris" @input="onlyAlpha" />
                <BaseInputAdmin v-model="heirInput.relationship" type="select"
                    :selectables="data.relationships.map(unit => ({ value: unit, text: unit }))"
                    placeholder="Hubungan dengan anggota" />
                <BaseInputAdmin v-model="heirInput.heir_contact" max="20" placeholder="Nomor Kontak"
                    @input="onlyNumbers" inputmode="numeric" />
                <Button variant="primary" @click="$emit('addHeir', heirInput)">
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
                <tbody v-if="form.member.heirs.length > 0">
                    <tr v-for="(item, index) in form.member.heirs" :key="index"
                        class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <td class="py-2 text-left pl-6">{{ item.heir_nik }}</td>
                        <td class="py-2 text-right pr-6">{{ item.heir_name }}</td>
                        <td class="py-2 text-right pr-6">{{ item.relationship }}</td>
                        <td class="py-2 text-right pr-6">{{ item.heir_contact }}</td>
                        <td class="py-2 text-center flex justify-center">
                            <Button size="small" variant="light" @click="$emit('removeHeir', index)">
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
    </section>
</template>
