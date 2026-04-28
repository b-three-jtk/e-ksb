<script setup>
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { toast } from 'vue3-toastify'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import ConfirmationModal from '@/Components/Savings/ConfirmationModal.vue'
import Struk from '@/Components/Savings/Struk.vue'

const props = defineProps({
    saving_types: { type: Array, required: true },
    members: { type: Array, required: true },
    accounts: { type: Array, required: true },
    pengurus: { type: Object, required: true }
})

const memberQuery   = ref('')
const selectedMember = ref(null)

const memberSuggestions = computed(() => {
  const q = memberQuery.value.toLowerCase().trim()
  if (!q || q.length < 2) return []
  return props.members
    .filter(m =>
      m.name?.toLowerCase().includes(q) ||
      m.user_code?.toLowerCase().includes(q)
    )
    .slice(0, 6)
})

const showSuggestions = computed(() =>
  memberSuggestions.value.length > 0 && !selectedMember.value
)

function pilihAnggota(member) {
  selectedMember.value = member
  memberQuery.value    = member.name
  jenisSimpanan.value  = ''
}

function resetAnggota() {
  selectedMember.value = null
  memberQuery.value    = ''
  jenisSimpanan.value  = ''
}

watch(memberQuery, val => {
  if (selectedMember.value && val !== selectedMember.value.name) {
    selectedMember.value = null
  }
})

const jenisSimpanan  = ref('')
const nominalRaw     = ref('')
const nominalDisplay = ref('')
const tanggalSetor   = ref(today())
const catatan        = ref('')
const depositMethod  = ref('Tunai')
const errorTarget = ref('')
const errorNominal   = ref('')

// Field dinamis
const tenorMonths  = ref('')
const targetAmount = ref('')
const targetDisplay = ref('')

// Non-tunai
const bankName      = ref('')
const accountName   = ref('')
const accountNumber = ref('')
const paymentFile   = ref(null)
const paymentProof  = ref(null)
const errorFile     = ref('')
const fileInput     = ref(null)

// Dialog & konfirmasi
const showDialog        = ref(false)

watch(jenisSimpanan, () => {
  tenorMonths.value   = ''
  targetAmount.value  = ''
  targetDisplay.value = ''
})

watch(depositMethod, val => {
  if (val === 'Tunai') resetNonTunai()
})

watch(accountName, val => {
  const acc = filteredAccounts.value.find(a => a.account_name === val)
  if (acc) {
    accountNumber.value = acc.account_number
    bankName.value      = acc.bank_name
  }
})

watch(accountNumber, val => {
  const acc = filteredAccounts.value.find(a => a.account_number === val)
  if (acc) {
    accountName.value = acc.account_name
    bankName.value    = acc.bank_name
  }
})

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

function initials(name = '') {
  return name.split(' ').slice(0, 2).map(w => w[0]?.toUpperCase() || '').join('')
}

function tenorHint(months) {
  const m = Number(months)
  if (!m || m <= 0) return ''
  const years  = Math.floor(m / 12)
  const remain = m % 12
  const parts  = []
  if (years  > 0) parts.push(`${years} tahun`)
  if (remain > 0) parts.push(`${remain} bulan`)
  return 'Setara ' + parts.join(' ')
}

function onNominalInput(e) {
  const value = e.target.value
  errorNominal.value = /[^0-9.]/.test(value) ? 'Nominal hanya boleh angka' : ''
  const raw = value.replace(/\D/g, '')
  nominalRaw.value     = raw
  nominalDisplay.value = raw ? formatRp(raw) : ''
}

function onTargetInput(e) {
  const value = e.target.value

  errorTarget.value = /[^0-9.]/.test(value)
    ? 'Target hanya boleh angka'
    : ''

  const raw = value.replace(/\D/g, '')
  targetAmount.value  = raw
  targetDisplay.value = raw ? formatRp(raw) : ''
}

function resetNonTunai() {
  bankName.value = accountName.value = accountNumber.value = ''
  paymentFile.value = paymentProof.value = null
  errorFile.value = ''
  if (fileInput.value) fileInput.value.value = ''
}

