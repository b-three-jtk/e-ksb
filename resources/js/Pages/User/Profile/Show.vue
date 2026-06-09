<script setup>
import { router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Base from '../../../Layouts/Base.vue';
import Button from '@/Components/Form/Button.vue'
import EyeIcon from '@/Icons/EyeIcon.vue'
import UserIcon from '@/Icons/UserIcon.vue'
import ChangePasswordModal from './ChangePasswordModal.vue'

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
})

const user = computed(() => props.user || {})
const member = computed(() => user.value.member || {})
const points = computed(() => user.value.points || { summary: {}, history: [] })
const photoUrl = computed(() => user.value.photo_url || (user.value.profile_picture ? `/storage/${user.value.profile_picture}` : null))
const documents = computed(() => member.value.documents || {})

const isModalOpen = ref(false)
const isPhotoPreviewOpen = ref(false)
const previewDocument = ref({ title: '', url: '', kind: 'image' })

const openPasswordModal = () => {
    isModalOpen.value = true
}

const closePasswordModal = () => {
    isModalOpen.value = false
}

const openPhotoPreview = () => {
    if (photoUrl.value) {
        previewDocument.value = { title: 'Foto Profil', url: photoUrl.value, kind: 'image' }
        isPhotoPreviewOpen.value = true
    }
}

const openDocumentPreview = (title, url) => {
    if (!url) return

    const kind = /\.pdf($|\?)/i.test(url) ? 'pdf' : 'image'
    previewDocument.value = { title, url, kind }
    isPhotoPreviewOpen.value = true
}

const closePhotoPreview = () => {
    isPhotoPreviewOpen.value = false
    previewDocument.value = { title: '', url: '', kind: 'image' }
}

const displayValue = (value) => value ?? '-'

const identityFields = computed(() => [
    { label: 'NIK', value: displayValue(user.value.nik) },
    { label: 'Jenis Kelamin', value: displayValue(member.value.gender) },
    { label: 'Tanggal Lahir', value: displayValue(member.value.birth_date) },
    { label: 'Tempat Lahir', value: displayValue(member.value.birth_place) },
    { label: 'Pendidikan Terakhir', value: displayValue(member.value.last_education) },
    { label: 'Status Pernikahan', value: displayValue(member.value.marital_status) },
])

const contactFields = computed(() => [
    { label: 'Nomor Telepon', value: displayValue(user.value.phone_number) },
    { label: 'Email', value: displayValue(user.value.email) },
    { label: 'Alamat Sesuai KTP', value: displayValue(member.value.domicile_address) },
    { label: 'Alamat Domisili', value: displayValue(member.value.residential_address) },
])

const heirRows = computed(() => member.value.heirs || [])

const documentFields = computed(() => [
    { label: 'KTP', title: 'KTP', url: documents.value.ktp || null },
    { label: 'KK', title: 'KK', url: documents.value.kk || null },
])

const hasDocument = (url) => Boolean(url)

const pointSummary = computed(() => points.value.summary || {})
const pointHistory = computed(() => points.value.history || [])

const formatCurrency = (value) => {
    const numericValue = Number(value ?? 0)

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number.isFinite(numericValue) ? numericValue : 0)
}

const formatPoint = (value) => `${Number(value ?? 0).toLocaleString('id-ID')}`
</script>

