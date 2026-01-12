<script setup>
import Base from '@/Layouts/Base.vue'
import ReadonlyField from '@/Components/Form/ReadonlyField.vue'
import { ref, watch } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

const uploadedFile = ref(null)
const isAgreed = ref(false)
const isConfirmed = ref(false)
const fileInput = ref(null)
const errorFile = ref(null)
const showSuccessModal = ref(false)
const showErrorModal = ref(false)
const showAlreadySubmittedModal = ref(false)
const errorMessage = ref('')

const page = usePage()
const memberData = page.props.member
const hasExistingResign = page.props.has_existing_resign || false

const formatRupiah = (value) => {
    return 'Rp ' + Number(value ?? 0).toLocaleString('id-ID')
}

const MAX_SIZE = 2 * 1024 * 1024

const validateFile = (file) => {
    if (!['application/pdf',
          'application/msword',
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ].includes(file.type)) {
        errorFile.value = 'File harus PDF atau DOC'
        return false
    }

    if (file.size > MAX_SIZE) {
        errorFile.value = 'Ukuran file maksimal 2 MB'
        return false
    }

    errorFile.value = null
    return true
}

const handleFileUpload = (event) => {
    const file = event.target.files[0]
    if (!file) return

    if (!validateFile(file)) {
        event.target.value = null
        return
    }

    uploadedFile.value = file
}

const removeFile = () => {
    uploadedFile.value = null
    errorFile.value = null

    if (fileInput.value) {
        fileInput.value.value = null
    }
}

const handleDrop = (event) => {
    event.preventDefault()
    const file = event.dataTransfer.files[0]
    if (!file) return

    if (!validateFile(file)) return

    uploadedFile.value = file
}

const handleDragOver = (event) => {
    event.preventDefault();
}

const submitResignation = () => {
    if (hasExistingResign) {
        errorMessage.value = 'Permohonan pengunduran diri sudah pernah diajukan. Harap menunggu peninjauan dari petugas koperasi.'
        showAlreadySubmittedModal.value = true
        return
    }

    if (!uploadedFile.value || !isAgreed.value || !isConfirmed.value) {
        alert('Harap lengkapi semua persyaratan')
        return
    }

    const formData = new FormData()
    formData.append('document', uploadedFile.value)

    router.post(
        `/user/resign/${memberData.member_number}`,
        formData,
        {
            forceFormData: true,
            preserveScroll: true,
            preserveState : true,
            onSuccess: (page) => {
                showSuccessModal.value = true
                uploadedFile.value = null
                isAgreed.value = false
                isConfirmed.value = false
                if (fileInput.value) fileInput.value.value = null
            },
            onError: (errors) => {
                if (errors.resign) {
                    errorMessage.value = errors.resign
                    showAlreadySubmittedModal.value = true
                }
                else if (errors.document) {
                    errorFile.value = errors.document
                }
                else {
                    const errorKeys = Object.keys(errors)
                    if (errorKeys.length > 0) {
                        errorMessage.value = errors[errorKeys[0]]
                    } else {
                        errorMessage.value = 'Terjadi kesalahan saat mengirim permohonan'
                    }
                    showErrorModal.value = true
                }
            }
        }
    )
}

watch(
    () => page.props.flash,
    (newFlash) => {
        if (newFlash?.success) {
            showSuccessModal.value = true
        }
    },
    { deep: true }
)

</script>