const bankOptions = ['BCA','BNI','BRI','Mandiri','BTN','CIMB Niaga','Permata','Danamon','BSI','BJB']

const filteredAccounts = computed(() => {
  if (!selectedMember.value) return []
  return props.accounts.filter(a => a.user_id === selectedMember.value.id)
})

const accountNameOptions   = computed(() => filteredAccounts.value.map(a => a.account_name))
const accountNumberOptions = computed(() => filteredAccounts.value.map(a => a.account_number))

function handleFileUpload(e) {
  const file  = e.target.files[0]
  if (!file) return
  const valid = ['image/jpeg','image/png','image/jpg','application/pdf']
  if (!valid.includes(file.type)) { errorFile.value = 'Hanya JPG, PNG, atau PDF'; return }
  if (file.size > 2 * 1024 * 1024) { errorFile.value = 'Maksimal 2 MB'; return }
  paymentFile.value  = file
  paymentProof.value = URL.createObjectURL(file)
  errorFile.value    = ''
}

function removeFile() {
  paymentFile.value = paymentProof.value = null
  errorFile.value   = ''
  if (fileInput.value) fileInput.value.value = ''
}

const selectedAccount = computed(() => {
  if (!selectedMember.value || !jenisSimpanan.value) return null
  return (selectedMember.value.savingAccounts || []).find(
    acc => acc.saving_type === jenisSimpanan.value
  ) || null
})

const isNewAccount = computed(() => !selectedAccount.value)

// Validasi
const errorsForm = computed(() => {
  const e = {}
  if (!selectedMember.value) e.anggota = 'Pilih anggota dulu'
  if (!jenisSimpanan.value)  e.jenis   = 'Pilih jenis simpanan'
  if (!nominalRaw.value || Number(nominalRaw.value) <= 0) e.nominal = 'Masukkan nominal valid'
  if (!tanggalSetor.value)           e.tanggal = 'Pilih tanggal'
  if (tanggalSetor.value > today())  e.tanggal = 'Tanggal tidak boleh di masa depan'

  if (isNewAccount.value) {
    if (jenisSimpanan.value === 'Tabungan Berjangka' && !tenorMonths.value)
      e.tenor = 'Jangka waktu wajib diisi'
    if (jenisSimpanan.value === 'Tabungan Ibadah' && !targetAmount.value)
      e.target = 'Target wajib diisi'
  }

  if (depositMethod.value === 'Non-Tunai') {
    if (!bankName.value)      e.bank          = 'Pilih bank'
    if (!accountNumber.value) e.accountNumber = 'Isi nomor rekening'
    if (!accountName.value)   e.accountName   = 'Isi atas nama'
    if (!paymentFile.value)   e.bukti         = 'Upload bukti pembayaran'
  }
  return e
})

const isFormValid = computed(() => Object.keys(errorsForm.value).length === 0)

// Struk
const showStruk = ref(false)
const dataStruk = ref(null)

// Submit
function bukaDialog() {
  if (!isFormValid.value) {
    toast('Lengkapi data yang wajib diisi', { type: 'warning' })
    return
  }
  showDialog.value = true
}

const confirmationData = computed(() => ({
  memberName: selectedMember.value?.name,
  memberNumber: selectedMember.value?.user_code,
  savingType: jenisSimpanan.value,
  method: depositMethod.value,
  amount: nominalRaw.value,
  date: tanggalSetor.value,
  tenorMonths: tenorMonths.value,
  targetAmount: targetAmount.value,
  officerName: props.pengurus.value?.name,
}))

