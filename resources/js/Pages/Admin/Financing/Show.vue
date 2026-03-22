<script setup>
import AdminLayout from '../../../Layouts/Admin/Layout.vue'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'
import { defineProps } from 'vue';
import Info from '../../../Components/Form/Info.vue';
import Button from '../../../Components/Form/Button.vue';
import moneyParser from '../../../Composables/moneyParser.js';
import dateParser from '../../../Composables/dateParser.js';
import EyeIcon from '../../../Icons/EyeIcon.vue';
import FinancingChart from '../../../Components/FinancingChart.vue';

const props = defineProps({
    data: { type: Object, required: true },
});

console.log(props.data)

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengelolaan Pembiayaan' },
];

const getStatusClass = () => {
    const baseClass = 'font-semibold rounded-2xl px-4 text-theme-sm py-1'
    const status = props.data.status

    switch (status) {
        case 'Belum Ditinjau':
            return `${baseClass} text-gray-600 bg-gray-100`
        case 'Disetujui':
            return `${baseClass} text-green-600 bg-green-50`
        case 'Disetujui Dengan Catatan':
            return `${baseClass} text-blue-600 bg-blue-50`
        case 'Ditolak':
            return `${baseClass} text-red-600 bg-red-50`
        case 'Menunggu Kelengkapan Dokumen':
            return `${baseClass} text-yellow-600 bg-yellow-50`
        case 'Barang Diterima':
            return `${baseClass} text-purple-600 bg-purple-50`
        case 'Angsuran Berjalan':
            return `${baseClass} text-blue-600 bg-blue-50`
        case 'Lunas':
            return `${baseClass} text-green-600 bg-green-50`
        default:
            return `${baseClass} text-gray-600 bg-gray-100`
    }
}

const getScheduleStatusClass = (status) => {
    const baseClass = 'font-semibold rounded-lg px-3 py-1 text-xs'

    switch (status) {
        case 'Dibayar':
            return `${baseClass} text-green-600 bg-green-50`
        case 'Menunggu Konfirmasi':
            return `${baseClass} text-yellow-600 bg-yellow-50`
        case 'Dibatalkan':
            return `${baseClass} text-gray-600 bg-gray-50`
        case 'Terlambat':
            return `${baseClass} text-red-600 bg-red-50`
        case 'Terjadwal':
            return `${baseClass} text-blue-600 bg-blue-50`
        case 'Ditolak':
            return `${baseClass} text-red-600 bg-red-50`
        default:
            return `${baseClass} text-gray-600 bg-gray-50`
    }
}
</script>

