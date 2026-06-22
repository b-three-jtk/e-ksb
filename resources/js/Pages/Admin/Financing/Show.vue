<script setup>
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import PageBreadcrumb from '@/Components/PageBreadcrumb.vue'
import Info from '@/Components/Form/Info.vue'
import Button from '@/Components/Form/Button.vue'
import BaseTable from '@/Components/Table/BaseTable.vue'
import Pagination from '@/Components/Table/Pagination.vue'
import FinancingChart from '@/Components/FinancingChart.vue'
import EyeIcon from '@/Icons/EyeIcon.vue'
import moneyParser from '@/Composables/moneyParser.js'
import dateParser from '@/Composables/dateParser.js'
import useFinancingStatus from '@/Composables/useFinancingStatus.js'
import ModalDocument from '@/Components/ModalDocument.vue'
import Documents from './Show/Documents.vue'

const props = defineProps({
    data: { type: Object, required: true },
})
const page = usePage()

const can = computed(() => page.props.auth.can)

const installments = computed(() => props.data?.installment ?? {
    data: [], current_page: 1, per_page: 10, total: 0, links: [],
})

const hasInstallmentHistory = computed(() => Number(props.data.total_price) > 0)

const canPayBill = computed(() =>
    can.value['payment_murabahah']
    && props.data.installment
)

const INSTALLMENT_COLUMNS = [
    { key: 'installment_no', label: 'Pembayaran Ke' },
    { key: 'installment_trans_code', label: 'No. Transaksi' },
    { key: 'due_date', label: 'Tanggal Jatuh Tempo' },
    { key: 'payment_date', label: 'Tanggal Pembayaran' },
    { key: 'amount', label: 'Nominal' },
    { key: 'is_early_repayment', label: 'Keterangan' },
    { key: 'installment_payment_receipt', label: 'Aksi' },
]

const BREADCRUMBS = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Pengelolaan Pembiayaan' },
]

const modalRef = ref(null)

const selectedReceipt = ref(null)

const openReceiptModal = (receiptPath) => {
    selectedReceipt.value = receiptPath
    modalRef.value.openModal()
}
</script>

