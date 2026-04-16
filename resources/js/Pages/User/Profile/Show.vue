<script setup>
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import Base from '../../../Layouts/Base.vue';
import ReadonlyField from '@/Components/Form/ReadonlyField.vue'
import UserIcon from '@/Icons/UserIcon.vue'
import ChangePasswordModal from './ChangePasswordModal.vue'

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
})

const isModalOpen = ref(false)

const openPasswordModal = () => {
    isModalOpen.value = true
}

const closePasswordModal = () => {
    isModalOpen.value = false
}
</script>

<template>
    <Base title="Profil Anggota">
        <div class="min-h-screen bg-white dark:bg-gray-900 pt-20 pb-12">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mt-24 mb-8">
                    <h1 class="text-3xl font-bold font-head text-blue-900 dark:text-blue-600">Profil Anggota</h1>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
                    <div class="flex flex-col sm:flex-row items-center gap-6 pb-8 border-b border-gray-200">
                        <div class="relative flex-shrink-0">
                            <div v-if="user.photo_url" class="w-32 h-32 rounded-full overflow-hidden border-4 border-gray-200">
                                <img
                                    :src="user.photo_url"
                                    :alt="user.name"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div v-else class="w-32 h-32 rounded-full bg-gray-200 border-4 border-gray-200 flex items-center justify-center">
                                <UserIcon class="w-16 h-16 text-gray-400" style="width: 64px; height: 64px;" />
                            </div>
                        </div>
                        <div class="w-full sm:w-auto ml-auto text-center sm:text-right self-end flex gap-3 flex-col sm:flex-row">
                            <button
                                @click="router.visit('/user/profile/edit')"
                                class="px-6 py-2.5 bg-orange-500 text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition-colors"
                            >
                                Edit Profil
                            </button>
                            <button
                                @click="openPasswordModal"
                                class="px-6 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors"
                            >
                                Ubah Password
                            </button>
                        </div>
                    </div>

                    <div class="mt-12">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <ReadonlyField label="Nama Anggota" :model-value="user.name || '-'" />
                            <ReadonlyField label="NIK" :model-value="user.nik || '-'" />
                            <ReadonlyField label="Tanggal Lahir" :model-value="user.birth_date || '-'" />
                            <ReadonlyField label="Jenis Kelamin" :model-value="user.gender || '-'" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password Modal Component -->
        <ChangePasswordModal :is-open="isModalOpen" @close="closePasswordModal" />
    </Base>
</template>
