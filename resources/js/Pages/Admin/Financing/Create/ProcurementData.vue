<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Info from '@/Components/Form/Info.vue'
import parseCurrencyAmount from '@/Composables/moneyParser.js'
import { computed, watch } from 'vue'
import { toast } from 'vue3-toastify'
import { ref } from 'vue'

const props = defineProps({
    form: Object,
    data: Object,
    searchPemasokQuery: String,
    isLoadingSearchPemasok: Boolean,
    isPemasokSelected: Boolean,
    pemasokResults: Array,
    errors: Object,
})

const emit = defineEmits([
    'update:searchPemasokQuery',
    'selectPemasok',
    'resetPemasokSelection',
    'validate-field',
])

// Hitung harga_perolehan & margin otomatis dari harga_beli_per_unit × kuantitas
watch(() => props.form.pembiayaan.harga_beli_per_unit, () => {
    const costPrice = (parseFloat(props.form.pembiayaan.harga_beli_per_unit) || 0)
        * (parseFloat(props.form.pembiayaan.kuantitas) || 0)
    props.form.pembiayaan.harga_perolehan = costPrice
    props.form.pembiayaan.margin_keuntungan = costPrice * (props.data.margin_percentage / 100)
}, { immediate: true })

const totalPrice = computed(() => {
    const costPrice = parseFloat(props.form.pembiayaan.harga_perolehan) || 0
    const marginAmount = parseFloat(props.form.pembiayaan.margin_keuntungan) || 0
    const downPayment = parseFloat(props.form.pembiayaan.uang_muka) || 0
    return costPrice + marginAmount - downPayment
})

const showNewPemasokInput = ref(false)
const newPemasokName = ref('')
const newPemasokAddress = ref('')
const newPemasokContact = ref('')
const isCreatingPemasok = ref(false)

const pemasokSelectables = computed(() => {
    const items = props.data.pemasok.map((pt) => ({
        value: pt.id,
        text: pt.nama_pemasok,
    }))
    return [
        ...items,
        { value: 'NEW', text: '+ Tambah Pemasok Baru', isAction: true },
    ]
})

const handlePemasokChange = (value) => {
    if (value === 'NEW') {
        showNewPemasokInput.value = true
        props.form.pembiayaan.pemasok_id = null
        props.form.pemasok.nama_pemasok = ''
        props.form.pemasok.alamat_pemasok = ''
        props.form.pemasok.kontak_pemasok = ''
    } else {
        showNewPemasokInput.value = false
        props.form.pembiayaan.pemasok_id = value

        const selectedPemasok = props.data.pemasok.find(s => String(s.id) === String(value))
        props.form.pemasok.nama_pemasok = selectedPemasok?.nama_pemasok || ''
        props.form.pemasok.alamat_pemasok = selectedPemasok?.alamat_pemasok || ''
        props.form.pemasok.kontak_pemasok = selectedPemasok?.kontak_pemasok || ''
    }
}

const createNewPemasok = async () => {
    if (!newPemasokName.value.trim()) return
    isCreatingPemasok.value = true
    try {
        const response = await axios.post('/admin/pemasok', {
            nama_pemasok: newPemasokName.value,
            alamat_pemasok: newPemasokAddress.value,
            kontak_pemasok: newPemasokContact.value,
        })

        props.data.pemasok.push(response.data)

        props.form.pembiayaan.pemasok_id = response.data.id
        props.form.pemasok.nama_pemasok = response.data.nama_pemasok || newPemasokName.value
        props.form.pemasok.alamat_pemasok = response.data.alamat_pemasok || newPemasokAddress.value
        props.form.pemasok.kontak_pemasok = response.data.kontak_pemasok || newPemasokContact.value

        // Reset state modal
        newPemasokName.value = ''
        newPemasokAddress.value = ''
        newPemasokContact.value = ''
        showNewPemasokInput.value = false
    } catch (error) {
        console.error('Error creating pemasok:', error)
        toast('Gagal membuat pemasok', {
            type: 'error',
            position: 'bottom-right',
        })
    } finally {
        isCreatingPemasok.value = false
    }
}

const closeModal = () => {
    showNewPemasokInput.value = false
    newPemasokName.value = ''
    newPemasokAddress.value = ''
    newPemasokContact.value = ''
}

const onFieldChange = (field) => emit('validate-field', field)
</script>