<template>
    <Base title="Pengunduran Diri Anggota">
        <div class="font-body min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8 dark:bg-gray-900 transition-colors">
            <div class="max-w-5xl mx-auto border rounded-xl mt-20 p-10 dark:bg-gray-800 shadow-md overflow-hidden">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="font-head text-2xl font-bold text-gray-900 mb-2 dark:text-gray-100">
                        PENGUNDURAN DIRI ANGGOTA KOPERASI
                    </h1>
                    <p class="text-gray-600 dark:text-gray-300">
                        Periksa kembali ringkasan informasi keanggotaan Anda dan lampirkan
                        permohonan pengunduran diri untuk proses lebih lanjut.
                    </p>
                </div>

                <!-- Divider -->
                <div class="border-b p-0 m-0 border-gray-300"></div>

                <!-- Informasi Anggota -->
                <div class="mb-8 mt-15">
                    <h2 class="font-head text-lg font-semibold text-gray-900 mb-4 dark:text-gray-100">Informasi Anggota</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <ReadonlyField
                            label="Nama Anggota"
                            :modelValue="memberData.name"
                        />

                        <ReadonlyField
                            label="Nomor Anggota"
                            :modelValue="memberData.member_number"
                        />

                        <ReadonlyField
                            label="Total Simpanan"
                            :modelValue="formatRupiah(memberData.total_saving)"
                        />

                        <ReadonlyField
                                label="Total Kewajiban"
                                :modelValue="formatRupiah(memberData.total_obligation)"
                            />

                        <div class="md:col-span-2">
                            <ReadonlyField
                                label="Tanggal Bergabung"
                                :modelValue="memberData.joined_date"
                            />
                        </div>
                    </div>
                </div>

                <!-- Upload Dokumen -->
                <div class="mb-8">
                    <h2 class="font-head text-lg font-semibold text-gray-900 mb-4 dark:text-gray-100">
                        Upload Dokumen Permohonan
                    </h2>

                    <div
                        @drop="handleDrop"
                        @dragover="handleDragOver"
                        class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition-colors cursor-pointer"
                        @click="$refs.fileInput.click()"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            @change="handleFileUpload"
                            accept=".pdf,.doc,.docx"
                            class="hidden"
                        />

                        <div class="space-y-3">
                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>

                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    Drag & drop file untuk melampirkannya, atau
                                    <span class="text-blue-600 font-medium dark:text-blue-400">browse</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1 dark:text-gray-400">
                                    Hanya file PDF dan DOC yang diterima (maks. 2MB)
                                </p>
                            </div>

                            <!-- File yang diupload -->
                            <div v-if="uploadedFile" class="mt-4 p-3 bg-blue-50 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ uploadedFile.name }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ (uploadedFile.size / 1024).toFixed(2) }} KB
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        @click.stop="removeFile"
                                        class="text-red-500 hover:text-red-700"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-if="errorFile" class="font-body mt-2 text-sm text-red-600">
                        {{ errorFile }}
                    </p>

                    <p class="text-sm text-gray-600 mt-4">
                        Unduh dokumen permohonan
                        <a
                            href="/dokumen-permohonan-pengunduran-diri.pdf"
                            target="_blank"
                            class="text-blue-600 hover:text-blue-800 underline font-medium"
                        >
                            disini
                        </a>
                    </p>
                </div>

                <!-- Pernyataan -->
                <div class="mb-8">
                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 dark:bg-gray-800">
                        <p class="font-head text-gray-700 mb-6 dark:text-gray-100">
                            Dengan ini saya menyatakan bahwa saya benar-benar ingin mengundurkan diri
                            sebagai anggota koperasi dan seluruh dokumen yang saya kirimkan adalah benar.
                        </p>

                        <div class="space-y-4">
                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input
                                    v-model="isAgreed"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 text-blue-600 rounded focus:ring-blue-500"
                                />
                                <span class="text-gray-700 dark:text-gray-300">
                                    Saya memahami bahwa setelah permohonan dikirim, data tidak dapat diubah.
                                </span>
                            </label>

                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input
                                    v-model="isConfirmed"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 text-blue-600 rounded focus:ring-blue-500"
                                />
                                <span class="text-gray-700 dark:text-gray-300">
                                    Saya yakin ingin melanjutkan pengunduran diri sebagai anggota koperasi.
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-center">
                    <button
                        @click="submitResignation"
                        :disabled="!uploadedFile || !isAgreed || !isConfirmed"
                        class="font-head px-6 py-3 mt-5 mb-5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        Ajukan Permohonan
                    </button>
                </div>
            </div>
        </div>
    </Base>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-blue-900 rounded-2xl p-8 max-w-md w-full text-white shadow-xl">
            <div class="flex justify-center">
                <Icon icon="ix:success-filled" width="65" height="65" style="color: #36bffa" class="mb-5" />
            </div>

            <h2 class="font-head text-3xl font-bold mb-10">
                Berkas Pengunduran Diri<br />Telah Berhasil Terkirim!
            </h2>

            <p class="text-md opacity-90 mb-10">
                Berkas pengunduran diri yang telah terkirim akan dilakukan peninjauan oleh petugas kami!
            </p>

            <div class="flex justify-end">
                <Link
                    :href="'/user/dashboard/'"
                    class="font-head bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg font-medium transition no-underline"
                >
                    Kembali ke Dashboard
                </Link>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div v-if="showErrorModal && !showAlreadySubmittedModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-red-700 rounded-2xl p-8 max-w-md w-full text-white text-center shadow-xl">
            <div class="flex justify-center">
                <Icon icon="icon-park-twotone:error" width="65" height="65"  style="color: #ff929a" class="mb-5" />
            </div>

            <h2 class="font-head text-3xl font-bold mb-10">
                Gagal Mengirim Permohonan
            </h2>
            <p class="text-md opacity-90 mb-10">
                {{ errorMessage }}
            </p>

            <div class="flex justify-end">
                <button
                    @click="showErrorModal = false"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium transition"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Sudah Pernah Mengajukan Modal -->
    <div v-if="showAlreadySubmittedModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-yellow-600 rounded-2xl p-8 max-w-md w-full text-white shadow-xl">
            <div class="flex justify-center">
                <Icon icon="ic:twotone-info" width="65" height="65"  style="color: #fff34f" class="mb-5"  />
            </div>

            <h2 class="font-head text-3xl font-bold mb-10">
                Permohonan Sudah Diajukan
            </h2>

            <p class="text-md opacity-90 mb-10">
                {{ errorMessage || 'Anda sudah pernah mengajukan permohonan pengunduran diri. Tunggu proses peninjauan dari petugas kami.' }}
            </p>

            <div class="flex justify-end">
                <Link
                    :href="'/user/dashboard/'"
                    class="font-head bg-orange-500 hover:bg-orange-600 text-white px-8 py-3 rounded-lg font-medium transition no-underline text-lg"
                >
                    Kembali ke Dashboard
                </Link>
            </div>
        </div>
    </div>
</template>
