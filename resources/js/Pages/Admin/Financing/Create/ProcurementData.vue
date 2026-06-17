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
    searchSupplierQuery: String,
    isLoadingSearchSupplier: Boolean,
    isSupplierSelected: Boolean,
    supplierResults: Array,
    errors: Object,
})

const emit = defineEmits([
    'update:searchSupplierQuery',
    'selectSupplier',
    'resetSupplierSelection',
    'validate-field',
])

// Hitung cost_price & margin otomatis dari price_per_unit × qty
watch(() => props.form.financing.price_per_unit, () => {
    const costPrice = (parseFloat(props.form.financing.price_per_unit) || 0)
        * (parseFloat(props.form.financing.qty) || 0)
    props.form.financing.cost_price = costPrice
    props.form.financing.margin_amount = costPrice * (props.data.margin_percentage / 100)
}, { immediate: true })

const totalPrice = computed(() => {
    const costPrice = parseFloat(props.form.financing.cost_price) || 0
    const marginAmount = parseFloat(props.form.financing.margin_amount) || 0
    const downPayment = parseFloat(props.form.financing.down_payment) || 0
    return costPrice + marginAmount - downPayment
})

const showNewSupplierInput = ref(false)
const newSupplierName = ref('')
const newSupplierAddress = ref('')
const newSupplierContact = ref('')
const isCreatingSupplier = ref(false)

const supplierSelectables = computed(() => {
    const items = props.data.suppliers.map((pt) => ({
        value: pt.id,
        text: pt.supplier_name,
    }))
    return [
        ...items,
        { value: 'NEW', text: '+ Tambah Supplier Baru', isAction: true },
    ]
})

const handleSupplierChange = (value) => {
    if (value === 'NEW') {
        showNewSupplierInput.value = true
        props.form.financing.supplier_id = null
        props.form.supplier.supplier_name = ''
        props.form.supplier.address = ''
        props.form.supplier.contact = ''
    } else {
        showNewSupplierInput.value = false
        props.form.financing.supplier_id = value

        const selectedSupplier = props.data.suppliers.find(s => String(s.id) === String(value))
        props.form.supplier.supplier_name = selectedSupplier?.supplier_name || ''
        props.form.supplier.address = selectedSupplier?.address || ''
        props.form.supplier.contact = selectedSupplier?.contact || ''
    }
}

const createNewSupplier = async () => {
    if (!newSupplierName.value.trim()) return
    isCreatingSupplier.value = true
    try {
        const response = await axios.post('/admin/suppliers', {
            supplier_name: newSupplierName.value,
            address: newSupplierAddress.value,
            contact: props.form.supplier.contact,
        })

        props.data.suppliers.push(response.data)

        props.form.financing.supplier_id = response.data.id
        props.form.supplier.supplier_name = response.data.supplier_name || newSupplierName.value
        props.form.supplier.address = response.data.address || newSupplierAddress.value
        props.form.supplier.contact = response.data.contact || props.form.supplier.contact

        // Reset state modal
        newSupplierName.value = ''
        newSupplierAddress.value = ''
        newSupplierContact.value = ''
        showNewSupplierInput.value = false
    } catch (error) {
        console.error('Error creating supplier:', error)
        toast('Gagal membuat supplier', {
            type: 'error',
            position: 'bottom-right',
        })
    } finally {
        isCreatingSupplier.value = false
    }
}