function handleConfirm() {
  const formData = new FormData()

  formData.append('member_id', selectedMember.value.id)
  formData.append('saving_category', jenisSimpanan.value)
  formData.append('amount', nominalRaw.value)
  formData.append('date', tanggalSetor.value)
  formData.append('method', depositMethod.value)
  formData.append('notes', catatan.value)

  if (isNewAccount.value) {
    formData.append('tenor_months', tenorMonths.value)
    formData.append('target_amount', targetAmount.value)
  }

  if (depositMethod.value === 'Non-Tunai') {
    formData.append('bank_name', bankName.value)
    formData.append('account_name', accountName.value)
    formData.append('account_number', accountNumber.value)
    formData.append('payment_proof', paymentFile.value)
  }

  router.post('/admin/saving/deposit', formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: (page) => {
      toast('Penyetoran berhasil!', { type: 'success', position: 'top-center' })
      dataStruk.value = page.props.struk
      showStruk.value = true
      showDialog.value = false
      resetForm()
    },
    onError: (errors) => {
      showDialog.value = false
      const msg = Object.values(errors).flat().join('\n')
      toast(msg || 'Gagal menyimpan', { type: 'error' })
    }
  })
}

function resetForm() {
  resetAnggota()
  jenisSimpanan.value  = ''
  nominalRaw.value     = ''
  nominalDisplay.value = ''
  tanggalSetor.value   = today()
  catatan.value        = ''
  depositMethod.value  = 'Tunai'
  konfirmasiChecked.value = false
  tenorMonths.value    = ''
  targetAmount.value   = ''
  targetDisplay.value  = ''
  resetNonTunai()
}

const breadcrumbItems = [
  { name: 'Dashboard',              link: '/admin' },
  { name: 'Pengelolaan Simpanan',   link: '/admin/savings/list' },
  { name: 'Penyetoran Simpanan' },
]

const akadType = computed(() => {
  switch (jenisSimpanan.value) {
    case 'Simpanan Pokok':
    case 'Simpanan Wajib':
      return 'musyarakah'

    case 'Tabungan Anggota':
      return 'wadiah'

    case 'Tabungan Ibadah':
    case 'Tabungan Berjangka':
      return 'mudharabah'

    default:
      return null
  }
})
</script>

