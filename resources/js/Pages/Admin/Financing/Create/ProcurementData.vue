<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Info from '@/Components/Form/Info.vue'

defineProps({
    form: Object,
    searchSupplierQuery: String,
    isLoadingSearchSupplier: Boolean,
    isSupplierSelected: Boolean,
    filteredSuppliers: Array,
})
</script>

<template>
    <section>
        <div class="card-layout mx-4">
            <h1 class="card-title">Informasi Pemasok</h1>
            <div class="grid grid-cols-2 gap-4 pt-4">
                <div class="col-span-2 relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Cari Pemasok <span class="text-red-500">*</span>
                    </label>
                    <input :value="searchSupplierQuery" @input="$emit('update:searchSupplierQuery', $event.target.value)" type="text"
                        placeholder="Cari berdasarkan nama..." :disabled="isSupplierSelected"
                        class="w-full px-4 font-body py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed" />

                    <!-- Loading indicator -->
                    <div v-if="isLoadingSearchSupplier" class="absolute right-3 top-10">
                        <div class="animate-spin w-5 h-5 border-2 border-primary border-t-transparent rounded-full">
                        </div>
                    </div>

                    <!-- Search results dropdown -->
                    <div v-if="filteredSuppliers.length > 0 && !isSupplierSelected"
                        class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg z-10">
                        <div v-for="supplier in filteredSuppliers" :key="supplier.id" @click="$emit('selectSupplier', supplier)"
                            class="px-4 py-3 hover:bg-gray-100 cursor-pointer border-b last:border-0">
                            <div class="font-medium text-dark-text">{{ supplier.name }}</div>
                            <div class="text-sm text-gray-500">{{ supplier.contact }}</div>
                        </div>
                    </div>

                    <!-- No results message -->
                    <div v-else-if="searchSupplierQuery && !isLoadingSearchSupplier && !isSupplierSelected"
                        class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg p-4 text-center text-gray-500 z-10">
                        Pemasok tidak ditemukan
                    </div>
                </div>
                <BaseInputAdmin v-model="form.supplier.name" label="Nama" placeholder="Masukkan nama pemasok" />
                <BaseInputAdmin v-model="form.supplier.contact" label="Kontak" placeholder="Masukkan kontak pemasok" />
                <BaseInputAdmin v-model="form.supplier.address" label="Alamat" type="textarea" rows="3"
                    placeholder="Masukkan alamat pemasok" />
                <BaseInputAdmin v-model="form.supplier.link_address" label="Alamat URL"
                    placeholder="Masukkan link alamat pemasok (Google Maps)" />
            </div>
        </div>
        <div class="card-layout mx-4">
            <h1 class="card-title">Pengadaan Barang</h1>
            <div class="grid grid-cols-2 gap-4 pt-4">
                <BaseInputAdmin type="file" label="Bukti Pembayaran Uang Muka" v-model="form.income_slip"
                    accept=".pdf,.jpg,.jpeg,.png" required />
                <BaseInputAdmin type="file" label="Bukti Pembelian" v-model="form.income_slip"
                    accept=".pdf,.jpg,.jpeg,.png" required />
                <BaseInputAdmin v-model="form.financing.cost_price" label="Harga Perolehan Barang"
                    placeholder="Masukkan harga perolehan barang" />
                <BaseInputAdmin v-model="form.financing.discount" label="Diskon Barang" placeholder="Masukkan diskon" />
                <Info label="Margin" :value="form.financing.margin" />
            </div>
            <div class="bg-light-bg flex justify-between border px-8 py-4 rounded-lg">
                <div class="font-semibold text-primary">Total Harga Murabahah</div>
                <div class="font-semibold text-primary">{{ form.financing.cost_price + form.financing.margin }}</div>
            </div>
        </div>
    </section>
</template>
