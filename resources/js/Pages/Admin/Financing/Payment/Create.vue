<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import { Icon } from '@iconify/vue'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengelolaan Murabahah', link: '#' },
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
    formatRp(props.financing.installment_per_month)
)

const nextInstallmentNumber = ref(
    props.financing.next_installment_number
)

const tanggalPembayaran = ref(today())

function today() {
    const d = new Date()

    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')

    return `${year}-${month}-${day}`
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

function submitReschedule() {

    if (!rescheduleDate.value) {
        toast(
            'Tanggal reschedule wajib diisi',
            {
                type: 'error',
            },
        )
        return
    }
    rescheduleLoading.value = true

    router.post(
        `/admin/financings/${props.financing.id}/reschedule`,
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

const bankName = ref('')
const accountNumber = ref('')
const accountName = ref('')

const bankOptions = [
    'BCA',
    'BNI',
    'BRI',
    'Mandiri',
    'BSI',
]

// File
const paymentFile = ref(null)
const fileInput = ref(null)

function handleFileUpload(event) {
    const file = event.target.files[0]

    if (!file) return

    paymentFile.value = file
}

function removeFile() {
    paymentFile.value = null

    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

function resetPembiayaan() {
    nominalDisplay.value = formatRp(
        props.financing.installment_per_month
    )

    tanggalPembayaran.value = today()

    depositMethod.value = 'Tunai'
}

// Submit
function handleSubmit() {
    router.post(
        `/admin/financings/${props.financing.id}/payments`,
        {
            financing_id:
                selectedFinancing.value.id,

            installment_id:
                selectedFinancing.value.installment_id,

            payment_method:
                depositMethod.value,

            nominal:
                Number(
                    nominalDisplay.value.replace(/\./g, '')
                ),

            payment_date:
                tanggalPembayaran.value,
        },
        {
            preserveScroll: true,

            onSuccess: (page) => {

                toast(
                    'Pembayaran berhasil diposting',
                    {
                        type: 'success',
                    },
                )

                const pdfUrl =
                    page.props.flash?.pdf_url

                if (pdfUrl) {
                    window.open(pdfUrl, '_blank')
                }

                resetPembiayaan()
            },

            onError: (errors) => {

                console.error(errors)

                toast(
                    'Terjadi kesalahan saat memproses pembayaran',
                    {
                        type: 'error',
                    },
                )
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
                                    Merk
                                </label>

                                <input
                                    :value="selectedFinancing.brand"
                                    readonly
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm text-gray-600 mb-1"
                                >
                                    Warna
                                </label>

                                <input
                                    :value="selectedFinancing.color"
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
                                :value="nextInstallmentNumber"
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
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600"
                            />
                        </div>

                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-4"
                        >
                            <div>
                                <label
                                    class="block text-sm text-gray-600 mb-1"
                                >
                                    Tanggal Pembayaran
                                </label>

                                <input
                                    v-model="tanggalPembayaran"
                                    type="date"
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300"
                                />
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
                        @click="resetPembiayaan"
                        type="button"
                        class="px-8 py-2.5 rounded-lg border border-gray-300 hover:bg-gray-50"
                    >
                        Reset
                    </button>

                    <button
                        @click="handleSubmit"
                        type="button"
                        class="px-8 py-2.5 rounded-lg bg-green-700 hover:bg-green-800 text-white"
                    >
                        Posting
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
                            <input
                                v-model="rescheduleDate"
                                type="date"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600"
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
                            class="px-6 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium"
                        >
                            Kirim
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