<template>
    <AdminLayout title="Detail Pembiayaan">
        <PageBreadcrumb page-title="Detail Pembiayaan" :items="BREADCRUMBS" />
        <div class="flex flex-col gap-4">
            <div class="card-layout flex justify-between">
                <div class="flex gap-2 items-center">
                    <h1 class="font-semibold text-dark-text dark:text-white">No. Transaksi #{{
                        data.financing_transaction_code }} <span class="my-auto ml-2"
                            :class="useFinancingStatus(data.status)">{{ data.status }}</span>
                    </h1>
                </div>
                <div class="flex items-center gap-4">
                    <Button v-if="canPayBill && data.status === 'Angsuran Berjalan'" :href="`/admin/financings/repayment/${data.id}`" variant="secondary">
                        <span class="icon-[tabler--moneybag-move]" style="width:18px;height:18px;" />
                        Pelunasan Dipercepat
                    </Button>
                    <Button v-if="canPayBill" :href="`/admin/financings/${data.id}/payments/create`" variant="info">
                        <span class="icon-[tabler--credit-card-pay]" style="width:18px;height:18px;" />
                        Bayar Tagihan
                    </Button>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-7 gap-4">
                <div class="flex flex-col justify-end col-span-1 lg:col-span-5">
                    <div class="card-layout flex flex-col gap-4 col-span-1 lg:col-span-3">
                        <div class="card-layout">
                            <h2 class="card-title mb-4">Detail Transaksi</h2>
                            <ul class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">
                                <Info label="Harga Pokok" :value="moneyParser(data.cost_price)" />
                                <Info label="Margin" :value="moneyParser(data.margin_amount)" />
                                <Info label="Uang Muka" :value="moneyParser(data.down_payment)" />
                                <Info label="Total Pembiayaan" :value="moneyParser(data.total_price)" />
                                <Info label="Total Dibayar" :value="moneyParser(data.total_paid)" />
                                <Info label="Sisa Tagihan" :value="moneyParser(data.remaining_balance)" />
                                <Info label="Angsuran/Bulan" :value="moneyParser(data.installment_per_month)" />
                                <Info v-if="data.tenor" label="Tenor" :value="`${data.tenor} Bulan`" />
                                <Info v-if="data.next_due_date" label="Jatuh Tempo Terdekat"
                                    :value="dateParser(data.next_due_date)" />
                            </ul>
                        </div>
                        <div class="card-layout">
                            <h1 class="card-title mb-4">Detail Objek Pembiayaan</h1>
                            <ul class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <Info label="Kategori Produk"
                                    :value="data.financing_item?.product_type?.product_type_name" />
                                <Info label="Nama Produk" :value="data.financing_item?.name" />
                                <Info label="Tanggal Akad" :value="dateParser(data.akad_date)" />
                                <Info label="Jumlah/Kuantitas" :value="data.financing_item?.qty" />
                                <Info label="Kondisi" :value="data.financing_item?.condition" />
                                <Info label="Deskripsi Spesifikasi" :value="data.financing_item?.specification" />
                            </ul>
                        </div>
                        <section class="flex flex-col py-2 gap-2">
                            <h1 class="card-title mb-4">Riwayat Pembayaran</h1>
                            <div class="card-layout p-0!">
                                <BaseTable :columns="INSTALLMENT_COLUMNS" :data="installments">

                                    <template #cell-installment_trans_code="{ row }">
                                        {{ row.installment_trans_code ?? '-' }}
                                    </template>
                                    <template #cell-due_date="{ row }">
                                        {{ dateParser(row.due_date) }}
                                    </template>
                                    <template #cell-payment_date="{ row }">
                                        {{ dateParser(row.payment_date) }}
                                    </template>
                                    <template #cell-amount="{ row }">
                                        {{ moneyParser(row.amount) }}
                                    </template>
                                    <template #cell-is_early_repayment="{ row }">
                                        <span class="font-semibold rounded-lg px-3 py-1 text-xs" :class="row.is_early_repayment
                                            ? 'text-blue-600 bg-blue-50'
                                            : 'text-green-600 bg-green-50'">
                                            {{ row.is_early_repayment ? 'Pelunasan Dipercepat' : 'Reguler' }}
                                        </span>
                                    </template>
                                    <template #cell-installment_payment_receipt="{ row }">
                                        <Button v-if="row.installment_payment_receipt" size="small" variant="primary"
                                            @click="openReceiptModal(row.installment_payment_receipt)">
                                            <EyeIcon width="18px" height="18px" />
                                            Lihat Bukti
                                        </Button>
                                        <Button v-else size="small" variant="gray" disabled>
                                            <EyeIcon width="18px" height="18px" />
                                            Lihat Bukti
                                        </Button>
                                    </template>

                                </BaseTable>
                                <Pagination :links="installments.links" :total="installments.total" />
                            </div>
                        </section>
                    </div>

                </div>
                <div class="flex flex-col col-span-1 lg:col-span-2 gap-2">
                    <div v-if="hasInstallmentHistory" class="card-layout">
                        <h1 class="card-title mb-4">Progres Angsuran</h1>
                        <FinancingChart :total-price="Number(data.total_price)" :total-paid="Number(data.total_paid)"
                            :remaining-balance="Number(data.remaining_balance)" />
                    </div>
                    <div v-if="data.supplier" class="card-layout h-fit flex flex-col gap-6">
                        <h1 class="card-title">Informasi Pemasok</h1>
                        <ul class="grid grid-cols-1 gap-6">
                            <Info label="Nama Pemasok" :value="data.supplier?.supplier_name" />
                            <Info label="Alamat Pemasok" :value="data.supplier?.supplier_address" />
                            <Info label="Kontak Pemasok" :value="data.supplier?.supplier_contact" />
                        </ul>
                    </div>
                    <div v-if="data.collateral" class="card-layout flex flex-col pb-12.5! gap-6">
                        <h1 class="card-title">Informasi Jaminan</h1>
                        <ul class="grid grid-cols-1 gap-6">
                            <Info label="Tipe Jaminan" :value="data.collateral.collateral_type" />
                            <Info label="Nama Pemilik" :value="data.collateral.owner_name" />
                            <Info label="Lokasi Jaminan" :value="data.collateral.collateral_location" />
                            <Info label="Nilai Pasar Estimasi"
                                :value="moneyParser(data.collateral.estimated_market_value)" />
                        </ul>
                    </div>
                    <Documents :data="data" />

                    </div>
            </div>
        </div>
        <ModalDocument ref="modalRef" modal-id="buktiModal" title="Bukti Penyetoran Angsuran" :name="selectedReceipt"
            :attachment="selectedReceipt" />
    </AdminLayout>
</template>
