<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Button from '@/Components/Form/Button.vue'
import { ref } from 'vue'

defineProps({
    form: Object,
    searchQuery: String,
    isLoadingSearch: Boolean,
    isMemberSelected: Boolean,
    filteredMembers: Array,
    educations: Array,
    relationships: Array,
    errors: Object,
})

const emit = defineEmits(['update:searchQuery', 'selectMember', 'addHeir', 'removeHeir', 'resetSearch'])

const heirInput = ref({
    nik: '',
    name: '',
    relationship: '',
    contact: '',
})
</script>

<template>
    <section>
        <div class="border-b border-gray-200 px-8 pb-4">
            <h1 class="card-title">Identitas Pribadi & Ahli Waris</h1>
        </div>
        <div class="grid grid-cols-2 gap-6 p-4 border-b">
            <!-- Member search input -->
            <div class="col-span-2 relative">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Cari Anggota <span class="text-red-500">*</span>
                </label>

                <div v-if="!isMemberSelected" class="flex gap-2">
                    <input
                        :value="searchQuery"
                        @input="$emit('update:searchQuery', $event.target.value)"
                        type="text"
                        placeholder="Cari berdasarkan nama atau nomor anggota..."
                        class="flex-1 px-4 font-body py-2 border border-gray-300 rounded-lg focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 shadow-theme-xs focus:outline-hidden"
                    />

                    <!-- Loading indicator -->
                    <div v-if="isLoadingSearch" class="absolute right-12 top-10">
                        <div class="animate-spin w-5 h-5 border-2 border-primary border-t-transparent rounded-full"></div>
                    </div>
                </div>

                <!-- Selected member display -->
                <div v-else class="flex items-center justify-between bg-light-bg border border-green-200 rounded-lg p-4">
                    <div>
                        <p class="text-green-700 font-medium">{{ form.member.name }}</p>
                        <p class="text-sm text-green-600">{{ form.member.member_code }}</p>
                    </div>
                    <Button
                        variant="secondary"
                        size="small"
                        @click="$emit('resetMemberSelection')"
                    >
                        Ubah Anggota
                    </Button>
                </div>

                <!-- Search results dropdown -->
                <div v-if="filteredMembers.length > 0 && !isMemberSelected"
                    class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg z-10">
                    <div v-for="member in filteredMembers" :key="member.id" @click="$emit('selectMember', member)"
                        class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b last:border-0">
                        <div class="font-medium text-dark-text">{{ member.name }}</div>
                        <div class="text-sm text-gray-500">{{ member.member_code }} • {{ member.email }}</div>
                    </div>
                </div>

                <!-- No results message -->
                <div v-else-if="searchQuery && !isLoadingSearch && !isMemberSelected"
                    class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg p-4 text-center text-gray-500 z-10">
                    Anggota tidak ditemukan
                </div>
            </div>

            <!-- Form fields -->
            <BaseInputAdmin
                label="Nomor Anggota"
                placeholder="Masukkan nomor anggota"
                max="10"
                :model-value="form.member.member_code"
                :isDisabled="true"
                required
                :errors="errors.member_code"
            />
            <BaseInputAdmin
                label="Nama Lengkap"
                placeholder="Masukkan nama lengkap"
                :model-value="form.member.name"
                required
                :errors="errors.name"
            />
            <BaseInputAdmin
                label="NIK"
                placeholder="Masukkan NIK"
                :model-value="form.member.nik"
                max="16"
                required
                :errors="errors.nik"
            />
            <BaseInputAdmin
                label="Email"
                placeholder="Masukkan email"
                :model-value="form.member.email"
                required
                :errors="errors.email"
            />
            <BaseInputAdmin
                label="Nomor Telepon"
                placeholder="Masukkan nomor telepon"
                max="13"
                :model-value="form.member.phone_number"
                :errors="errors.phone_number"
            />
            <BaseInputAdmin
                :model-value="form.member.gender"
                label="Jenis Kelamin"
                type="radio"
                :selectables="[
                    { value: 'Laki-laki', text: 'Laki-laki' },
                    { value: 'Perempuan', text: 'Perempuan' }
                ]"
                :error="errors.gender"
            />
            <BaseInputAdmin
                label="Tempat Lahir"
                :model-value="form.member.birth_place"
                :error="errors.birth_place"
                placeholder="Masukkan tempat lahir"
            />
            <BaseInputAdmin
                label="Tanggal Lahir"
                type="date"
                :model-value="form.member.birth_date"
                :error="errors.birth_date"
            />
            <BaseInputAdmin
                :model-value="form.member.address"
                label="Alamat"
                type="textarea"
                placeholder="Masukkan alamat lengkap sesuai KTP"
                rows="4"
                :error="errors.address"
            />
            <BaseInputAdmin
                :model-value="form.member.residential_address"
                label="Alamat Domisili"
                type="textarea"
                placeholder="Masukkan alamat domisili"
                rows="4"
                :error="errors.residential_address"
            />
            <BaseInputAdmin
                :model-value="form.member.last_education"
                label="Pendidikan Terakhir"
                type="select"
                :selectables="educations.map(unit => ({ value: unit, text: unit }))"
                :error="errors.last_education"
            />
        </div>

        <!-- Heirs section -->
        <div class="flex flex-col gap-4 w-full p-4 border-b border-gray-200">
            <div class="flex gap-4 w-full items-end">
                <BaseInputAdmin
                    label="Data Ahli Waris"
                    max="16"
                    pattern="[0-9]{16}"
                    placeholder="Masukkan NIK Ahli Waris"
                    v-model="heirInput.nik"
                />
                <BaseInputAdmin
                    v-model="heirInput.name"
                    placeholder="Nama Ahli Waris"
                />
                <BaseInputAdmin
                    v-model="heirInput.relationship"
                    type="select"
                    :selectables="relationships.map(unit => ({ value: unit, text: unit }))"
                    placeholder="Hubungan dengan anggota"
                />
                <BaseInputAdmin
                    v-model="heirInput.contact"
                    placeholder="Nomor Kontak"
                />
                <Button
                    variant="primary"
                    @click="$emit('addHeir', heirInput)"
                >
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
                        <td class="py-2 text-left pl-6">{{ item.nik }}</td>
                        <td class="py-2 text-right pr-6">{{ item.name }}</td>
                        <td class="py-2 text-right pr-6">{{ item.relationship }}</td>
                        <td class="py-2 text-right pr-6">{{ item.contact }}</td>
                        <td class="py-2 text-center flex justify-center">
                            <Button
                                size="small"
                                variant="light"
                                @click="$emit('removeHeir', index)"
                            >
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