<template>
    <section class="flex flex-col gap-6">

        <!-- Pengadaan Barang -->
        <div class="card-layout mx-4">
            <h1 class="card-title">Pengadaan Barang</h1>
            <div class="grid grid-cols-2 gap-4 pt-4">
                <div>
                    <BaseInputAdmin type="file" label="Bukti Pembelian" v-model="form.purchase_receipt_file"
                        accept=".jpg,.jpeg,.png" required :error="errors?.purchase_receipt_file"
                        @change="onFieldChange('purchase_receipt_file')" />
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <p>Format: JPG, JPEG, PNG</p>
                        <p>Max. 2 MB per file</p>
                    </div>
                </div>
                <BaseInputAdmin v-model.number="form.pembiayaan.harga_beli_per_unit" label="Harga Per Item" required isMoney
                    placeholder="Masukkan harga per item" :error="errors?.harga_perolehan"
                    @input="onFieldChange('harga_perolehan')" />
                <Info label="Harga Perolehan Barang" :value="parseCurrencyAmount(form.pembiayaan.harga_perolehan)" />
                <Info label="Uang Muka" :value="parseCurrencyAmount(form.pembiayaan.uang_muka)" />
                <Info :label="`Margin (${data.margin_percentage}%)`" :value="parseCurrencyAmount(form.pembiayaan.margin_keuntungan)" />
            </div>

            <div class="bg-light-bg flex justify-between border px-8 py-4 mt-6 rounded-lg">
                <div class="font-semibold text-primary">Total Pembiayaan Murabahah</div>
                <div class="font-semibold text-primary">{{ parseCurrencyAmount(totalPrice) }}</div>
            </div>

        </div>

        <!-- Informasi Pemasok -->
        <div class="card-layout mx-4">
            <h1 class="card-title">Informasi Pemasok</h1>
            <div class="grid grid-cols-2 gap-4 pt-4">

                <!-- Pemasok search / input -->
                <BaseInputAdmin v-model="form.pembiayaan.pemasok_id" label="Pemasok" type="select"
                    :selectables="pemasokSelectables" @update:modelValue="handlePemasokChange" />
                <BaseInputAdmin v-model="form.pemasok.kontak_pemasok" label="Kontak" type="text"
                    placeholder="Masukkan kontak pemasok" />
                <BaseInputAdmin v-model="form.pemasok.alamat_pemasok" label="Alamat" type="textarea" rows="3"
                    placeholder="Masukkan alamat pemasok" />

            </div>
        </div>
    </section>

    <Teleport to="body">
        <div v-if="showNewPemasokInput" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-200 mb-4">Tambah Pemasok Baru</h2>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Pemasok</label>
                    <input v-model="newPemasokName" type="text" placeholder="Masukkan nama pemasok..."
                        class="w-full px-4 py-2 border border-gray-300 dark:text-gray-300 font-body rounded-lg focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 focus:outline-none"
                        @keyup.enter="createNewPemasok" />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alamat Pemasok</label>
                    <input v-model="newPemasokAddress" type="text" placeholder="Masukkan alamat pemasok..."
                        class="w-full px-4 py-2 border border-gray-300 dark:text-gray-300 font-body rounded-lg focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 focus:outline-none"
                        @keyup.enter="createNewPemasok" />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kontak Pemasok</label>
                    <input v-model="newPemasokContact" type="text" placeholder="Masukkan kontak pemasok..."
                        class="w-full px-4 py-2 border border-gray-300 dark:text-gray-300 font-body rounded-lg focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 focus:outline-none"
                        @keyup.enter="createNewPemasok" />
                </div>
                <div class="flex gap-3 justify-end">
                    <button @click="closeModal"
                        class="px-4 py-2 bg-gray-300 text-gray-900 rounded-lg hover:bg-gray-400 transition font-medium">
                        Batal
                    </button>
                    <button @click="createNewPemasok" :disabled="isCreatingPemasok || !newPemasokName.trim()"
                        class="px-6 py-2 bg-primary hover:bg-secondary text-white rounded-lg disabled:bg-gray-400 disabled:cursor-not-allowed transition font-medium">
                        <span v-if="!isCreatingPemasok">Buat</span>
                        <span v-else class="flex items-center gap-2">
                            <div class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" />
                            Membuat...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
