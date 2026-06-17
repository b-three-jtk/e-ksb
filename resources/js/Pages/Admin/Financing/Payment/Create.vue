<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import { Icon } from '@iconify/vue'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import Swal from 'sweetalert2'

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Pengelolaan Murabahah', link: '/admin/financings' },
    { name: 'Pembayaran Murabahah' },
]

const props = defineProps({
    financing: {
        type: Object,
        required: true,
    },
})

const selectedFinancing = ref(props.financing)

// Format
function formatRp(value) {
    return Number(value || 0).toLocaleString('id-ID')
}

// Form
const nominalDisplay = ref(
    'Rp ' +
    formatRp(
        props.financing.installment_per_month
    )
)

const tanggalPembayaran = ref(today())

function today() {
    const d = new Date()

    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')

    return `${year}-${month}-${day}`
}

function getLateDays() {
    if (
        !selectedFinancing.value?.current_due_date ||
        !tanggalPembayaran.value
    ) {
        return 0
    }

    const due = new Date(
        selectedFinancing.value.current_due_date
    )

    const pay = new Date(
        tanggalPembayaran.value
    )

    const diff =
        Math.floor(
            (pay - due) /
            (1000 * 60 * 60 * 24)
        )

    return diff > 0 ? diff : 0
}

// Reschedule Modal
const showRescheduleModal = ref(false)
const rescheduleInstallmentNumber = ref('')
const rescheduleDate = ref('')

function openReschedule() {
    if (selectedFinancing.value) {
        rescheduleInstallmentNumber.value =
            selectedFinancing.value.next_installment_number
        rescheduleDate.value =
            selectedFinancing.value.next_due_date
    }
    showRescheduleModal.value = true
}

function closeReschedule() {
    showRescheduleModal.value = false
}

const rescheduleLoading = ref(false)

const isSubmittingPayment = ref(false)
const isSubmittingReschedule = ref(false)

