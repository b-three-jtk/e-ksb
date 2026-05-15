<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { Icon } from '@iconify/vue';
import pageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';
import ConfirmationModal from '@/Components/Savings/ConfirmationModal.vue';
import Struk from '@/Components/Savings/Struk.vue';

const page = usePage()

const financings = computed(() => page.props.financings || [])
const pengurus = computed(() => page.props.pengurus || {})

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin'},
    { name: 'Pengelolaan Murabahah', link: '#'},
    { name: 'Pembayaran Murabahah' },
]

// Cari Pembiayaan
const pembiayaanQuery = ref('')
const selectedFinancing = ref(null)

const pembiayaanSuggestion = computed(() => {
    const q = pembiayaanQuery.value.toLocaleLowerCase().trim()
    if (!q || q.length < 2) return []
    return financings.value
        .filter(f => 
            f.transaction_code?.toLowerCase().includes(q) ||
            f.product_name?.toLowerCase().includes(q) ||
            f.user?.name?.toLowerCase().includes(q)
        )
        .slice(0, 8)
})

const showSuggestions = computed(() => 
    pembiayaanSuggestion.value,length > 0 && !selectedFinancing.value
)

function pilihPembiayaan(f) {
    selectedFinancing.value = f
    pembiayaanQuery.value = `${f.transaction_code} - ${f.product_name}`
}

function resetPembiayaan() {
    selectedFinancing.value = null
    pembiayaanQuery.value = ''
    resetPaymentForm()
}

const errorNominal = ref('')
const tanggalPembayaran   = ref(today())

function onNominalInput(e) {
  const value = e.target.value
  errorNominal.value = /[^0-9.]/.test(value) ? 'Nominal hanya boleh angka' : ''
  const raw = value.replace(/\D/g, '')
  nominalRaw.value     = raw
  nominalDisplay.value = raw ? formatRp(raw) : ''
}

