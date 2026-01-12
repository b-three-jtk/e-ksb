<script setup>
import { router } from '@inertiajs/vue3'
import Base from '../../../Layouts/Base.vue';
import ReadonlyField from '@/Components/Form/ReadonlyField.vue'

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
})
</script>

<template>
    <Base title="Profil Anggota">
        <div class="min-h-screen bg-gray-50 pt-20 pb-12">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold text-blue-900">Profil Anggota</h1>
                </div>

                <div class="bg-white rounded-lg shadow-md p-8">
                    <div class="flex flex-col sm:flex-row items-center gap-6 pb-8 border-b border-gray-200">
                        <div class="relative flex-shrink-0">
                            <img
                                :src="user.photo_url || '/images/default-avatar.png'"
                                :alt="user.name"
                                class="w-32 h-32 rounded-full object-cover border-4 border-gray-200"
                            />
                        </div>
                        <div class="w-full sm:w-auto ml-auto text-center sm:text-right self-end">
                            <button
                                @click="router.visit(`/user/profile/${user.member_number}/edit`)"
                                class="px-6 py-2.5 bg-orange-500 text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition-colors"
                            >
                                Edit Profile
                            </button>
                        </div>
                    </div>

                    <div class="mt-12">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <ReadonlyField label="Nama Anggota" :model-value="user.name || '-'" />
                            <ReadonlyField label="NIK" :model-value="user.nik || '-'" />
                            <ReadonlyField label="Tanggal Lahir" :model-value="user.birth_date || '-'" />
                            <ReadonlyField label="Jenis Kelamin" :model-value="user.gender || '-'" />
                            <ReadonlyField label="Unit Kerja" :model-value="user.work_unit || '-'" />
                            <ReadonlyField label="Nama Lembaga" :model-value="user.institution || '-'" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Base>
</template>
