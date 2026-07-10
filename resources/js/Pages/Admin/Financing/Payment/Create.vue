<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import RekeningModal from '@/Components/Form/RekeningModal.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import Swal from 'sweetalert2'

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Pengelolaan Murabahah', link: '/admin/pembiayaan' },
    { name: 'Pembayaran Murabahah' },
]

const props = defineProps({
    pembiayaan: {
        type: Object,
        required: true,
    },
})

const selectedFinancing = ref(props.pembiayaan)

// Format
function formatRp(value) {
    return Number(value || 0).toLocaleString('id-ID')
}

// Form
const nominalDisplay = ref(
    'Rp ' +
    formatRp(
        props.pembiayaan.installment_per_month
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
        `/admin/pembiayaan/${props.pembiayaan.id}/payments/reschedule`,
        {
            installment_id:
                selectedFinancing.value.angsuran_id,
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
const selectedRekening = ref(null)
const showRekeningModal = ref(false)
const rekeningNo = ref('')
const rekeningBank = ref('')
const rekeningName = ref('')
const buktiPembayaran = ref(null)
const errorBukti = ref('')

const MAX_FILE_SIZE = 2 * 1024 * 1024 // 2MB

function onBuktiChange(file) {
  if (!file) {
    buktiPembayaran.value = null
    errorBukti.value = ''
    return
  }
  if (file.size > MAX_FILE_SIZE) {
    errorBukti.value = 'Ukuran file maksimal 2MB'
    buktiPembayaran.value = null
    toast('Ukuran bukti pembayaran maksimal 2MB', { type: 'warning', position: 'bottom-right' })
    return
  }
  errorBukti.value = ''
  buktiPembayaran.value = file
}

const rekeningSelectables = computed(() => {
  const accounts = (props.pembiayaan?.bank_accounts || []).map(r => ({
    value: r.no_rekening,
    text: `${r.nama_bank} - ${r.no_rekening} (a.n ${r.atas_nama})`
  }))
  return [
    ...accounts,
    { value: 'NEW', text: '+ Tambah Rekening Baru', isAction: true }
  ]
})

function handleRekeningChange(value) {
  if (value === 'NEW') {
    showRekeningModal.value = true
    rekeningNo.value = ''
    rekeningBank.value = ''
    rekeningName.value = ''
    selectedRekening.value = null
    return
  }
  rekeningNo.value = value
  const found = (props.pembiayaan?.bank_accounts || [])
    .find(r => r.no_rekening === value)
  if (found) {
    selectedRekening.value = found
    rekeningBank.value = found.nama_bank
    rekeningName.value = found.atas_nama
  }
}

function handleRekeningCreated(rekening) {
  if (!props.pembiayaan.bank_accounts) {
    props.pembiayaan.bank_accounts = []
  }
  props.pembiayaan.bank_accounts.push(rekening)
  selectedRekening.value = rekening
  rekeningNo.value = rekening.no_rekening
  rekeningBank.value = rekening.nama_bank
  rekeningName.value = rekening.atas_nama
}

// Submit
async function handleSubmit() {
  if (depositMethod.value === 'Non-Tunai') {
    if (!rekeningNo.value) {
      toast('Pilih rekening tujuan terlebih dahulu', { type: 'warning', position: 'bottom-right' })
      return
    }
    if (!buktiPembayaran.value) {
      toast('Unggah bukti pembayaran terlebih dahulu', { type: 'warning', position: 'bottom-right' })
      return
    }
  }

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

  if (!result.isConfirmed) return

  isSubmittingPayment.value = true

  const formData = new FormData()
  formData.append('pembiayaan_id', selectedFinancing.value.id)
  formData.append('angsuran_id', selectedFinancing.value.angsuran_id)
  formData.append('metode_pembayaran', depositMethod.value)
  formData.append('jumlah_angsuran_dibayar', selectedFinancing.value.installment_per_month)
  formData.append('tgl_pembayaran', tanggalPembayaran.value)

  if (depositMethod.value === 'Non-Tunai') {
    formData.append('no_rekening', rekeningNo.value)
    formData.append('bukti_pembayaran', buktiPembayaran.value)
  }

  router.post(
    `/admin/pembiayaan/${props.pembiayaan.id}/payments/store`,
    formData,
    {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        toast('Pembayaran berhasil diposting', { type: 'success', position: 'bottom-right' })
      },
      onError: (errors) => {
        console.error(errors)
        const msg = Object.values(errors).flat().join(', ')
        toast(msg || 'Terjadi kesalahan saat memproses pembayaran', { type: 'error', position: 'bottom-right' })
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
                                {{ selectedFinancing.user.nama }}
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
                                    :value="selectedFinancing.jenis_barang"
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
                                    :value="selectedFinancing.kuantitas"
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
                                    :value="selectedFinancing.product_spesifikasi_barang"
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
                                    v-if="selectedFinancing.next_due_date"
                                    :value="selectedFinancing.next_due_date"
                                    type="date"
                                    readonly
                                    class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50"
                                />
                                <input
                                    v-else
                                    value="-"
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
                        <!-- REKENING & BUKTI -->
                        <div v-if="depositMethod === 'Non-Tunai'" class="mt-2 flex flex-col gap-4">
                            <BaseInputAdmin
                                :model-value="rekeningNo"
                                label="Rekening Tujuan"
                                type="select"
                                required
                                :selectables="rekeningSelectables"
                                @update:modelValue="handleRekeningChange"
                                hint="Pilih rekening tujuan yang digunakan anggota untuk membayar."
                            />

                            <BaseInputAdmin
                                label="Bukti Pembayaran"
                                type="file"
                                accept="image/*,.pdf"
                                required
                                @update:modelValue="onBuktiChange"
                                hint="Unggah bukti transfer (format JPG, PNG, atau PDF, maks. 2MB)."
                            />
                            <p v-if="errorBukti" class="mt-1 text-xs text-red-500 flex items-center gap-1">
                            <Icon icon="mdi:alert-circle-outline" width="13" />{{ errorBukti }}
                            </p>
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
                                :max-date="selectedFinancing.tanggal_akhir_periode"
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

        <RekeningModal
            :show="showRekeningModal"
            :anggota-id="selectedFinancing?.anggota_id"
            endpoint="/admin/pembiayaan/rekening-angsuran"
            @close="showRekeningModal = false"
            @created="handleRekeningCreated"
        />
    </AdminLayout>
</template>