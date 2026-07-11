<script setup>
import { computed, ref, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
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
import Swal from 'sweetalert2'

const props = defineProps({
    data: { type: Object, required: true },
})
const page = usePage()

const can = computed(() => page.props.auth.can)

const installments = computed(() => props.data?.angsuran ?? {
    data: [], current_page: 1, per_page: 10, total: 0, links: [],
})

const hasInstallmentHistory = computed(() => Number(props.data.total_price) > 0)

const canPayBill = computed(() =>
    can.value['payment_murabahah']
    && props.data.angsuran
)

const INSTALLMENT_COLUMNS = [
    { key: 'angsuran_ke', label: 'Pembayaran Ke' },
    { key: 'kode_transaksi_pembayaran', label: 'No. Transaksi' },
    { key: 'tgl_jatuh_tempo', label: 'Tanggal Jatuh Tempo' },
    { key: 'tgl_pembayaran', label: 'Tanggal Pembayaran' },
    { key: 'nominal_angsuran', label: 'Nominal' },
    { key: 'is_pelunasan_lebih_cepat', label: 'Keterangan' },
    { key: 'struk_pembayaran', label: 'Aksi' },
]

const BREADCRUMBS = [
    { name: 'Dashboard', link: '/admin/dashboard' },
    { name: 'Pengelolaan Pembiayaan' },
]

const modalRef = ref(null)

const selectedReceipt = ref(null)

onMounted(() => {
    const pdfUrl = page.props.flash?.pdf_url

    if (pdfUrl) {
        selectedReceipt.value = pdfUrl

        setTimeout(() => {
            modalRef.value?.openModal()
        }, 100)
    }
})

const openReceiptModal = (receiptPath) => {
    selectedReceipt.value = receiptPath.startsWith('http')
        ? receiptPath
        : `/storage/${receiptPath}`

    modalRef.value.openModal()
}

const batalkan = () => {
    Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin membatalkan permohonan ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, batal',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#009141',
    }).then((result) => {
            if (result.isConfirmed) {
                router.delete(`/admin/pembiayaan/batal/${props.data.id}`, {
                    onSuccess: (page) => {
                        if (page.props.flash?.success) {
                            toast(page.props.flash.success, {
                                type: 'success',
                                position: 'bottom-right',
                            })
                        }
                    },
                    onError: (errors) => {
                        const errorMessages = Object.values(errors).flat()

                        if (errorMessages.length > 0) {
                            toast(errorMessages.join(', '), {
                                type: 'error',
                                position: 'bottom-right',
                            })
                        } else {
                            toast('Gagal menyimpan permohonan', {
                                type: 'error',
                                position: 'bottom-right',
                            })
                        }
                    }
                })
            }
    })
}
</script>