const closeModal = () => {
    showNewSupplierInput.value = false
    newSupplierName.value = ''
    newSupplierAddress.value = ''
    newSupplierContact.value = ''
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
                <BaseInputAdmin v-model.number="form.financing.price_per_unit" label="Harga Per Item" required isMoney
                    placeholder="Masukkan harga per item" :error="errors?.cost_price"
                    @input="onFieldChange('cost_price')" />
                <Info label="Harga Perolehan Barang" :value="parseCurrencyAmount(form.financing.cost_price)" />
                <Info label="Uang Muka" :value="parseCurrencyAmount(form.financing.down_payment)" />
                <Info :label="`Margin (${data.margin_percentage}%)`" :value="parseCurrencyAmount(form.financing.margin_amount)" />
            </div>

            <div class="bg-light-bg flex justify-between border px-8 py-4 mt-6 rounded-lg">
                <div class="font-semibold text-primary">Total Pembiayaan Murabahah</div>
                <div class="font-semibold text-primary">{{ parseCurrencyAmount(totalPrice) }}</div>
            </div>

            <!-- Wakalah toggle -->
            <div class="mt-6 flex items-center gap-2">
                <input v-model="form.is_wakalah" type="checkbox" id="wakalah"
                    class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary-500" />
                <label for="wakalah" class="text-sm text-gray-700 dark:text-gray-300">
                    Pengadaan dengan Skema Wakalah
                </label>
            </div>

            <!-- Wakalah section -->
            <div v-if="form.is_wakalah || form.financing.akad_wakalah_date" class="grid grid-cols-2 items-end gap-6 mt-4">
                <a href="/docs/AkadWakalah.docx" target="_blank"
                    class="border border-gray-300 flex justify-between rounded-lg p-4">
                    <div class="text-sm text-primary hover:underline">
                        Unduh Dokumen Akad Wakalah
                    </div>
                    <span class="icon-[tabler--download] text-green-500"></span>
                </a>
                <div class="col-span-2 grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <BaseInputAdmin type="file" label="Upload Dokumen Wakalah Tertandatangani"
                            v-model="form.akad_wakalah_file" accept="application/pdf" required />
                        <div class="flex justify-between text-xs text-gray-400">
                            <p>Format: PDF</p>
                            <p>Max. 2 MB per file</p>
                        </div>
                    </div>
                    <BaseInputAdmin v-model="form.financing.akad_wakalah_date" required label="Tanggal Akad Wakalah"
                        errors="errors?.akad_wakalah_date"
                        type="date" />
                </div>
            </div>
        </div>

        <!-- Informasi Pemasok -->
        <div class="card-layout mx-4">
            <h1 class="card-title">Informasi Pemasok</h1>
            <div class="grid grid-cols-2 gap-4 pt-4">

                <!-- Supplier search / input -->
                <BaseInputAdmin v-model="form.financing.supplier_id" label="Pemasok" type="select"
                    :selectables="supplierSelectables" @update:modelValue="handleSupplierChange" />
                <BaseInputAdmin v-model="form.supplier.contact" label="Kontak" type="text"
                    placeholder="Masukkan kontak pemasok" />
                <BaseInputAdmin v-model="form.supplier.address" label="Alamat" type="textarea" rows="3"
                    placeholder="Masukkan alamat pemasok" />

            </div>
        </div>
    </section>

    <Teleport to="body">
        <div v-if="showNewSupplierInput" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-md">
                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-200 mb-4">Tambah Pemasok Baru</h2>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Pemasok</label>
                    <input v-model="newSupplierName" type="text" placeholder="Masukkan nama pemasok..."
                        class="w-full px-4 py-2 border border-gray-300 dark:text-gray-300 font-body rounded-lg focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 focus:outline-none"
                        @keyup.enter="createNewSupplier" />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alamat Pemasok</label>
                    <input v-model="newSupplierAddress" type="text" placeholder="Masukkan alamat pemasok..."
                        class="w-full px-4 py-2 border border-gray-300 dark:text-gray-300 font-body rounded-lg focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 focus:outline-none"
                        @keyup.enter="createNewSupplier" />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kontak Pemasok</label>
                    <input v-model="props.form.supplier.contact" type="text" placeholder="Masukkan kontak pemasok..."
                        class="w-full px-4 py-2 border border-gray-300 dark:text-gray-300 font-body rounded-lg focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 focus:outline-none"
                        @keyup.enter="createNewSupplier" />
                </div>
                <div class="flex gap-3 justify-end">
                    <button @click="closeModal"
                        class="px-4 py-2 bg-gray-300 text-gray-900 rounded-lg hover:bg-gray-400 transition font-medium">
                        Batal
                    </button>
                    <button @click="createNewSupplier" :disabled="isCreatingSupplier || !newSupplierName.trim()"
                        class="px-6 py-2 bg-primary hover:bg-secondary text-white rounded-lg disabled:bg-gray-400 disabled:cursor-not-allowed transition font-medium">
                        <span v-if="!isCreatingSupplier">Buat</span>
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