<template>
    <AdminLayout title="Detail Pembiayaan">
        <PageBreadcrumb :page-title="'Detail Pembiayaan'" :items="breadcrumbItems" />
        <div class="card-layout px-0!">
            <div class="flex justify-between border-b border-gray-200 pb-4 px-8">
                <div class="flex flex-col">
                    <h1 class="uppercase font-medium text-lg">Detail Pembiayaan Murabahah</h1>
                    <p class="text-sm font-light text-gray-500">No. Pembiayaan: {{ data.transaction_code }}</p>
                </div>
                <span class="my-auto" :class="getStatusClass()">{{ data.status }}</span>
            </div>
            <div class="flex flex-col px-12 pb-2 pt-4 gap-2">
                <h1>Detail Objek Pembiayaan Murabahah</h1>
                <div class="card-layout grid grid-cols-2 gap-6">
                    <Info label="Kategori Produk" :value="data.product_type" />
                    <Info label="Nama Produk" :value="data.product_name" />
                    <Info label="Tanggal Akad" :value="data.akad_date" />
                    <Info label="Jumlah/Kuantitas" :value="data.qty" />
                    <Info label="Merek" :value="data.brand" />
                    <Info label="Kondisi" :value="data.condition" />
                    <Info label="Warna" :value="data.color" />
                    <Info label="Deskripsi Spesifikasi" :value="data.description" />
                </div>
            </div>
            <div class="flex flex-col px-12 py-2 gap-2">
                <div class="flex justify-between items-center">
                    <h1>Ringkasan Pembiayaan</h1>
                    <Button variant="secondary" size="small">
                        <span class="icon-[tabler--credit-card-pay]" style="width: 24px; height: 24px;"></span>
                        Bayar Tagihan
                    </Button>
                </div>
                <div class="card-layout px-0!">
                    <div class="grid grid-cols-2 gap-6 border-b border-gray-200 pb-4 px-8">
                        <Info label="Harga Pokok" :value="moneyParser(data.cost_price)" />
                        <Info label="Margin" :value="moneyParser(data.margin)" />
                        <Info label="Uang Muka" :value="moneyParser(data.down_payment)" />
                        <Info label="Total Pembiayaan" :value="moneyParser(data.total_price)" />
                        <Info label="Total Dibayar" :value="moneyParser(data.total_paid)" />
                        <Info label="Sisa Tagihan" :value="moneyParser(data.remaining_balance)" />
                        <Info label="Angsuran/Bulan" :value="moneyParser(data.loan.monthly_installment)" />
                        <Info label="Tenor" :value="data.loan.tenor + ' Bulan'" />
                    </div>
                    <div class="py-8 px-8">
                        <h3 class="font-semibold mb-4">Status Angsuran</h3>
                        <FinancingChart :payment-schedules="data.loan.payment_schedules" />
                    </div>
                </div>
            </div>
            <div class="flex flex-col px-12 py-2 gap-2">
                <h1>Jadwal dan Riwayat Angsuran</h1>
                <div class="card-layout p-0!">
                    <table class="min-w-full text-center">
                        <thead>
                            <tr class="text-gray-500 text-md font-medium dark:text-gray-400">
                                <th class="py-5 px-2">
                                    Angsuran ke-
                                </th>
                                <th class="py-5 px-2">
                                    Tanggal Jatuh Tempo
                                </th>
                                <th class="py-5 px-2">
                                    Nominal
                                </th>
                                <th class="py-5 px-2">
                                    Status
                                </th>
                                <th class="py-5 px-2">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody v-if="data.loan.payment_schedules">
                            <tr v-for="data in data.loan.payment_schedules"
                                class="border-t border-gray-100 dark:border-gray-500">
                                <td class="py-5 px-2 whitespace-nowrap">
                                    <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                        {{ data.installment_number }}
                                    </p>
                                </td>
                                <td class="py-5 px-2 whitespace-nowrap">
                                    <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                        {{ dateParser(data.due_date) }}
                                    </p>
                                </td>
                                <td class="py-5 px-2 whitespace-nowrap">
                                    <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                        {{ moneyParser(data.total_amount) }}
                                    </p>
                                </td>
                                <td class="py-5 px-2 whitespace-nowrap">
                                    <span :class="getScheduleStatusClass(data.status)">
                                        {{ data.status }}
                                    </span>
                                </td>
                                <td class="py-5 px-2 whitespace-nowrap flex justify-center">
                                    <div
                                        v-if="data.status != 'Dibayar' && data.status != 'Terlambat' && data.status != 'Ditolak'">
                                        <Button size="small" variant="gray" disabled>
                                            <EyeIcon width="18px" height="18px" />
                                            Lihat Bukti
                                        </Button>
                                    </div>
                                    <div v-else>
                                        <Button size="small" variant="info" :href="`/admin/savings/show/${data.id}`">
                                            <EyeIcon width="18px" height="18px" />
                                            Lihat Bukti
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr class="border-t border-gray-100 dark:border-gray-500">
                                <td colspan="5" class="py-5 text-center">
                                    <NoArchiveIcon width="120px" height="120px" />
                                    <p class="text-dark-text text-sm dark:text-gray-400 pt-4">
                                        Tidak ada data angsuran.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