<template>
  <AdminLayout title="Penyetoran Simpanan">
    <PageBreadcrumb page-title="Penyetoran Simpanan" :items="breadcrumbItems" />

    <div class="py-6 px-4 sm:px-6 lg:px-8">
      <div class="w-full px-4 sm:px-10 lg:px-10 space-y-6 font-body">

        <!-- ══ Pilih Anggota ══ -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
          <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xs font-semibold tracking-widest text-gray-500 dark:text-gray-400 uppercase font-head">
              Data Anggota
            </h2>
          </div>

          <div class="p-5 space-y-4">
            <!-- Search box -->
            <div class="relative">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                Cari Anggota <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <Icon icon="mdi:magnify" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <input
                  v-model="memberQuery"
                  type="text"
                  placeholder="Nama / No. Anggota..."
                  class="pl-10 w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600
                         rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                         focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <!-- Suggestions dropdown -->
              <div
                v-if="showSuggestions"
                class="absolute z-10 w-full bg-white dark:bg-gray-800 border border-gray-200
                       dark:border-gray-600 rounded-lg shadow-lg mt-1 max-h-64 overflow-auto"
              >
                <button
                  v-for="m in memberSuggestions" :key="m.id"
                  @click="pilihAnggota(m)"
                  class="w-full text-left px-4 py-2.5 hover:bg-blue-50 dark:hover:bg-gray-700
                         flex items-center gap-3 transition-colors"
                >
                  <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center
                              justify-center text-blue-700 dark:text-blue-300 font-semibold text-sm shrink-0">
                    {{ initials(m.name) }}
                  </div>
                  <div>
                    <div class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ m.name }}</div>
                    <div class="text-xs text-gray-500">{{ m.user_code }}</div>
                  </div>
                </button>
              </div>
            </div>

            <!-- Selected member card -->
            <Transition name="fade">
              <div
                v-if="selectedMember"
                class="flex items-center gap-4 p-4 bg-blue-50 dark:bg-blue-900/20
                       border border-blue-100 dark:border-blue-800 rounded-lg"
              >
                <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center
                            justify-center text-xl font-bold text-blue-700 dark:text-blue-300 shrink-0">
                  {{ initials(selectedMember.name) }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ selectedMember.name }}</div>
                  <div class="text-sm text-gray-500">{{ selectedMember.user_code }}</div>
                </div>
                <button @click="resetAnggota" class="text-red-400 hover:text-red-600 transition-colors shrink-0">
                  <Icon icon="mdi:close" width="20" />
                </button>
              </div>
            </Transition>
          </div>
        </div>

        <!-- Detail Penyetoran -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xs font-semibold tracking-widest text-gray-500 dark:text-gray-400 uppercase font-head">
              Detail Penyetoran
            </h2>
          </div>

          <div class="p-5 space-y-5">

            <!-- Peringatan jika anggota belum dipilih -->
            <Transition name="fade">
              <div
                v-if="!selectedMember"
                class="flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/20
                       border border-amber-200 dark:border-amber-700 rounded-lg"
              >
                <Icon icon="mdi:account-alert-outline" class="text-amber-500 shrink-0" width="22" />
                <p class="text-sm text-amber-700 dark:text-amber-300">
                  Pilih anggota terlebih dahulu untuk mengisi detail penyetoran.
                </p>
              </div>
            </Transition>

            <!-- Fieldset disable jika belum pilih anggota -->
            <fieldset
              :disabled="!selectedMember"
              class="space-y-5 transition-opacity duration-200"
              :class="{ 'opacity-40 pointer-events-none select-none': !selectedMember }"
            >

              <!-- Jenis simpanan -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                  Jenis Simpanan <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="jenisSimpanan"
                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                         bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                         focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="" disabled>— Pilih jenis simpanan —</option>
                  <option v-for="j in saving_types" :key="j" :value="j">{{ j }}</option>
                </select>
              </div>

              <!-- Informasi Akad -->
              <Transition name="fade">
                <div
                  v-if="akadType"
                  class="flex items-start gap-3 p-4 rounded-lg border
                        bg-blue-50 dark:bg-blue-900/20
                        border-blue-200 dark:border-blue-800"
                >
                  <div class="text-sm">
                    <div class="font-semibold text-blue-700 dark:text-blue-300 mb-1">
                      {{
                        akadType === 'musyarakah'
                          ? 'Akad Musyarakah'
                          : akadType === 'wadiah'
                          ? 'Akad Wadiah Yad Dhamanah'
                          : 'Akad Mudharabah Mutlaqah'
                      }}
                    </div>

                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                      {{
                        akadType === 'musyarakah'
                          ? 'Akad antara dua pihak atau lebih yang bersepakat menjalankan usaha bersama dengan pembagian keuntungan sesuai porsi modal dan menanggung risiko secara proporsional tanpa jaminan imbal hasil tetap.'
                          : akadType === 'wadiah'
                          ? 'Akad penitipan dana di mana penerima titipan diperbolehkan memanfaatkan dana tersebut namun wajib menjamin pengembalian pokoknya, pihak koperasi syariah diperbolehkan (tidak wajib) memberikan bonus kepada pemilik dana.'
                          : 'Akad kerja sama antara pemilik dana (shahibul maal) dan pengelola (mudharib) di mana koperasi diberi kebebasan penuh mengelola dana dalam usaha halal. Keuntungan dibagi sesuai nisbah yang disepakati, sedangkan kerugian ditanggung pemilik dana selama tidak terdapat kelalaian dari pengelola.'
                      }}
                    </p>
                  </div>
                </div>
              </Transition>

              <!-- Field dinamis: Tenor & Target -->
              <Transition name="fade">
                <div v-if="isNewAccount && jenisSimpanan" class="space-y-4">

                  <!-- Tabungan Berjangka — Tenor -->
                  <div v-if="jenisSimpanan === 'Tabungan Berjangka'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                      Jangka Waktu <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                      <input
                        v-model="tenorMonths"
                        type="number"
                        min="1"
                        max="360"
                        placeholder="Contoh: 12"
                        class="w-full px-4 py-2.5 pr-20 border rounded-lg
                               bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                               focus:outline-none focus:ring-2 transition-colors"
                        :class="errorsForm.tenor
                          ? 'border-red-400 focus:ring-red-400 dark:border-red-500'
                          : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                      />
                      <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none">
                        bulan
                      </span>
                    </div>
                    <p v-if="errorsForm.tenor" class="mt-1 text-xs text-red-500 flex items-center gap-1">
                      <Icon icon="mdi:alert-circle-outline" width="13" />
                      {{ errorsForm.tenor }}
                    </p>
                    <p v-else-if="tenorMonths && Number(tenorMonths) > 0" class="mt-1 text-xs text-gray-400">
                      {{ tenorHint(tenorMonths) }}
                    </p>
                  </div>

                  <!-- Tabungan Ibadah — Target -->
                  <div v-if="jenisSimpanan === 'Tabungan Ibadah'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                      Target Tabungan <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 pointer-events-none">
                        Rp
                      </span>
                      <input
                        :value="targetDisplay"
                        @input="onTargetInput"
                        type="text"
                        inputmode="numeric"
                        placeholder="0"
                        class="w-full pl-10 pr-4 py-2.5 border rounded-lg
                               bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                               focus:outline-none focus:ring-2 transition-colors"
                        :class="errorsForm.target
                          ? 'border-red-400 focus:ring-red-400 dark:border-red-500'
                          : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500'"
                      />
                    </div>
                    <p v-if="errorsForm.target" class="mt-1 text-xs text-red-500 flex items-center gap-1">
                      <Icon icon="mdi:alert-circle-outline" width="13" />
                      {{ errorsForm.target }}
                    </p>
                    <p v-else-if="targetAmount" class="mt-1 text-xs text-gray-400">
                      Target: Rp {{ formatRp(targetAmount) }}
                    </p>
                    <p v-if="errorTarget" class="mt-1 text-xs text-red-600 flex items-center gap-1">
                    <Icon icon="mdi:alert-circle-outline" width="13" />
                    {{ errorTarget }}
                  </p>
                  </div>

                  <!-- Info rekening baru -->
                  <div class="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/20
                              border border-amber-200 dark:border-amber-700 rounded-lg">
                    <Icon icon="mdi:information-outline" class="text-amber-500 mt-0.5 shrink-0" width="16" />
                    <p class="text-xs text-amber-600 dark:text-amber-400">
                      Rekening simpanan akan dibuat otomatis saat transaksi diposting.
                    </p>
                  </div>

                </div>
              </Transition>

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

              <!-- Tanggal & Catatan -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">
                    Tanggal Setor <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="tanggalSetor"
                    type="date"
                    :max="today()"
                    readonly
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                           bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 font-head">Catatan</label>
                  <input
                    v-model="catatan"
                    type="text"
                    placeholder="Opsional"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                           bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                           focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
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

            </fieldset>
            <!-- Akhir fieldset -->

          </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="flex justify-between">
          <button
            @click="resetForm"
            class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm
                   text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            Reset
          </button>
          <button
            @click="bukaDialog"
            :disabled="!isFormValid"
            class="px-8 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg
                   hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Posting
          </button>
        </div>

      </div>
    </div>

    <ConfirmationModal
      :isOpen="showDialog"
      type="deposit"
      :data="confirmationData"
      @confirm="handleConfirm"
      @close="showDialog = false"
    />

    <div
      v-if="showStruk && dataStruk"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
    >
      <div class="bg-white rounded-xl shadow-lg p-4 max-w-sm w-full relative">

        <!-- tombol close -->
        <!-- <button
          @click="showStruk = false"
          class="absolute top-2 right-2 text-gray-500 hover:text-red-500"
        >
          ✕
        </button> -->

        <Struk
          :transaksi="dataStruk"
          mode="deposit"
        />
      </div>
    </div>
  </AdminLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,  .fade-leave-to      { opacity: 0; }

.slide-enter-active, .slide-leave-active { transition: all 0.25s ease; overflow: hidden; }
.slide-enter-from,   .slide-leave-to     { opacity: 0; max-height: 0; }

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from,   .modal-leave-to     { opacity: 0; }
</style>
