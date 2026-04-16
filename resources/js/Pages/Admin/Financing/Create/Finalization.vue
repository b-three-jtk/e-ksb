<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Info from '@/Components/Form/Info.vue'

defineProps({
    form: Object,
})
</script>

<template>
    <section>
        <div class="border-b border-gray-200 px-8 pb-4">
            <h1 class="card-title">Finalisasi Pembiayaan Murabahah</h1>
        </div>
        <section class="px-8 py-4">
            <h1 class="card-title">Detail Objek Pembiayaan</h1>
            <div class="card-layout grid grid-cols-2 gap-4 mt-2">
                <Info label="Nama Barang" :value="form.product_name" />
                <Info label="Kategori" :value="form.product_type" />
                <Info label="Kuantitas" :value="form.qty" />
                <Info label="Detail Spesifikasi" :value="form.description" />
            </div>
        </section>
        <section class="px-8 py-4">
            <h1 class="card-title">Rincian Harga Murabahah</h1>
            <div class="border rounded-2xl overflow-hidden mt-2">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-dark-text border-y dark:bg-gray-700 dark:text-gray-400">
                        <tr class="border-b">
                            <th class="text-left pl-6 py-4">Komponen</th>
                            <th class="text-left pl-6 py-4">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Harga Perolehan Barang</td>
                            <td class="text-right pr-6 py-4">{{ form.cost_price }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Margin</td>
                            <td class="text-right pr-6 py-4">{{ form.margin }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Uang Muka</td>
                            <td class="text-right pr-6 py-4">{{ form.down_payment }}</td>
                        </tr>
                        <tr class="border-b bg-light-bg">
                            <td class="text-left pl-6 py-4">Total Harga Murabahah</td>
                            <td class="text-right pr-6 py-4">{{ form.total_price }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <div class="card-layout mx-8 mt-6">
            <h1 class="card-title">Simulasi Cicilan</h1>
            <div class="grid grid-cols-2">
                <Info label="Cicilan Perbulan" :value="form.monthly_installment" />
                <Info label="Sisa Penghasilan Bulanan" :value="form.monthly_income" />
            </div>
        </div>
        <section class="px-8 py-4">
            <h1 class="card-title">Skema Angsuran</h1>
            <div class="border rounded-2xl overflow-hidden mt-2">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-dark-text border-y dark:bg-gray-700 dark:text-gray-400">
                        <tr class="border-b">
                            <th class="text-left pl-6 py-4">Keterangan</th>
                            <th class="text-left pl-6 py-4">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white border-b text-dark-text dark:bg-gray-800 dark:border-gray-700">
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Tenor / Jangka Waktu</td>
                            <td class="text-right pr-6 py-4">{{ form.cost_price }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Angsuran/bulan</td>
                            <td class="text-right pr-6 py-4">{{ form.margin }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Jatuh Tempo Pertama</td>
                            <td class="text-right pr-6 py-4">{{ form.down_payment }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Jatuh Tempo Terakhir</td>
                            <td class="text-right pr-6 py-4">{{ form.total_price }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <div class="px-8 pt-6">
            <BaseInputAdmin type="file" label="Unggah Dokumen Akad Tertandatangani" v-model="form.income_slip"
                accept=".pdf,.jpg,.jpeg,.png" required />
        </div>
    </section>
</template>
