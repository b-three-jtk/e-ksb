<script setup>
import Base from '@/Layouts/Base.vue'
import BaseContainer from '@/Components/BaseContainer.vue'
import StepIndicator from '@/Components/StepIndicator.vue'
import { ref, computed, watch, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'

const page = usePage()

const steps = ref([
  { key: 'info', label: 'Informasi Simpanan' },
  { key: 'deposit', label: 'Detail Penyetoran' },
  { key: 'confirmation', label: 'Konfirmasi' }
])

const currentStep = ref(0)
const savingAccounts = computed(() => page.props.savingAccounts || [])
const totalBalance = computed(() => page.props.totalBalance ?? 0)
const totalPerCategory = computed(() => page.props.totalPerCategory || {
    pokok: 0,
    wajib: 0,
    sukarela: 0
})
const accounts = computed(() => page.props.accounts || [])
const memberData = computed(() => page.props.member || {})

const tanggalHariIni = new Date().toLocaleDateString('id-ID', {
  day: '2-digit',
  month: 'long',
  year: 'numeric'
})

const savingAmount = ref('')
const savingCategory = ref('')
const statementAccepted = ref(false)
const paymentProof = ref(null)
const paymentFile = ref(null)
const errorFile = ref('')
const fileInput = ref(null)
const depositMethod = ref('Tunai')
const accountName = ref('')
const accountNumber = ref('')
const bankName = ref('')
const savingAccountId = ref('')

const stepValidations = ref([
  { isValid: true },
  { isValid: false },
  { isValid: false }
])

const bankOptions = [
  'BCA',
  'BNI',
  'BRI',
  'Mandiri',
  'BTN',
  'CIMB Niaga',
  'Permata',
  'Danamon',
  'BSI',
  'BJB',
]

const isStep2Valid = computed(() => {
  if (!savingAmount.value || !savingCategory.value) return false
  
  const numericAmount = parseInt(savingAmount.value.replace(/\D/g, ''))
  if (numericAmount <= 0) return false
  
  if (depositMethod.value === 'Non-Tunai') {
    return !!(accountName.value && accountNumber.value && bankName.value && paymentFile.value)
  }
  
  return true
})

const isStep3Valid = computed(() => {
  return statementAccepted.value
})

const accountNameOptions = computed(() =>
    accounts.value.map(a => ({
        label: a.account_name,
        value: a.account_name,
        data: a,
    }))
)

const accountNumberOptions = computed(() =>
    accounts.value.map(a => ({
        label: a.account_number,
        value: a.account_number,
        data: a,
    }))
)

const fillFormAccount = (account) => {
    if (!account) return
    accountName.value = account.account_name
    accountNumber.value = account.account_number
    bankName.value = account.bank_name
}

watch(accountName, (val) => {
    const acc = accounts.value.find(a => a.account_name === val)
    if (acc) fillFormAccount(acc)
})

watch(accountNumber, (val) => {
    const acc = accounts.value.find(a => a.account_number === val)
    if (acc) fillFormAccount(acc)
})

watch(savingCategory, (val) => {
  const account = savingAccounts.value.find(a => a.type === val)
  savingAccountId.value = account?.id || ''
})

const savingCategories = [
  'Simpanan Pokok',
  'Simpanan Wajib',
  'Simpanan Sukarela'
]

watch(depositMethod, (val) => {
  if (val === 'Tunai') {
    accountName.value = ''
    accountNumber.value = ''
    bankName.value = ''
    paymentFile.value = null
    paymentProof.value = null
  }
})

watch([savingAmount, savingCategory, depositMethod, accountName, accountNumber, bankName, paymentFile], () => {
  stepValidations.value[1].isValid = isStep2Valid.value
}, { deep: true })

watch(statementAccepted, () => {
  stepValidations.value[2].isValid = isStep3Valid.value
})

const formatRupiah = (value) => {
    return Number(value ?? 0).toLocaleString('id-ID')
}

const formatCurrencyInput = (value) => {
    const numericValue = value.replace(/\D/g, '')
    savingAmount.value = numericValue ? formatRupiah(numericValue) : ''
}

const handleFileUpload = (event) => {
    const file = event.target.files[0]
    if (!file) return

    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']
    const maxSize = 2 * 1024 * 1024 // 2MB

    if (!validTypes.includes(file.type)) {
        errorFile.value = 'Format file harus JPG, PNG, atau PDF'
        return
    }

    if (file.size > maxSize) {
        errorFile.value = 'Ukuran file maksimal 2MB'
        return
    }

    paymentFile.value = file
    paymentProof.value = URL.createObjectURL(file)
    errorFile.value = ''
}

const removeFile = () => {
    paymentFile.value = null
    paymentProof.value = null
    errorFile.value = ''
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const nextStep = () => {
    if (currentStep.value === 1 && !stepValidations.value[1].isValid) {
        toast('Harap lengkapi semua data pada step ini', {
            type: 'warning',
            position: 'bottom-right',
        })
        return
    }

    if (currentStep.value < steps.value.length - 1) {
        currentStep.value++
    }
}

const prevStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const submitDeposit = () => {
    if (!stepValidations.value[2].isValid) {
        toast('Harap setujui pernyataan terlebih dahulu', {
            type: 'warning',
            position: 'bottom-right',
        })
        return
    }

    const numericAmount = parseInt(savingAmount.value.replace(/\D/g, ''))
    if (!numericAmount || numericAmount <= 0) {
        toast('Nominal simpanan tidak valid', {
            type: 'warning',
            position: 'bottom-right',
        })
        return
    }

    if (depositMethod.value === 'Non-Tunai') {
        if (!accountName.value || !accountNumber.value || !bankName.value) {
            toast('Lengkapi data rekening Non-Tunai', {
                type: 'warning',
                position: 'bottom-right',
            })
            return
        }

        if (!paymentFile.value) {
            toast('Upload bukti transaksi terlebih dahulu', {
                type: 'warning',
                position: 'bottom-right',
            })
            return
        }
    }

    Swal.fire({
        title: 'Konfirmasi Penyetoran',
        text: 'Apakah data penyetoran yang Anda masukkan sudah benar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kirim',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#2563eb',
    }).then((result) => {
        if (!result.isConfirmed) return

        const formData = new FormData()
        formData.append('saving_account_id', savingAccountId.value)
        formData.append('amount', numericAmount)
        formData.append('method', depositMethod.value)
        formData.append('saving_category', savingCategory.value)

        if (depositMethod.value === 'Non-Tunai') {
            formData.append('account_name', accountName.value)
            formData.append('account_number', accountNumber.value)
            formData.append('bank_name', bankName.value)
            formData.append('payment_proof', paymentFile.value)
        }

        router.post('/user/simpanan/penyetoran', formData, {
            forceFormData: true,
            preserveScroll: true,

            onSuccess: () => {
                toast('Penyetoran simpanan berhasil diajukan!', {
                    type: 'success',
                    position: 'bottom-right',
                })

                savingAmount.value = ''
                savingCategory.value = ''
                statementAccepted.value = false
                paymentProof.value = null
                paymentFile.value = null
                depositMethod.value = 'Tunai'
                accountName.value = ''
                accountNumber.value = ''
                bankName.value = ''
                currentStep.value = 0

                window.scrollTo({ top: 0, behavior: 'smooth' })
            },

            onError: (errors) => {
                const messages = Object.values(errors).flat().join('<br>')

                toast(messages || 'Terjadi kesalahan saat mengajukan penyetoran', {
                    type: 'error',
                    position: 'bottom-right',
                    dangerouslyHTMLString: true,
                })
            },
        })
    })
}
</script>

<template>
    <Base>
        <div class="font-body min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8 dark:bg-gray-900 transition-colors">
            <!-- Step Indicator -->
            <div class="max-w-5xl mx-auto">
                <StepIndicator 
                    :steps="steps"
                    :current-step="currentStep"
                />
             </div>

            <!-- Step 1: Informasi Simpanan -->
            <BaseContainer
                v-show="currentStep === 0"
                title="INFORMASI SIMPANAN"
                subtitle="Periksa kembali informasi simpanan anda"
                :showDivider="true"
                contentClass="mt-6"
            >
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Nomor Anggota</p>
                            <p class="font-head font-medium text-gray-900 dark:text-gray-100 text-lg">
                                {{ memberData.member_number }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Nama Anggota</p>
                            <p class="font-head font-medium text-gray-900 dark:text-gray-100 text-lg">
                                {{ memberData.name }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                                Saldo Keseluruhan Simpanan
                            </p>
                            <div class="flex items-center gap-2">
                                <p class="font-head font-medium text-gray-900 dark:text-gray-100 text-lg">
                                    Rp. {{ formatRupiah(totalBalance) }}
                                </p>
                                <!-- Tooltip informasi simpanan -->
                                <span class="relative group cursor-pointer">
                                    <Icon
                                        icon="mdi:information-outline"
                                        class="text-gray-400 hover:text-blue-500"
                                        width="18"
                                        height="18"
                                    />
                                    <div
                                        class="absolute left-full top-1/2 -translate-y-1/2 ml-2 mt-12
                                            w-72 bg-white dark:bg-gray-800
                                            border border-gray-200 dark:border-gray-700
                                            rounded-lg shadow-lg
                                            opacity-0 group-hover:opacity-100
                                            pointer-events-none
                                            transition-opacity duration-200
                                            z-50"
                                    >
                                        <div class="p-4 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                                            <div class="grid grid-cols-2 gap-1.5">
                                                <span class="font-head">Simpanan Pokok</span>
                                                <span class="font-medium text-blue-500">
                                                    {{ formatRupiah(totalPerCategory.pokok) }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-1.5">
                                                <span class="font-head">Simpanan Wajib</span>
                                                <span class="font-medium text-blue-500">
                                                    {{ formatRupiah(totalPerCategory.wajib) }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 gap-1.5">
                                                <span class="font-head">Simpanan Sukarela</span>
                                                <span class="font-medium text-blue-500">
                                                    {{ formatRupiah(totalPerCategory.sukarela) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-end pt-6">
                        <button
                            @click="nextStep"
                            class="px-8 py-3 bg-blue-600 text-white font-head font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                        >
                            Selanjutnya
                        </button>
                    </div>
                </div>
            </BaseContainer>
            
            <!-- Step 2: Detail Penyetoran -->
            <BaseContainer
                v-show="currentStep === 1"
                title="DETAIL PENYETORAN SIMPANAN"
                subtitle="Isi detail permohonan penyetoran simpanan anda"
                :showDivider="true"
                contentClass="mt-6"
            >
                <div class="space-y-6">
                    <!-- Isian Nominal Simpanan -->
                        <div>
                            <label class="font-head block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nominal Simpanan <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                                <input
                                    v-model="savingAmount"
                                    @input="formatCurrencyInput(savingAmount)"
                                    type="text"
                                    placeholder="Masukan nominal simpanan"
                                    class="pl-10 w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="font-head block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kategori Simpanan <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="savingCategory"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="" disabled>Pilih kategori simpanan</option>
                                <option v-for="category in savingCategories" :key="category" :value="category">
                                    {{ category }}
                                </option>
                            </select>
                        </div>

                    <!-- Metode Penyetoran -->
                    <div>
                        <label class="font-head block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Metode Penyetoran <span class="text-red-500">*</span>
                        </label>

                        <div class="flex gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="radio"
                                    name="depositMethod"
                                    value="Tunai"
                                    v-model="depositMethod"
                                    class="text-blue-600"
                                />
                                <span>Tunai</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="radio"
                                    name="depositMethod"
                                    value="Non-Tunai"
                                    v-model="depositMethod"
                                    class="text-blue-600"
                                />
                                <span>Non-Tunai</span>
                            </label>
                        </div>
                    </div>

                    <!-- Detail Rekening untuk Non-Tunai -->
                    <div v-if="depositMethod === 'Non-Tunai'" class="space-y-6 mt-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Atas Nama Rekening -->
                            <div>
                                <label class="font-head block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Atas Nama Rekening <span class="text-red-500">*</span></label>
                                <input 
                                    list="accountNames"
                                    v-model="accountName"
                                    placeholder="Nama pemilik rekening"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                />
                                <datalist id="accountNames">
                                    <option v-for="opt in accountNameOptions" :key="opt.value" :value="opt.value" />
                                </datalist>
                            </div>

                            <!-- Nomor Rekening -->
                            <div>
                                <label class="font-head block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nomor Rekening <span class="text-red-500">*</span></label>
                                <input 
                                    list="accountNumbers"
                                    v-model="accountNumber"
                                    placeholder="Nomor rekening"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                />
                                <datalist id="accountNumbers">
                                    <option v-for="opt in accountNumberOptions" :key="opt.value" :value="opt.value" />
                                </datalist>
                            </div>
                        </div>

                        <!-- Nama Bank -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="font-head block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Bank <span class="text-red-500">*</span></label>
                                <select
                                    v-model="bankName"
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="" disabled>Pilih Bank</option>
                                    <option
                                        v-for="bank in bankOptions"
                                        :key="bank"
                                        :value="bank"
                                    >
                                        {{ bank }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Bukti Transaksi -->
                        <div>
                            <label class="font-head block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bukti Transaksi <span class="text-red-500">*</span>
                            </label>
                        
                            <div 
                                @click="() => fileInput?.click()"
                                class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors cursor-pointer"
                            >
                                <input
                                    ref="fileInput"
                                    type="file"
                                    @change="handleFileUpload"
                                    accept="image/*,.pdf"
                                    class="hidden"
                                />
                            
                                <div v-if="!paymentProof" class="space-y-3">
                                    <div class="flex justify-center text-gray-500 dark:text-gray-200">
                                        <Icon icon="lets-icons:upload" width="50" height="50" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                            Klik untuk upload atau drag & drop file
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            Format: JPG, PNG, PDF (maks. 2MB)
                                        </p>
                                    </div>
                                </div>

                                <!-- Preview File -->
                                <div v-else class="mt-4">
                                    <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <div class="text-blue-500">
                                                <Icon icon="akar-icons:file" width="30" height="30" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ paymentFile?.name || 'File' }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ paymentFile ? (paymentFile.size / 1024).toFixed(2) + ' KB' : '' }}
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

                            <p v-if="errorFile" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ errorFile }}
                            </p>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between pt-6">
                        <button
                            @click="prevStep"
                            class="px-6 py-3 border border-blue-300 dark:border-blue-600 text-blue-700 dark:text-blue-300 font-head font-medium rounded-lg hover:bg-blue-100 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                        >
                            Kembali
                        </button>
                        <button
                            @click="nextStep"
                            :disabled="!stepValidations[1].isValid"
                            class="px-8 py-3 bg-blue-600 text-white font-head font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Selanjutnya
                        </button>
                    </div>
                </div>
            </BaseContainer>

            <!-- Step 3: Konfirmasi -->
            <BaseContainer
                v-show="currentStep === 2"
                title="PERYATAAN DAN KONFIRMASI"
                subtitle="Periksa kembali detail informasi untuk penyetoran dan isi pernyataan"
                :showDivider="true"
                contentClass="mt-6"
            >
                <div class="space-y-6">
                    <!-- Rincian Simpanan -->
                    <h3 class="font-head font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Rincian Simpanan
                    </h3>
                    <div class="p-6 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">Tanggal Pengajuan:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ tanggalHariIni }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">Nominal Simpanan:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ savingAmount || formatRupiah(0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">Metode:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ depositMethod }}</span>
                            </div>
                            <div
                                v-if="depositMethod === 'Non-Tunai'"
                                class="flex justify-between"
                            >
                                <span class="text-gray-600 dark:text-gray-300">Sumber Dana:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ accountName }} ({{ accountNumber }})</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">Kategori:</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ savingCategory || 'Pilih kategori simpanan' }}</span>
                            </div>
                        </div>
                    </div>

                    <label class="flex items-start space-x-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <input
                            v-model="statementAccepted"
                            type="checkbox"
                            class="mt-1 h-5 w-5 text-blue-600 rounded focus:ring-blue-500"
                        />
                        <div>
                            <span class="font-head font-medium text-gray-900 dark:text-gray-100">Pernyataan</span>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                Saya menyatakan bahwa data yang diisi adalah benar dan dapat dipertanggungjawabkan sesuai peraturan yang berlaku
                            </p>
                        </div>
                    </label>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between pt-6">
                        <button
                            @click="prevStep"
                            class="px-6 py-3 border border-blue-300 dark:border-blue-600 text-blue-700 dark:text-blue-300 font-head font-medium rounded-lg hover:bg-blue-100 dark:hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                        >
                            Kembali
                        </button>
                        <button
                            @click="submitDeposit"
                            :disabled="!stepValidations[2].isValid"
                            class="px-8 py-3 bg-blue-600 text-white font-head font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Kirim
                        </button>
                    </div>
                </div>
            </BaseContainer>
        </div>
    </Base>
</template>