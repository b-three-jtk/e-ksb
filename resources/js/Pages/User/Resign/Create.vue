<script setup>
import Base from '@/Layouts/Base.vue'
import ReadonlyField from '@/Components/Form/ReadonlyField.vue'
import { ref, watch } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import BaseContainer from '../../../Components/BaseContainer.vue';
import Swal from 'sweetalert2';
import { toast } from 'vue3-toastify';
import dateParser from '@/Composables/dateParser.js';

const uploadedFile = ref(null)
const isAgreed = ref(false)
const isConfirmed = ref(false)
const fileInput = ref(null)
const errorFile = ref(null)

const page = usePage()
const memberData = page.props.member
const hasExistingResign = page.props.has_existing_resign || false

const formatRupiah = (value) => {
    return 'Rp ' + Number(value ?? 0).toLocaleString('id-ID')
}

const MAX_SIZE = 2 * 1024 * 1024

const validateFile = (file) => {
    if (file.type !== 'application/pdf') {
        errorFile.value = 'File harus berformat PDF'
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
    console.log(file.type)
    console.log(file.name)
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

const isSubmitting = ref(false)

const submitResignation = () => {
    if (hasExistingResign) {
        Swal.fire({
            icon: 'warning',
            title: 'Permohonan Sudah Diajukan',
            text: 'Permohonan pengunduran diri sudah pernah diajukan. Harap menunggu peninjauan dari petugas koperasi.',
            confirmButtonColor: '#009141',
            confirmButtonText: 'Kembali ke Dashboard'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/user/dashboard';
            }
        });
        return
    }

    if (!uploadedFile.value || !isAgreed.value || !isConfirmed.value) {
        Swal.fire({
            icon: 'error',
            title: 'Data Belum Lengkap',
            text: 'Harap lengkapi semua persyaratan (upload dokumen dan centang pernyataan)',
            confirmButtonColor: '#009141'
        });
        return;
    }

    Swal.fire({
        title: 'Konfirmasi Pengajuan',
        text: 'Apakah Anda yakin ingin mengajukan permohonan pengunduran diri?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Ajukan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#009141',
    }).then((result) => {
        if (result.isConfirmed) {
            isSubmitting.value = true
            const formData = new FormData()
            formData.append('document', uploadedFile.value)

            router.post(
                '/user/resign',
                formData,
                {
                    forceFormData: true,
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        isSubmitting.value = false
                        toast("Permohonan pengunduran diri berhasil diajukan!", {
                            "type": "success",
                            "position": "bottom-right",
                            "transition": "slide",
                            "dangerouslyHTMLString": true
                        }).then(() => {
                            setTimeout(() => {
                                window.location.href = '/user/dashboard';
                            }, 1500);
                        });

                        // Reset form
                        uploadedFile.value = null
                        isAgreed.value = false
                        isConfirmed.value = false
                        if (fileInput.value) fileInput.value.value = null
                    },
                    onError: (errors) => {
                        isSubmitting.value = false
                        if (errors.resign) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Permohonan Tidak Dapat Diajukan',
                                text: errors.resign,
                                confirmButtonColor: '#009141',
                                confirmButtonText: 'Kembali ke Dashboard'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = '/user/dashboard';
                                }
                            });
                        }
                        else if (errors.document) {
                            errorFile.value = errors.document
                            toast("Gagal mengupload dokumen: " + errors.document, {
                                "type": "error",
                                "position": "bottom-right",
                                "transition": "slide"
                            });
                        }
                        else {
                            const errorKeys = Object.keys(errors)
                            if (errorKeys.length > 0) {
                                toast(errors[errorKeys[0]], {
                                    "type": "error",
                                    "position": "bottom-right",
                                    "transition": "slide"
                                });
                            } else {
                                toast("Terjadi kesalahan saat mengirim permohonan", {
                                    "type": "error",
                                    "position": "bottom-right",
                                    "transition": "slide"
                                });
                            }
                        }
                    },
                    onFinish: () => {
                        isSubmitting.value = false
                    }
                }
            )
        }
    })
}
</script>

