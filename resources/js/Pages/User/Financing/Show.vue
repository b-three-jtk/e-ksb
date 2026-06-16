<script setup>
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BaseLayout from '@/Layouts/Base.vue'
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

const props = defineProps({
    data: { type: Object, required: true },
})
const page = usePage()

const can = computed(() => page.props.auth.can)

const installments = computed(() => props.data?.installment ?? {
    data: [], current_page: 1, per_page: 10, total: 0, links: [],
})

const hasInstallmentHistory = computed(() => props.data?.installment?.data?.length > 0)

const canPayBill = computed(() =>
    can.value['edit_murabahah']
    && props.data.installment
    && (props.data.status === 'Angsuran Berjalan' || props.data.status === 'Pembayaran Tangguh')
)

const INSTALLMENT_COLUMNS = [
    { key: 'installment_no', label: 'Angsuran Ke' },
    { key: 'installment_trans_code', label: 'No. Transaksi' },
    { key: 'due_date', label: 'Tanggal Jatuh Tempo' },
    { key: 'payment_date', label: 'Tanggal Pembayaran' },
    { key: 'amount', label: 'Nominal' },
    { key: 'is_early_repayment', label: 'Keterangan' },
    { key: 'installment_payment_receipt', label: 'Aksi' },
]

const BREADCRUMBS = [
    { name: 'Dashboard', link: '/user/dashboard' },
    { name: 'Pengelolaan Pembiayaan' },
]

const modalRef = ref(null)

const selectedReceipt = ref(null)

const openReceiptModal = (receiptPath) => {
    selectedReceipt.value = receiptPath
    modalRef.value.open()
}
</script>

<template>
    <BaseLayout title="Detail Pembiayaan">
        <div class="container mx-auto pt-30 pb-10">
            <PageBreadcrumb page-title="Detail Pembiayaan" :items="BREADCRUMBS" />

            <div class="card-layout px-0!">

                <!-- Header -->
                <div class="flex justify-between border-b border-gray-200 dark:border-gray-600 pb-4 px-8">
                    <div class="flex flex-col">
                        <h1 class="uppercase font-medium text-lg dark:text-gray-200">Detail Pembiayaan Murabahah</h1>
                        <p class="text-sm font-light text-gray-500">
                            No. Pembiayaan: {{ data.financing_transaction_code }}
                        </p>
                    </div>
                    <span class="my-auto" :class="useFinancingStatus(data.status)">{{ data.status }}</span>
                </div>

                <!-- Objek Pembiayaan -->
                <section class="flex flex-col px-12 pb-2 pt-4 gap-2">
                    <h1 class="dark:text-gray-200">Detail Objek Pembiayaan Murabahah</h1>
                    <div class="card-layout grid grid-cols-2 gap-6">
                        <Info label="Kategori Produk" :value="data.financing_item?.product_type?.product_type_name" />
                        <Info label="Nama Produk" :value="data.financing_item?.name" />
                        <Info label="Tanggal Akad" :value="dateParser(data.akad_date)" />
                        <Info label="Jumlah/Kuantitas" :value="data.financing_item?.qty" />
                        <Info label="Kondisi" :value="data.financing_item?.condition" />
                        <Info label="Deskripsi Spesifikasi" :value="data.financing_item?.specification" />
                        <Info label="Supplier" :value="data.financing_item?.supplier?.supplier_name" />
                    </div>
                </section>

                <!-- Jaminan (opsional) -->
                <section v-if="data.collateral" class="flex flex-col px-12 pb-2 pt-4 gap-2">
                    <h1 class="dark:text-gray-200">Detail Jaminan</h1>
                    <div class="card-layout grid grid-cols-2 gap-6">
                        <Info label="Tipe Jaminan" :value="data.collateral.collateral_type" />
                        <Info label="Nama Pemilik" :value="data.collateral.owner_name" />
                        <Info label="Lokasi Jaminan" :value="data.collateral.collateral_location" />
                        <Info label="Nilai Pasar Estimasi"
                            :value="moneyParser(data.collateral.estimated_market_value)" />
                    </div>
                </section>

                <!-- Ringkasan Pembiayaan -->
                <section class="flex flex-col px-12 py-2 gap-2">
                    <div class="flex justify-between items-center">
                        <h1 class="dark:text-gray-200">Ringkasan Pembiayaan</h1>
                        <div v-if="canPayBill" class="flex items-center gap-4">
                            <Button :href="`/admin/financings/repayment/${data.id}`" variant="secondary" size="small">
                                <span class="icon-[tabler--moneybag-move]" style="width:18px;height:18px;" />
                                Pelunasan Dipercepat
                            </Button>
                            <Button :href="`/admin/financings/${data.id}/payments/create`" variant="info" size="small">
                                <span class="icon-[tabler--credit-card-pay]" style="width:18px;height:18px;" />
                                Bayar Tagihan
                            </Button>
                        </div>
                    </div>

                    <div class="card-layout px-0!">
                        <div class="grid grid-cols-2 gap-6 border-b border-gray-200 dark:border-gray-600 pb-4 px-8">
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
                        </div>

                        <div v-if="hasInstallmentHistory" class="py-8 px-8">
                            <h3 class="font-semibold mb-4 dark:text-gray-200">Progres Angsuran</h3>
                            <FinancingChart :total-price="Number(data.total_price)"
                                :total-paid="Number(data.total_paid)"
                                :remaining-balance="Number(data.remaining_balance)" />
                        </div>
                    </div>
                </section>

                <!-- Riwayat Angsuran -->
                <section class="flex flex-col px-12 py-2 gap-2">
                    <h1 class="dark:text-gray-200">Riwayat Angsuran</h1>
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
                                    @click="openReceiptModal(row.installment_payment_receipt)"> <EyeIcon width="18px" height="18px" />
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
        <ModalDocument
            ref="modalRef"
            modal-id="buktiModal"
            title="Bukti Penyetoran Angsuran"
            :name="selectedReceipt"
            :attachment="selectedReceipt"
        />
    </BaseLayout>
</template>
