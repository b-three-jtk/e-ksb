<template>
    <AdminLayout>
        <div class="flex flex-col gap-4">
            <div class="flex justify-between">
                <!-- TODO:  -->
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <CardInfo title="Total Kas" content="Rp18,600,000" :percentage="5" />
                <CardInfo title="Total Penjualan" content="Rp108,600,000" :percentage="-3" />
                <CardInfo title="Jumlah Anggota Aktif" :content="active_user_count" :percentage="2" />
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <CardStatisticBar title="Statistik Penjualan" />
                <div class="row-span-4 lg:row-span-4">
                    <CardTable title="Permohonan Keanggotaan Terbaru" :columns="user_columns" :data="members" />
                </div>
                <div class="row-span-4 lg:row-span-4">
                    <CardTable title="Transaksi Terbaru" :columns="trans_columns" :data="transactions" />
                </div>
                <CardSingleInfo title="Pengajuan Pembiayaan Murabahah" subTitle="39289348" :data="data" />
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'

import AdminLayout from '@/Layouts/Admin/Layout.vue'
import CardInfo from '@/Components/CardInfo.vue'
import CardStatisticBar from '@/Components/CardStatisticBar.vue'
import CardTable from '@/Components/CardTable.vue'
import CardSingleInfo from '@/Components/CardSingleInfo.vue'

const props = defineProps({
    active_user_count: Number,
});

const user_columns = [
    { key: 'name', label: 'Nama' },
    { key: 'email', label: 'Email' },
    { key: 'work_unit', label: 'Unit Kerja' },
    { key: 'action', label: 'Aksi' }
]

const members = ref([
    { name: 'John Doe', email: 'john@example.com', work_unit: 'Unit A', action: 'Edit' },
    { name: 'Jane Smith', email: 'jane@example.com', work_unit: 'Unit B', action: 'Edit' },
    { name: 'Michael Johnson', email: 'michael@example.com', work_unit: 'Unit C', action: 'Edit' },
    { name: 'Emily Davis', email: 'emily@example.com', work_unit: 'Unit D', action: 'Edit' },
    { name: 'William Brown', email: 'william@example.com', work_unit: 'Unit E', action: 'Edit' }
])

const trans_columns = [
    { key: 'invoice', label: 'No. Invoice' },
    { key: 'amount', label: 'Jumlah' },
    { key: 'total', label: 'Total' },
    { key: 'date', label: 'Tanggal' },
    { key: 'action', label: 'Aksi' }
]

const transactions = ref([
    { invoice: 'INV001', amount: '2', total: 'Rp200,000', date: '2024-01-15', action: 'Lihat' },
    { invoice: 'INV002', amount: '1', total: 'Rp150,000', date: '2024-01-16', action: 'Lihat' },
    { invoice: 'INV003', amount: '3', total: 'Rp300,000', date: '2024-01-17', action: 'Lihat' },
    { invoice: 'INV004', amount: '5', total: 'Rp500,000', date: '2024-01-18', action: 'Lihat' },
    { invoice: 'INV005', amount: '4', total: 'Rp400,000', date: '2024-01-19', action: 'Lihat' }
])

const data = [
    { label: 'Permintaan Produk', content: 'LGTV NanoCell 43 inch' },
    { label: 'Status', content: 'Barang Diterima' },
    { label: 'Nomor Anggota', content: '231511041' },
    { label: 'Nama Anggota', content: 'Sulistyo Wirawan' },
]

</script>