<template>
    <AdminLayout title="Detail Pembiayaan">
        <PageBreadcrumb page-title="Detail Pembiayaan" :items="BREADCRUMBS" />
        <div class="flex flex-col gap-4">
            <div class="card-layout flex justify-between">
                <div class="flex gap-2 items-center">
                    <h1 class="font-semibold text-dark-text dark:text-white">No. Transaksi #{{
                        data.kode_pembiayaan }} <span class="my-auto ml-2"
                            :class="useFinancingStatus(data.status)">{{ data.status }}</span>
                    </h1>
                </div>
                <div class="flex items-center gap-4">
                    <Button
                        v-if="data.status !== 'Angsuran Berjalan' && data.status !== 'Tangguh' && data.status !== 'Lunas'"
                        variant="gray"
                        @click="batalkan()">
                        Batalkan Permohonan
                    </Button>
                    <Button v-if="canPayBill && data.status === 'Angsuran Berjalan'" :href="`/admin/pembiayaan/repayment/${data.id}`" variant="secondary">
                        <span class="icon-[tabler--moneybag-move]" style="width:18px;height:18px;" />
                        Pelunasan Dipercepat
                    </Button>
                    <Button v-if="canPayBill && (data.status === 'Angsuran Berjalan' || data.status === 'Tangguh')" :href="`/admin/pembiayaan/${data.id}/payments/create`" variant="info">
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
                                <Info label="Harga Pokok" :value="moneyParser(data.harga_perolehan)" />
                                <Info label="Margin" :value="moneyParser(data.margin_keuntungan)" />
                                <Info label="Uang Muka" :value="moneyParser(data.uang_muka)" />
                                <Info label="Total Pembiayaan" :value="moneyParser(data.total_price)" />
                                <Info label="Total Dibayar" :value="moneyParser(data.total_paid)" />
                                <Info label="Sisa Tagihan" :value="moneyParser(data.remaining_balance)" />
                                <Info label="Angsuran/Bulan" :value="moneyParser(data.installment_per_month)" />
                                <Info v-if="data.tenor" label="Tenor" :value="`${data.tenor} ${data.satuan_tenor}`" />
                                <Info v-if="data.next_due_date" label="Jatuh Tempo Terdekat"
                                    :value="dateParser(data.next_due_date)" />
                            </ul>
                        </div>
                        <div class="card-layout">
                            <h1 class="card-title mb-4">Detail Objek Pembiayaan</h1>
                            <ul class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <Info label="Kategori Produk"
                                    :value="data.objek_pembiayaan?.jenis_barang?.nama_jenis_barang" />
                                <Info label="Nama Produk" :value="data.objek_pembiayaan?.nama_barang" />
                                <Info label="Tanggal Akad" :value="dateParser(data.tgl_akad)" />
                                <Info label="Jumlah/Kuantitas" :value="data.objek_pembiayaan?.kuantitas" />
                                <Info label="Kondisi" :value="data.objek_pembiayaan?.kondisi_produk" />
                                <Info label="Deskripsi Spesifikasi" :value="data.objek_pembiayaan?.spesifikasi_barang" />
                            </ul>
                        </div>
                        <section class="flex flex-col py-2 gap-2">
                            <h1 class="card-title mb-4">Riwayat Pembayaran</h1>
                            <div class="card-layout p-0!">
                                <BaseTable :columns="INSTALLMENT_COLUMNS" :data="installments">

                                    <template #cell-kode_transaksi_pembayaran="{ row }">
                                        {{ row.kode_transaksi_pembayaran ?? '-' }}
                                    </template>
                                    <template #cell-tgl_jatuh_tempo="{ row }">
                                        {{ dateParser(row.tgl_jatuh_tempo) }}
                                    </template>
                                    <template #cell-tgl_pembayaran="{ row }">
                                        {{ dateParser(row.tgl_pembayaran) }}
                                    </template>
                                    <template #cell-nominal_angsuran="{ row }">
                                        {{ moneyParser(row.nominal_angsuran) }}
                                    </template>
                                    <template #cell-is_pelunasan_lebih_cepat="{ row }">
                                        <span class="font-semibold rounded-lg px-3 py-1 text-xs" :class="row.is_pelunasan_lebih_cepat
                                            ? 'text-blue-600 bg-blue-50'
                                            : 'text-green-600 bg-green-50'">
                                            {{ row.is_pelunasan_lebih_cepat ? 'Pelunasan Dipercepat' : 'Reguler' }}
                                        </span>
                                    </template>
                                    <template #cell-struk_pembayaran="{ row }">
                                        <Button v-if="row.struk_pembayaran" size="small" variant="primary"
                                            @click="openReceiptModal(row.struk_pembayaran)">
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
                    <div v-if="hasInstallmentHistory && data.status != 'Lunas'" class="card-layout">
                        <h1 class="card-title mb-4">Progres Angsuran</h1>
                        <FinancingChart :total-price="Number(data.total_price)" :total-paid="Number(data.total_paid)"
                            :remaining-balance="Number(data.remaining_balance)" />
                    </div>
                    <div v-if="data.pemasok" class="card-layout h-fit flex flex-col gap-6">
                        <h1 class="card-title">Informasi Pemasok</h1>
                        <ul class="grid grid-cols-1 gap-6">
                            <Info label="Nama Pemasok" :value="data.pemasok?.nama_pemasok" />
                            <Info label="Alamat Pemasok" :value="data.pemasok?.pemasok_address" />
                            <Info label="Kontak Pemasok" :value="data.pemasok?.pemasok_contact" />
                        </ul>
                    </div>
                    <div v-if="data.jaminan" class="card-layout flex flex-col pb-12.5! gap-6">
                        <h1 class="card-title">Informasi Jaminan</h1>
                        <ul class="grid grid-cols-1 gap-6">
                            <Info label="Tipe Jaminan" :value="data.jaminan.jenis_jaminan" />
                            <Info label="Nama Pemilik" :value="data.jaminan.nama_pemilik" />
                            <Info label="Lokasi Jaminan" :value="data.jaminan.lokasi_kondisi_jaminan" />
                            <Info label="Nilai Pasar Estimasi"
                                :value="moneyParser(data.jaminan.nilai_perkiraan_pasar)" />
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
