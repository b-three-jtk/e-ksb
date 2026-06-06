<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import { ref, computed } from 'vue'
import { Transition } from 'vue'

const props = defineProps({
    form: Object,
    data: Object,
})

const showNewProductTypeInput = ref(false)
const newProductTypeName = ref('')
const isCreatingProductType = ref(false)

const productTypeSelectables = computed(() => {
    const items = props.data.productTypes.map((pt) => ({
        value: pt.id,
        text: pt.product_type_name
    }))

    return [
        ...items,
        { value: 'NEW', text: '+ Tambah Kategori Baru', isAction: true }
    ]
})

const handleProductTypeChange = (value) => {
    if (value === 'NEW') {
        showNewProductTypeInput.value = true
        props.form.financing.product_type_id = null
    } else {
        showNewProductTypeInput.value = false
        props.form.financing.product_type_id = value
    }
}

const createNewProductType = async () => {
    if (!newProductTypeName.value.trim()) return

    isCreatingProductType.value = true
    try {
        const response = await axios.post('/admin/product-types', {
            product_type_name: newProductTypeName.value
        })

        props.data.productTypes.push(response.data)
        props.form.financing.product_type_id = response.data.id

        newProductTypeName.value = ''
        showNewProductTypeInput.value = false
    } catch (error) {
        console.error('Error creating product type:', error)
        alert('Gagal membuat kategori produk')
    } finally {
        isCreatingProductType.value = false
    }
}

const closeModal = () => {
    showNewProductTypeInput.value = false
    newProductTypeName.value = ''
}
</script>

<template>
    <section>
        <div class="border-b border-gray-200 px-8 pb-4">
            <h1 class="card-title">Objek Pembiayaan</h1>
        </div>
        <div class="grid grid-cols-2 gap-4 p-4">
            <Transition name="fade" class="col-span-2">
                <div class="flex items-start gap-3 p-4 rounded-lg border
                        bg-light-bg dark:bg-blue-900/20
                        border-brand-200 dark:border-blue-800">
                    <div class="text-sm">
                        <div class="font-semibold text-primary dark:text-blue-300 mb-1">
                            Ketentuan Objek Pembiayaan Murabahah (No. 111/DSN-MUI/IX/2017)
                        </div>
                        <ol class="text-gray-600 dark:text-gray-300 leading-relaxed ml-3">
                            <li class="list-disc">Barang halal menurut syariah,</li>
                            <li class="list-disc">Memiliki spesifikasi jelas,</li>
                            <li class="list-disc">Dapat diserahterimakan,</li>
                            <li class="list-disc">Bermanfaat dan tidak dilarang oleh peraturan perundang-undangan.</li>
                        </ol>
                    </div>
                </div>
            </Transition>
            <BaseInputAdmin v-model="form.financing.name" label="Nama Produk" placeholder="Masukkan nama produk"
                required />
            <BaseInputAdmin v-model="form.financing.product_type_id" label="Kategori Produk" type="select"
                :selectables="productTypeSelectables" @update:modelValue="handleProductTypeChange" />
            <BaseInputAdmin required v-model="form.financing.condition" label="Kondisi" type="select"
                :selectables="data.conditions.map((c) => ({ value: c, text: c }))" />
            <BaseInputAdmin required v-model="form.financing.qty" label="Jumlah" type="number" />
            <BaseInputAdmin v-model="form.financing.specification" label="Deskripsi" type="textarea" rows="4"
                placeholder="Masukkan deskripsi produk" />
            <BaseInputAdmin v-model="form.financing.predicted_cost_price" label="Harga Perkiraan" isMoney />
        </div>
        <div class="border-y border-gray-200 px-8 py-4">
            <h1 class="card-title">Jaminan (Rahn)</h1>
        </div>
        <div class="grid grid-cols-2 gap-4 p-4">
            <BaseInputAdmin v-model="form.collateral.collateral_type" label="Jenis Agunan"
                placeholder="Masukkan jenis agunan" required />
            <BaseInputAdmin v-model="form.collateral.owner_name" required label="Atas Nama"
                placeholder="Masukkan nama pemilik" />
            <BaseInputAdmin v-model="form.collateral.estimated_market_value" label="Nilai Perkiraan Pasar" isMoney
                placeholder="Masukkan nilai perkiraan pasar" />
            <BaseInputAdmin v-model="form.collateral.collateral_location" label="Lokasi/Kondisi Agunan" type="textarea"
                rows="4" placeholder="Masukkan lokasi atau kondisi agunan" />
        </div>
    </section>

    <!-- Modal -->
    <Teleport to="body">
        <div v-if="showNewProductTypeInput" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Tambah Kategori Produk Baru</h2>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kategori</label>
                    <input v-model="newProductTypeName" type="text" placeholder="Masukkan nama kategori..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 focus:outline-none"
                        @keyup.enter="createNewProductType" />
                </div>

                <div class="flex gap-3 justify-end">
                    <button @click="closeModal"
                        class="px-4 py-2 bg-gray-300 text-gray-900 rounded-lg hover:bg-gray-400 transition font-medium">
                        Batal
                    </button>
                    <button @click="createNewProductType"
                        :disabled="isCreatingProductType || !newProductTypeName.trim()"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition font-medium">
                        <span v-if="!isCreatingProductType">Buat</span>
                        <span v-else class="flex items-center gap-2">
                            <div class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full">
                            </div>
                            Membuat...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
