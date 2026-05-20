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
import NoArchiveIcon from '../../../Icons/NoArchiveIcon.vue';
import useFinancingStatus from '@/Composables/useFinancingStatus.js';

const props = defineProps({
    data: { type: Object, required: true },
});

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Pengelolaan Pembiayaan' },
];
</script>

<template>
    <AdminLayout title="Detail Pembiayaan">
        <PageBreadcrumb :page-title="'Detail Pembiayaan'" :items="breadcrumbItems" />
        <div class="card-layout px-0!">
            <div class="flex justify-between border-b border-gray-200 pb-4 px-8">
                <div class="flex flex-col">
                    <h1 class="uppercase font-medium text-lg">Detail Pembiayaan Murabahah</h1>
                    <p class="text-sm font-light text-gray-500">No. Pembiayaan: {{ data.financing_transaction_code }}
                    </p>
                </div>
                <span class="my-auto" :class="useFinancingStatus(data.status)">{{ data.status
                    }}</span>
            </div>
            <div class="flex flex-col px-12 pb-2 pt-4 gap-2">
                <h1>Detail Objek Pembiayaan Murabahah</h1>
                <div class="card-layout grid grid-cols-2 gap-6">
                    <Info label="Kategori Produk" :value="data.financing_item?.product_type?.product_type_name" />
                    <Info label="Nama Produk" :value="data.financing_item?.name" />
                    <Info label="Tanggal Akad" :value="data.akad_date" />
                    <Info label="Jumlah/Kuantitas" :value="data.financing_item?.qty" />
                    <Info label="Kondisi" :value="data.financing_item?.condition" />
                    <Info label="Deskripsi Spesifikasi" :value="data.financing_item?.specification" />
                    <Info label="Supplier" :value="data.financing_item.supplier?.supplier_name" />
                </div>
            </div>
            <div v-if="data.collateral" class="flex flex-col px-12 pb-2 pt-4 gap-2">
                <h1>Detail Jaminan</h1>
                <div class="card-layout grid grid-cols-2 gap-6">
                    <Info label="Tipe Jaminan" :value="data.collateral.collateral_type" />
                    <Info label="Nama Pemilik" :value="data.collateral.owner_name" />
                    <Info label="Lokasi Jaminan" :value="data.collateral.collateral_location" />
                    <Info label="Nilai Pasar Estimasi" :value="moneyParser(data.collateral.estimated_market_value)" />
                </div>
            </div>
            <div class="flex flex-col px-12 py-2 gap-2">
                <div class="flex justify-between items-center">
                    <h1>Ringkasan Pembiayaan</h1>
                    <div class="flex items-center gap-4">
                        <Button v-if="data.installment && data.status == 'Angsuran Berjalan'" :href="`/admin/financing/repayment/${data.id}`" variant="secondary" size="small">
                            <span class="icon-[tabler--moneybag-move]" style="width: 18px; height: 18px;"></span>
                            Pelunasan Dipercepat
                        </Button>
                        <Button variant="info" size="small">
                            <span class="icon-[tabler--credit-card-pay]" style="width: 18px; height: 18px;"></span>
                            Bayar Tagihan
                        </Button>
                    </div>
                </div>
                <div class="card-layout px-0!">
                    <div class="grid grid-cols-2 gap-6 border-b border-gray-200 pb-4 px-8">
                        <Info label="Harga Pokok" :value="moneyParser(data.cost_price)" />
                        <Info label="Margin" :value="moneyParser(data.margin_amount)" />
                        <Info label="Uang Muka" :value="moneyParser(data.down_payment)" />
                        <Info label="Total Pembiayaan" :value="moneyParser(data.total_price)" />
                        <Info label="Total Dibayar" :value="moneyParser(data.total_paid)" />
                        <Info label="Sisa Tagihan" :value="moneyParser(data.remaining_balance)" />
                                                <Info label="Angsuran/Bulan" :value="moneyParser(data.installment_per_month)" />
                        <Info v-if="data.installment?.tenor" label="Tenor"
                            :value="data.installment?.tenor + ' Bulan'" />
                        <Info v-if="data.next_due_date" label="Jatuh Tempo Terdekat" :value="dateParser(data.next_due_date)" />
                    </div>
                    <div class="py-8 px-8" v-if="data.total_price">
                    </div>
                    <div class="py-8 px-8" v-if="data.installment?.payment && data.installment?.payment?.length > 0">
                        <h3 class="font-semibold mb-4">Progres Angsuran</h3>
                        <FinancingChart :data="data.installment?.payment" :total-price="Number(data.total_price)" :total-paid="Number(data.total_paid)" />
                    </div>
                </div>
            </div>
            <div class="flex flex-col px-12 py-2 gap-2">
                <h1>Riwayat Angsuran</h1>
                <div class="card-layout p-0!">
                    <table class="min-w-full text-center">
                        <thead>
                            <tr class="text-gray-500 text-md font-medium dark:text-gray-400">
                                <th class="py-5 px-2">
                                    No. Transaksi
                                </th>
                                <th class="py-5 px-2">
                                    Tanggal Pembayaran
                                </th>
                                <th class="py-5 px-2">
                                    Nominal
                                </th>
                                <th class="py-5 px-2">
                                    Keterangan
                                </th>
                                <th class="py-5 px-2">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            v-if="data.installment?.payment && data.installment?.payment?.length > 0">
                            <tr v-for="i in data.installment?.payment"
                                class="border-t border-gray-100 dark:border-gray-500 font-body">
                                <td class="py-5 px-2 whitespace-nowrap">
                                    <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                        {{ i.installment_trans_code }}
                                    </p>
                                </td>
                                <td class="py-5 px-2 whitespace-nowrap">
                                    <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                        {{ dateParser(i.payment_date) }}
                                    </p>
                                </td>
                                <td class="py-5 px-2 whitespace-nowrap">
                                    <p class="text-dark-text text-theme-sm dark:text-gray-400">
                                        {{ moneyParser(i.nominal) }}
                                    </p>
                                </td>
                                <td class="py-5 px-2 whitespace-nowrap">
                                    <span class="font-semibold rounded-lg px-3 py-1 text-xs" :class="i.is_early_repayment ? 'text-blue-600 bg-blue-50' : 'text-green-600 bg-green-50'">
                                        {{ i.is_early_repayment ? 'Pelunasan Dipercepat' : 'Reguler' }}
                                    </span>
                                </td>
                                <td class="py-5 px-2 whitespace-nowrap flex justify-center">
                                    <div v-if="i.installment_payment_receipt">
                                        <Button size="small" variant="info" target="_blank"
                                            :href="`/storage/${i.installment_payment_receipt}`">
                                            <EyeIcon width="18px" height="18px" />
                                            Lihat Bukti
                                        </Button>
                                    </div>
                                    <div v-else>
                                        <Button size="small" variant="gray" disabled>
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
