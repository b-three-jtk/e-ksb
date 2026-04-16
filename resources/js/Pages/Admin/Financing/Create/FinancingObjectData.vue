<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'

defineProps({
    form: Object,
    conditions: Array,
})
</script>

<template>
    <section>
        <div class="border-b border-gray-200 px-8 pb-4">
            <h1 class="card-title">Objek Pembiayaan</h1>
        </div>
        <div class="grid grid-cols-2 gap-4 p-4">
            <BaseInputAdmin v-model="form.product_name" label="Nama Produk" placeholder="Masukkan nama produk" required />
            <BaseInputAdmin v-model="form.product_type" label="Kategori Produk" />
            <BaseInputAdmin v-model="form.brand" label="Merek" placeholder="Masukkan merek produk" />
            <BaseInputAdmin v-model="form.color" label="Warna" placeholder="Masukkan warna produk" />
            <BaseInputAdmin v-model="form.condition" label="Kondisi" type="select"
                :selectables="conditions.map((c) => ({ value: c, text: c }))" />
            <BaseInputAdmin v-model="form.qty" label="Jumlah" type="number" min="1" />
            <BaseInputAdmin v-model="form.description" label="Deskripsi" type="textarea" rows="4"
                placeholder="Masukkan deskripsi produk" />
            <BaseInputAdmin v-model="form.down_payment" label="Uang Muka" isMoney
                placeholder="Masukkan jumlah uang muka" />
        </div>
    </section>
</template>