function today() {
  const d     = new Date()
  const year  = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day   = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function formatRp(val) {
  return Number(val || 0).toLocaleString('id-ID')
}

const bankOptions = ['BCA','BNI','BRI','Mandiri','BTN','CIMB Niaga','Permata','Danamon','BSI','BJB']
</script>

<template>
    <AdminLayout title="Pembayaran Murabahah">
        <pageBreadcrumb page-title="Pembayaran Murabahah" :items="breadcrumbItems" />

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="w-full px-4 sm:px-10 space-y-6 font-body">

                <!-- Data Pembayaran Pembiayaan Murabahah -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xs font-semibold tracking-widest text-gray-500 dark:text-gray-400 uppercase font-head">
                            Data Pembiayaan
                        </h2>
                    </div>

                    <div class="p-5 space-y-4">
                        <!-- cari anggota atau pembiayaan -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                Cari Pembiayaan <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <Icon icon="mdi:magnify" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input
                                    type="text"
                                    placeholder="Nama Pembiayaan..."
                                    class="pl-10 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600
                                    rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Data Pembayaran Pembiayaan Murabahah -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xs font-semibold tracking-widest text-gray-500 dark:text-gray-400 uppercase font-head">
                            Detail Data Pembiayaan
                        </h2>
                    </div>

                    <div class="p-5 space-y-4">

                        <!-- Peringatan jika pembiayaan belum dipilih -->
                         <Transition name="fade">
                            <div
                                class="flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/20
                                        border border-amber-200 dark:border-amber-700 rounded-lg"
                            >
                                <Icon icon="mdi:account-alert-outline" class="text-amber-500 shrink-0" width="22" />
                                <p class="text-sm text-amber-700 dark:text-amber-300">
                                    Pilih pembiayaan terlebih dahulu untuk mengisi detail pembayaran.
                                </p>
                            </div>
                         </Transition>

                            <!-- Kategori Produk dan Jumlah Kuantitas -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                    Kategori Produk
                                </label>
                                <input
                                    type="text"
                                    readonly
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600
                                    rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                    focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                    Jumlah Kuantitas
                                </label>
                                <input
                                    type="text"
                                    readonly
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600
                                            rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <!-- Merk dan Warna -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                    Merk
                                </label>
                                <input
                                    type="text"
                                    readonly
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600
                                        rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                    Warna
                                </label>
                                <input
                                    type="text"
                                    readonly
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600
                                        rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <!-- Nominal Angsuran dan Total Sisa Angsuran -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                    Nominal Angsuran
                                </label>
                                <input
                                    type="text"
                                    readonly
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600
                                            rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                    Total Sisa Angsuran
                                </label>
                                <input
                                    type="text"
                                    readonly
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600
                                    rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pembayaran Pembiayaan Murabahah -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xs font-semibold tracking-widest text-gray-500 dark:text-gray-400 uppercase font-head">
                            Detail Pembayaran Pembiayaan
                        </h2>
                    </div>

                    <div class="p-5 space-y-4">

                        <!-- Pembayaran Pembiayaan Ke- -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                Pembayaran Pembiayaan Ke-
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    readonly
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                                        bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                        focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                        </div>

                        <!-- Nominal -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                Nominal <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm pointer-events-none">Rp</span>
                                <input
                                    :value="nominalDisplay"
                                    @input="onNominalInput"
                                    type="text"
                                    inputmode="numeric"
                                    readonly
                                    placeholder="0"
                                    class="pl-10 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                                        bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                        focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                            <p v-if="errorNominal" class="mt-1 text-xs text-red-600 flex items-center gap-1">
                                <Icon icon="mdi:alert-circle-outline" width="13" />
                                {{ errorNominal }}
                            </p>
                        </div>

                        <!-- Tanggal Pembayaran & Jatuh Tempo Selanjutnya -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                    Tanggal Pembayaran <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    :max="today()"
                                    readonly
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                                        bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                    Tanggal Jatuh Tempo Selanjutnya <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    :max="today()"
                                    readonly
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                                        bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <div class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                <span>Ingin melakukan reschedule?</span> <span class="text-secondary">klik disini</span>
                            </div>
                        </div>

                        <!-- Metode -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 font-head">
                                Metode Penyetoran
                            </label>
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="Tunai" v-model="depositMethod" class="text-blue-600" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Tunai</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="Non-Tunai" v-model="depositMethod" class="text-blue-600" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Non-Tunai</span>
                                </label>
                            </div>
                        </div>

                        <!-- Non-Tunai fields -->
                        <Transition name="slide">
                            <div
                                v-if="depositMethod === 'Non-Tunai'"
                                class="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700"
                            >
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- Bank -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                            Bank <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            v-model="bankName"
                                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                                                bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                                focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                            <option value="" disabled>— Pilih —</option>
                                            <option v-for="b in bankOptions" :key="b">{{ b }}</option>
                                        </select>
                                    </div>

                                    <!-- No. Rekening -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                            No. Rekening <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            list="norekList"
                                            v-model="accountNumber"
                                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                                                bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                                focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                        <datalist id="norekList">
                                            <option v-for="n in accountNumberOptions" :key="n" :value="n" />
                                        </datalist>
                                    </div>

                                    <!-- Atas Nama -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                            Atas Nama <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            list="namaRekList"
                                            v-model="accountName"
                                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                                                bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                                focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                        <datalist id="namaRekList">
                                            <option v-for="n in accountNameOptions" :key="n" :value="n" />
                                        </datalist>
                                    </div>
                                </div>

                                <!-- Upload bukti -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                                        Bukti Transfer <span class="text-red-500">*</span>
                                    </label>
                                    <div
                                        @click="fileInput?.click()"
                                        class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6
                                                text-center cursor-pointer hover:border-blue-400 transition-colors"
                                    >
                                        <input
                                            ref="fileInput"
                                            type="file"
                                            @change="handleFileUpload"
                                            accept="image/*,.pdf"
                                            class="hidden"
                                        />
                                        <div v-if="!paymentProof">
                                            <Icon icon="lets-icons:upload" class="mx-auto text-gray-400 mb-2" width="40" />
                                            <p class="text-sm text-gray-600 dark:text-gray-400">Klik untuk upload bukti</p>
                                            <p class="text-xs text-gray-400 mt-1">JPG / PNG / PDF • max 2MB</p>
                                        </div>
                                        <div v-else class="flex items-center gap-3">
                                            <Icon icon="akar-icons:file" class="text-blue-500 shrink-0" width="32" />
                                            <div class="flex-1 text-left text-sm text-gray-700 dark:text-gray-300 min-w-0">
                                                <div class="truncate">{{ paymentFile?.name }}</div>
                                                <div class="text-xs text-gray-500">{{ (paymentFile.size / 1024).toFixed(1) }} KB</div>
                                            </div>
                                            <button @click.stop="removeFile" class="text-red-500 hover:text-red-700 shrink-0">
                                                <Icon icon="mdi:close" width="20" />
                                            </button>
                                        </div>
                                    </div>
                                    <p v-if="errorFile" class="mt-1 text-xs text-red-600">{{ errorFile }}</p>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>