<template>
    <Base title="Profil Anggota">
        <div class="min-h-screen bg-[linear-gradient(180deg,#f8fafc_0%,#ffffff_100%)] pt-24 pb-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 mt-2">
                    <h1 class="text-3xl font-bold text-[#007031]">Profil Anggota</h1>
                </div>

                <div class="card-layout flex flex-col xl:flex-row justify-between gap-4 items-center">
                    <div class="flex flex-col xl:flex-row justify-center items-center text-center xl:text-left gap-6">
                        <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-100">
                            <img
                                v-if="photoUrl"
                                :src="photoUrl"
                                :alt="user.name || 'Profil anggota'"
                                class="h-full w-full object-cover"
                            >
                            <div v-else class="flex h-full w-full items-center justify-center text-slate-400">
                                <UserIcon class="h-10 w-10" />
                            </div>
                        </div>
                        <div class="flex flex-col justify-center gap-1">
                            <div class="flex gap-2 items-center flex-wrap">
                                <h1 class="card-title">{{ user.name || '-' }}</h1>
                            </div>
                            <p class="text-gray-500">
                                {{ user.user_code || '-' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <button
                            @click="router.visit('/user/profile/edit')"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-gray-400 px-5 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-gray-500 dark:border-gray-700 dark:hover:text-gray-200"
                        >
                            Edit Profil
                        </button>
                        <button
                            @click="openPasswordModal"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-[#008e43] px-5 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-[#00783a] dark:border-gray-700 dark:hover:text-gray-200"
                        >
                            Ubah Password
                        </button>
                    </div>
                </div>

                <div class="card-layout grid gap-5">
                    <div class="card-layout py-0! grid xl:grid-cols-2 grid-cols-1">
                        <div class="grid grid-cols-1 gap-8 py-6">
                            <h1 class="card-title">Identitas Pribadi</h1>
                            <ul class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                                <li v-for="field in identityFields" :key="field.label" class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">{{ field.label }}</span>
                                    <span class="font-medium text-dark-text dark:text-white">{{ field.value }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="flex flex-col gap-8 border-t-2 border-t-stroke xl:border-0 xl:border-l-2 xl:border-l-stroke xl:dark:border-l-gray-700 xl:pl-8 py-6">
                            <h1 class="card-title">Berkas Pendukung</h1>
                            <div class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                                <div
                                    v-for="field in documentFields"
                                    :key="field.label"
                                    class="rounded-2xl border border-stroke bg-white p-5 shadow-sm"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-sm text-gray-500 dark:text-gray-300">{{ field.label }}</span>
                                        </div>
                                    </div>

                                    <div class="mt-5">
                                        <Button
                                            variant="gray"
                                            :disabled="!field.url"
                                            @click="openDocumentPreview(field.title, field.url)"
                                        >
                                            <EyeIcon />
                                            Lihat
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-layout py-0! grid xl:grid-cols-2 grid-cols-1">
                        <div class="grid grid-cols-1 gap-8 py-6">
                            <h1 class="card-title">Kontak dan Alamat</h1>
                            <ul class="grid xl:grid-cols-2 grid-cols-1 gap-6">
                                <li v-for="field in contactFields" :key="field.label" class="flex flex-col gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-300">{{ field.label }}</span>
                                    <span class="font-medium text-dark-text dark:text-white whitespace-pre-line">{{ field.value }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="flex flex-col gap-8 border-t-2 border-t-stroke xl:border-0 xl:border-l-2 xl:border-l-stroke xl:dark:border-l-gray-700 xl:pl-8 py-6">
                            <h1 class="card-title">Ahli Waris</h1>
                            <div v-if="heirRows.length" class="grid gap-4">
                                <div v-for="heir in heirRows" :key="heir.heir_nik" class="grid md:grid-cols-2 gap-4 items-start rounded-2xl border border-stroke p-4 bg-white">
                                    <div>
                                        <span class="text-sm text-gray-500">Nama Ahli Waris</span>
                                        <div class="font-medium text-dark-text text-lg">{{ heir.heir_name }}</div>

                                        <span class="text-sm text-gray-500 mt-4 block">Kontak Ahli Waris</span>
                                        <div class="font-medium text-dark-text">{{ heir.heir_contact || '-' }}</div>
                                    </div>

                                    <div>
                                        <span class="text-sm text-gray-500">Hubungan Keluarga</span>
                                        <div class="font-medium text-dark-text text-lg">{{ heir.relationship }}</div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="rounded-2xl border border-dashed border-stroke px-4 py-5 text-sm text-gray-500">
                                Belum ada data ahli waris.
                            </p>
                        </div>
                    </div>

                    <div class="card-layout grid gap-5">
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <h1 class="card-title">Riwayat Poin</h1>
                        </div>

                        <div class="grid gap-5 xl:grid-cols-[320px_minmax(0,1fr)]">
                            <div class="rounded-2xl border border-stroke bg-white p-5 shadow-sm">
                                <div class="flex h-full flex-col justify-between gap-6">
                                    <div>
                                        <p class="text-sm text-gray-500">Total Poin</p>
                                        <div class="mt-2 text-5xl font-semibold leading-none text-[#00a04a]">
                                            {{ formatPoint(pointSummary.total_points) }}
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p class="text-gray-500">Terakhir diperbarui</p>
                                            <p class="mt-1 font-medium text-dark-text dark:text-white">
                                                {{ pointSummary.latest_calculated_at || '-' }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Poin Terakhir</p>
                                            <p class="mt-1 font-medium text-dark-text dark:text-white">
                                                +{{ formatPoint(pointSummary.latest_points_earned) }} Poin
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Total Simpanan Terakhir</p>
                                            <p class="mt-1 font-medium text-dark-text dark:text-white">
                                                {{ formatCurrency(pointSummary.latest_total_simpanan) }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Tanggal Pendapatan Poin</p>
                                            <p class="mt-1 font-medium text-dark-text dark:text-white">
                                                {{ pointSummary.latest_calculated_at || '-' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-2xl border border-stroke bg-white shadow-sm">
                                <div class="border-b border-stroke px-5 py-4">
                                    <h2 class="text-base font-semibold text-dark-text dark:text-white">Riwayat Poin Saya</h2>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-stroke text-left text-sm">
                                        <thead class="bg-gray-50">
                                            <tr class="text-gray-500">
                                                <th class="px-5 py-3 font-medium">Tanggal</th>
                                                <th class="px-5 py-3 font-medium">Total Simpanan</th>
                                                <th class="px-5 py-3 font-medium">Poin Diperoleh</th>
                                                <th class="px-5 py-3 font-medium">Total Poin</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-stroke">
                                            <tr v-if="pointHistory.length === 0">
                                                <td colspan="4" class="px-5 py-8 text-center text-gray-500">
                                                    Belum ada riwayat poin.
                                                </td>
                                            </tr>
                                            <tr v-for="row in pointHistory" :key="row.id" class="hover:bg-gray-50/70">
                                                <td class="px-5 py-4 font-medium text-dark-text dark:text-white">{{ row.calculation_date }}</td>
                                                <td class="px-5 py-4 text-dark-text dark:text-white">{{ formatCurrency(row.total_simpanan) }}</td>
                                                <td class="px-5 py-4 font-medium text-[#00a04a]">+{{ formatPoint(row.points_earned) }}</td>
                                                <td class="px-5 py-4 font-medium text-dark-text dark:text-white">{{ formatPoint(row.total_points) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Transition name="fade">
            <div v-if="isPhotoPreviewOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="closePhotoPreview">
                <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h4 class="text-base font-semibold text-slate-900">{{ previewDocument.title || 'Pratinjau Dokumen' }}</h4>
                            <p class="text-sm text-slate-500">Pratinjau dokumen anggota</p>
                        </div>
                        <button type="button" @click="closePhotoPreview" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 10-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </button>
                    </div>
                    <div class="bg-slate-50 p-5">
                        <img
                            v-if="previewDocument.url && previewDocument.kind === 'image'"
                            :src="previewDocument.url"
                            :alt="previewDocument.title"
                            class="mx-auto max-h-[70vh] rounded-xl object-contain"
                        >
                        <iframe
                            v-else-if="previewDocument.url && previewDocument.kind === 'pdf'"
                            :src="previewDocument.url"
                            class="h-[70vh] w-full rounded-xl bg-white"
                        ></iframe>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Change Password Modal Component -->
        <ChangePasswordModal :is-open="isModalOpen" @close="closePasswordModal" />
    </Base>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.18s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