<template>
    <Base title="Pengunduran Diri Anggota">
        <div class="font-body min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8 dark:bg-gray-900 transition-colors">
            <BaseContainer
                title="PENGUNDURAN DIRI ANGGOTA KOPERASI"
                subtitle="Periksa kembali ringkasan informasi keanggotaan Anda dan lampirkan
                        permohonan pengunduran diri untuk proses lebih lanjut."
                :showDivider="true"
                class="mt-20"
                contentClass="mt-8"
            >
                <!-- <div
                    v-if="hasExistingResign"
                    class="flex justify-end mb-6"
                >
                    <span
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 border border-yellow-300"
                    >
                        <Icon icon="mdi:clock-outline" width="18" />
                        Pengunduran Diri Diajukan
                    </span>
                </div> -->

                <div
                    v-if="hasExistingResign"
                    class="mb-6 rounded-lg border border-yellow-300 bg-yellow-50 p-4"
                >
                    <p class="font-medium text-yellow-800">
                        Permohonan pengunduran diri telah diajukan.
                    </p>

                    <p class="mt-1 text-sm text-yellow-700">
                        Permohonan Anda sedang menunggu peninjauan dan persetujuan petugas koperasi.
                    </p>
                </div>

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
                            :modelValue="memberData.user_code"
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
                                :modelValue="dateParser(memberData.joined_date)"
                            />
                        </div>
                    </div>
                </div>

                <!-- Upload Dokumen -->
                <div
                    v-if="!hasExistingResign"
                    class="mb-8"
                >
                    <h2 class="font-head text-lg font-semibold text-gray-900 mb-4 dark:text-gray-100">
                        Upload Dokumen Permohonan
                    </h2>

                    <div
                        @drop="handleDrop"
                        @dragover="handleDragOver"
                        class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-secondary transition-colors cursor-pointer"
                        @click="$refs.fileInput.click()"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            @change="handleFileUpload"
                            accept=".pdf"
                            class="hidden"
                        />

                        <div class="space-y-3">
                            <div class="flex justify-center text-gray-500 dark:text-gray-200">
                                <Icon icon="lets-icons:upload" width="50" height="50" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    Drag & drop file untuk melampirkannya, atau
                                    <span class="text-blue-600 font-medium dark:text-blue-400">browse</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1 dark:text-gray-400">
                                    Hanya file PDF yang diterima (maks. 2MB)
                                </p>
                            </div>

                            <!-- File yang diupload -->
                            <div v-if="uploadedFile" class="mt-4 p-3 bg-gray-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-primary">
                                            <Icon icon="akar-icons:file" width="30" height="30" />
                                        </div>
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
                                        <div class="text-red-500">
                                            <Icon icon="iconoir:cancel" width="30" height="30" />
                                        </div>
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
                            href="/docs/PermohonanPengunduranDiri.docx"
                            target="_blank"
                            class="text-blue-600 hover:text-blue-800 underline font-medium"
                        >
                            disini
                        </a>
                    </p>
                </div>

                <!-- Pernyataan -->
                <div
                    v-if="!hasExistingResign"
                    class="mb-8"
                >
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
                                    class="mt-1 h-4 w-4 text-green-600 rounded focus:ring-green-500"
                                />
                                <span class="text-gray-700 dark:text-gray-300">
                                    Saya memahami bahwa setelah permohonan dikirim, data tidak dapat diubah.
                                </span>
                            </label>

                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input
                                    v-model="isConfirmed"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 text-green-600 rounded focus:ring-green-500"
                                />
                                <span class="text-gray-700 dark:text-gray-300">
                                    Saya yakin ingin melanjutkan pengunduran diri sebagai anggota koperasi.
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div
                    v-if="!hasExistingResign"
                    class="flex justify-center"
                >
                    <button
                        @click="submitResignation"
                        :disabled="
                            hasExistingResign ||
                            !uploadedFile ||
                            !isAgreed ||
                            !isConfirmed ||
                            isSubmitting
                        "
                        class="font-head px-6 py-3 mt-5 mb-5 bg-primary hover:bg-secondary text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
                    >
                        <Icon
                            v-if="isSubmitting"
                            icon="line-md:loading-twotone-loop"
                            width="20"
                            height="20"
                        />
                        Ajukan Permohonan
                    </button>
                </div>
            </BaseContainer>
        </div>
    </Base>
</template>