async function submitReschedule() {

    if (!rescheduleDate.value) {
        toast(
            'Tanggal reschedule wajib diisi',
            {
                type: 'error',
                position: 'bottom-right',
            },
        )
        return
    }

    const result = await Swal.fire({
        title: 'Reschedule Pembayaran?',
        text: `Jatuh tempo akan diubah menjadi ${rescheduleDate.value}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Reschedule',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        confirmButtonColor: '#009141'
    })

    if (!result.isConfirmed) {
        return
    }

    rescheduleLoading.value = true

    router.post(
        `/admin/financings/${props.financing.id}/payments/reschedule`,
        {
            installment_id:
                selectedFinancing.value.installment_id,
            due_date:
                rescheduleDate.value,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                selectedFinancing.value.next_due_date =
                    rescheduleDate.value
                toast(
                    'Jadwal pembayaran berhasil diperbarui',
                    {
                        type: 'success',
                        position: 'bottom-right',
                    },
                )
                closeReschedule()
            },

            onError: (errors) => {

                console.error(errors)

                toast(
                    'Gagal melakukan reschedule',
                    {
                        type: 'error',
                        position: 'bottom-right',
                    },
                )
            },

            onFinish: () => {
                rescheduleLoading.value = false
            },
        },
    )
}

// Metode Pembayaran
const depositMethod = ref('Tunai')

// Submit
async function handleSubmit() {
    const result = await Swal.fire({
        title: 'Posting Pembayaran?',
        text: 'Pembayaran akan diproses dan tidak dapat dibatalkan.',
        icon: 'question',
        iconColor: '#009141',
        showCancelButton: true,
        confirmButtonText: 'Ya, Posting',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        confirmButtonColor: '#009141'
    })

    if (!result.isConfirmed) {
        return
    }
    isSubmittingPayment.value = true
    router.post(
        `/admin/financings/${props.financing.id}/payments/store`,
        {
            financing_id:
                selectedFinancing.value.id,

            installment_id:
                selectedFinancing.value.installment_id,

            payment_method:
                depositMethod.value,

            nominal:
                selectedFinancing.value.installment_per_month,

            payment_date:
                tanggalPembayaran.value,
        },
        {
            preserveScroll: true,

            onSuccess: (page) => {
                console.log(page.props.flash)

                toast(
                    'Pembayaran berhasil diposting',
                    {
                        type: 'success',
                        position: 'bottom-right',
                    },
                )

                const pdfUrl =
                    page.props.flash?.pdf_url

                if (pdfUrl) {
                    window.open(pdfUrl, '_blank')
                }
                if (page.props.financing) {
                    selectedFinancing.value = page.props.financing
                }
            },
            onError: (errors) => {

                console.error(errors)

                toast(
                    'Terjadi kesalahan saat memproses pembayaran',
                    {
                        type: 'error',
                        position: 'bottom-right',
                    },
                )
            },
            onFinish: () => {
                isSubmittingPayment.value = false
            },
        },
    )
}
</script>

<template>
    <AdminLayout title="Pembayaran Murabahah">
        <PageBreadcrumb
            page-title="Pembayaran Murabahah"
            :items="breadcrumbItems"
        />

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="w-full px-4 sm:px-10 space-y-6">

                <!-- INFO PEMOHON PEMBIAYAAN -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
                >
                    <div
                        class="px-5 py-4 border-b border-gray-200 dark:border-gray-700"
                    >
                        <h2
                            class="text-xs font-semibold tracking-widest text-gray-500 uppercase"
                        >
                            Data Pembiayaan
                        </h2>
                    </div>

                    <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">
                                Nomor Pembiayaan
                            </p>

                            <p class="font-medium text-gray-800">
                                {{ selectedFinancing.transaction_code }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500 mb-1">
                                Nama Anggota
                            </p>

                            <p class="font-medium text-gray-800">
                                {{ selectedFinancing.user.name }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500 mb-1">
                                Produk
                            </p>

                            <p class="font-medium text-gray-800">
                                {{ selectedFinancing.product_name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- DETAIL PEMBIAYAAN -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
                >
                    <div
                        class="px-5 py-4 border-b border-gray-200 dark:border-gray-700"
                    >
                        <h2
                            class="text-xs font-semibold tracking-widest text-gray-500 uppercase"
                        >
                            Detail Data Pembiayaan
                        </h2>
                    </div>

                    <div class="p-5">
                        <div
                            v-if="!selectedFinancing"
                            class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex gap-3 items-start"
                        >
                            <Icon
                                icon="mdi:alert-circle-outline"
                                class="text-amber-500 mt-0.5"
                                width="20"
                            />

                            <p class="text-sm text-amber-700">
                                Pilih pembiayaan terlebih dahulu.
                            </p>
                        </div>

                        <div
                            v-else
                            class="grid grid-cols-1 md:grid-cols-2 gap-4"
                        >
                            <div>
                                <label
                                    class="block text-sm text-gray-600 mb-1"
                                >
                                    Kategori Produk
                                </label>

                                <input
                                    :value="selectedFinancing.product_type"
                                    readonly
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm text-gray-600 mb-1"
                                >
                                    Jumlah / Kuantitas
                                </label>

                                <input
                                    :value="selectedFinancing.qty"
                                    readonly
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm text-gray-600 mb-1"
                                >
                                    Nama Produk
                                </label>

                                <input
                                    :value="selectedFinancing.product_name"
                                    readonly
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm text-gray-600 mb-1"
                                >
                                    Deskripsi Produk
                                </label>

                                <input
                                    :value="selectedFinancing.product_specification"
                                    readonly
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm text-gray-600 mb-1"
                                >
                                    Nominal Angsuran
                                </label>

                                <input
                                    :value="'Rp ' + formatRp(selectedFinancing.installment_per_month)"
                                    readonly
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm text-gray-600 mb-1"
                                >
                                    Total Sisa Angsuran
                                </label>

                                <input
                                    :value="'Rp ' + formatRp(selectedFinancing.remaining_balance)"
                                    readonly
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DETAIL PEMBAYARAN -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
                >
                    <div
                        class="px-5 py-4 border-b border-gray-200 dark:border-gray-700"
                    >
                        <h2
                            class="text-xs font-semibold tracking-widest text-gray-500 uppercase"
                        >
                            Detail Pembayaran Pembiayaan
                        </h2>
                    </div>

                    <div class="p-5 space-y-4">

                        <div>
                            <label
                                class="block text-sm text-gray-600 mb-1"
                            >
                                Pembayaran Pembiayaan Ke-
                            </label>

                            <input
                                :value="selectedFinancing.next_installment_number"
                                readonly
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm text-gray-600 mb-1"
                            >
                                Nominal
                            </label>

                            <input
                                v-model="nominalDisplay"
                                type="text"
                                readonly
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600"
                            />
                        </div>

                        <div
                            class="grid grid-cols-1 md:grid-cols-3 gap-4"
                        >
                            <div>
                                <label
                                    class="block text-sm text-gray-600 mb-1"
                                >
                                    Tanggal Pembayaran
                                </label>

                                <BaseInputAdmin
                                    v-model="tanggalPembayaran"
                                    type="date"
                                    :disabled="true"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm text-gray-600 mb-1"
                                >
                                    Jatuh Tempo Angsuran Saat Ini
                                </label>

                                <input
                                    :value="selectedFinancing.current_due_date"
                                    type="date"
                                    readonly
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50"
                                />

                                <p
                                    v-if="getLateDays() > 0"
                                    class="text-sm text-red-600 mt-1"
                                >
                                    Terlambat {{ getLateDays() }} hari
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm text-gray-600 mb-1"
                                >
                                    Jatuh Tempo Selanjutnya
                                </label>

                                <input
                                    :value="selectedFinancing.next_due_date"
                                    type="date"
                                    readonly
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50"
                                />
                            </div>
                        </div>

                        <!-- RESCHEDULE LINK -->
                        <p class="text-sm text-gray-500">
                            Ingin melakukan reschedule?
                            <button
                                type="button"
                                @click="openReschedule"
                                class="text-green-600 hover:underline font-medium"
                            >
                                klik disini
                            </button>
                        </p>

                        <!-- METODE PEMBAYARAN -->
                        <div>
                            <label class="block text-sm text-gray-600 mb-2">
                                Metode Pembayaran <span class="text-red-500">*</span>
                            </label>

                            <div class="flex gap-5">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        value="Tunai"
                                        v-model="depositMethod"
                                        class="accent-green-600"
                                    />
                                    <span class="text-sm">Tunai</span>
                                </label>

                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        value="Non-Tunai"
                                        v-model="depositMethod"
                                        class="accent-green-600"
                                    />
                                    <span class="text-sm">Non-Tunai</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BUTTON -->
                <div class="flex justify-center gap-4 pb-10">
                    <button
                        @click="handleSubmit"
                        type="button"
                        :disabled="isSubmittingPayment"
                        class="inline-flex items-center gap-2 px-8 py-2.5 rounded-lg bg-primary hover:bg-secondary disabled:opacity-60 text-white transition-colors"
                    >
                        Posting

                        <Icon
                            v-if="isSubmittingPayment"
                            icon="tabler:loader-2"
                            class="w-4 h-4 animate-spin"
                        />
                    </button>
                </div>

            </div>
        </div>

        <!-- RESCHEDULE MODAL -->
        <Teleport to="body">
            <div
                v-if="showRescheduleModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            >
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
                    <h2 class="text-sm font-semibold tracking-widest text-gray-700 uppercase mb-5">
                        Penjadwalan Ulang Pembayaran
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">
                                Pembayaran Pembiayaan Ke-
                            </label>
                            <input
                                :value="rescheduleInstallmentNumber"
                                readonly
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50 text-gray-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">
                                Pemberlakuan Kembali Pembiayaan
                            </label>
                            <BaseInputAdmin
                                v-model="rescheduleDate"
                                type="date"
                                :max-date="undefined"
                                placeholder="Pilih tanggal"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end mt-6">
                        <button
                            type="button"
                            @click="closeReschedule"
                            class="mr-3 px-5 py-2 rounded-lg border border-gray-300 text-sm hover:bg-gray-50"
                        >
                            Batal   
                        </button>
                        <button
                            type="button"
                            @click="submitReschedule"
                            :disabled="rescheduleLoading"
                            class="inline-flex items-center gap-2 px-6 py-2 rounded-lg bg-primary hover:bg-secondary disabled:opacity-60 text-white text-sm font-medium transition-colors"
                        >
                            <Icon
                                v-if="rescheduleLoading"
                                icon="line-md:loading-alt-loop"
                                class="w-4 h-4"
                            />
                            Kirim
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>