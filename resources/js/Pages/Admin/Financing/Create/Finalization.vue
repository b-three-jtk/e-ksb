<script setup>
import BaseInputAdmin from '@/Components/Form/BaseInputAdmin.vue'
import Info from '@/Components/Form/Info.vue'
import parseCurrencyAmount from '@/Composables/moneyParser.js'
import { computed, ref, watch } from 'vue'

const props = defineProps({
    form: Object,
    errors: Object,
    data: Object,
})

const emit = defineEmits(['validate-field'])

const tenor = ref(props.form.pembiayaan.tenor || 1)
const simulasiTenor = ref(tenor.value)

const maxTenor = computed(() => {
    const akhirPeriodeStr = props.data?.tanggal_akhir_periode
    if (!akhirPeriodeStr) return 60;

    const tglAkadStr = props.form.pembiayaan.tgl_akad
    const start = tglAkadStr ? new Date(tglAkadStr) : new Date()
    const end = new Date(akhirPeriodeStr)
    
    if (start >= end) return 0;
    
    if (props.form.pembiayaan.satuan_tenor === 'Minggu') {
        const diffTime = end - start
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
        return Math.floor(diffDays / 7)
    } else {
        const years = end.getFullYear() - start.getFullYear()
        const months = end.getMonth() - start.getMonth()
        let totalMonths = (years * 12) + months
        if (end.getDate() < start.getDate()) {
            totalMonths--
        }
        return Math.max(0, totalMonths)
    }
})

watch(maxTenor, (newMax) => {
    if (tenor.value > newMax) {
        tenor.value = Math.max(1, newMax)
    }
    if (simulasiTenor.value > newMax) {
        simulasiTenor.value = Math.max(1, newMax)
    }
})

watch(tenor, (newVal) => {
    simulasiTenor.value = newVal
})

const tenorOptions = computed(() => {
    const options = []
    const max = maxTenor.value
    for (let i = 1; i <= max; i++) {
        options.push({ value: i, text: String(i) })
    }
    return options
})

const totalPrice = computed(() => {
    const costPrice    = parseFloat(props.form.pembiayaan.harga_perolehan) || 0
    const marginAmount = parseFloat(props.form.pembiayaan.margin_keuntungan) || 0
    const downPayment  = parseFloat(props.form.pembiayaan.uang_muka) || 0
    return costPrice + marginAmount - downPayment
})

const monthlyInstallment = computed(() =>
    tenor.value > 0 ? totalPrice.value / tenor.value : 0
)

const simulasiMonthlyInstallment = computed(() =>
    simulasiTenor.value > 0 ? totalPrice.value / simulasiTenor.value : 0
)

const incomes = [
    { model: 'jml_gaji_pokok' },
    { model: 'jml_penghasilan_usaha' },
    { model: 'jml_penghasilan_pasangan' },
    { model: 'jml_penghasilan_lainnya' },
]
const expenseKeys = [
    'jml_biaya_hidup_keluarga',
    'jml_biaya_pendidikan',
    'jml_cicilan',
    'jml_biaya_lainnya',
]

const monthlyIncome = computed(() => {
    const totalIn  = incomes.reduce((s, i) => s + (Number(props.form.anggota[i.model]) || 0), 0)
    const totalOut = expenseKeys.reduce((s, k) => s + (Number(props.form.anggota[k]) || 0), 0)
    return totalIn - totalOut
})

const remainingIncome = computed(() => monthlyIncome.value - simulasiMonthlyInstallment.value)

const firstDueDate = computed(() => {
    const d = props.form.pembiayaan.tgl_akad ? new Date(props.form.pembiayaan.tgl_akad) : new Date()
    if (props.form.pembiayaan.satuan_tenor === 'Minggu') {
        d.setDate(d.getDate() + 7)
    } else {
        d.setMonth(d.getMonth() + 1)
    }
    return d.toLocaleDateString('id-ID', { year: 'numeric', month: '2-digit', day: '2-digit' })
})

const lastDueDate = computed(() => {
    const d = props.form.pembiayaan.tgl_akad ? new Date(props.form.pembiayaan.tgl_akad) : new Date()
    if (props.form.pembiayaan.satuan_tenor === 'Minggu') {
        d.setDate(d.getDate() + (tenor.value * 7))
    } else {
        d.setMonth(d.getMonth() + tenor.value)
    }
    return d.toLocaleDateString('id-ID', { year: 'numeric', month: '2-digit', day: '2-digit' })
})

const canDownloadDocument = computed(() => {
    const p = props.form.pembiayaan;
    if (!p.metode_pembayaran || !p.tgl_akad) return false;
    
    if (p.metode_pembayaran === 'Cicilan') {
        return tenor.value && p.satuan_tenor;
    } else if (p.metode_pembayaran === 'Tangguh') {
        return !!p.tangguh_tgl_pembayaran;
    }
    return true; // Tunai
})

// Sync tenor & simulasi ke form supaya bisa dikirim ke backend
watch([tenor, monthlyInstallment, monthlyIncome], () => {
    props.form.pembiayaan.tenor   = tenor.value
    props.form.monthly_installment = monthlyInstallment.value
    props.form.monthly_income    = monthlyIncome.value
}, { immediate: true })

const minTangguhDate = computed(() => {
    if (!props.form.pembiayaan.tgl_akad) return undefined;
    const date = new Date(props.form.pembiayaan.tgl_akad);
    date.setDate(date.getDate() + 1);
    return date;
})

const paymentMethods = ['Cicilan', 'Tunai', 'Tangguh']

const onFieldChange = (field) => emit('validate-field', field)
</script>

<template>
    <section>
        <div class="border-b border-gray-200 px-8 pb-4">
            <h1 class="card-title">Finalisasi Pembiayaan Murabahah</h1>
        </div>

        <!-- Detail Objek Pembiayaan -->
        <section class="px-8 py-4">
            <h1 class="card-title text-lg!">Detail Objek Pembiayaan</h1>
            <div class="card-layout grid grid-cols-2 gap-4 mt-2">
                <Info label="Nama Barang"        :value="form.pembiayaan.nama_barang" />
                <Info label="Kualitas"           :value="form.pembiayaan.kondisi_produk" />
                <Info label="Kuantitas"          :value="form.pembiayaan.kuantitas" />
                <Info label="Detail Spesifikasi" :value="form.pembiayaan.spesifikasi_barang" />
            </div>
        </section>

        <!-- Rincian Harga Murabahah -->
        <section class="px-8 py-4">
            <h1 class="card-title text-lg!">Rincian Harga Murabahah</h1>
            <div class="border rounded-2xl overflow-hidden mt-2">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-dark-text dark:text-gray-300 border-y">
                        <tr class="border-b">
                            <th class="text-left pl-6 py-4">Komponen</th>
                            <th class="text-right pr-6 py-4">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 text-dark-text dark:text-gray-200">
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Harga Perolehan Barang</td>
                            <td class="text-right pr-6 py-4">{{ parseCurrencyAmount(form.pembiayaan.harga_perolehan) }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Margin</td>
                            <td class="text-right pr-6 py-4">{{ parseCurrencyAmount(form.pembiayaan.margin_keuntungan) }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Uang Muka</td>
                            <td class="text-right pr-6 py-4">{{ parseCurrencyAmount(form.pembiayaan.uang_muka) }}</td>
                        </tr>
                        <tr class="border-b bg-light-bg dark:bg-gray-700 text-primary dark:text-secondary">
                            <td class="text-left pl-6 py-4 font-semibold">Total Harga Murabahah</td>
                            <td class="text-right pr-6 py-4 font-semibold">{{ parseCurrencyAmount(totalPrice) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Metode & Tanggal Akad -->
        <div class="grid grid-cols-2 gap-4 px-8 py-4">
            <div>
                <BaseInputAdmin
                    required
                    v-model="form.pembiayaan.metode_pembayaran"
                    label="Metode Pembayaran"
                    type="select"
                    :selectables="paymentMethods.map(v => ({ value: v, text: v }))"
                    :error="errors?.metode_pembayaran"
                    @change="onFieldChange('metode_pembayaran')"
                />
            </div>
            <div>
                <BaseInputAdmin
                    v-model="form.pembiayaan.tgl_akad"
                    label="Tanggal Akad"
                    type="date"
                    required
                    :error="errors?.tgl_akad"
                    @change="onFieldChange('tgl_akad')"
                />
            </div>
        </div>

        <section v-if="form.pembiayaan.metode_pembayaran === 'Tangguh'" class="px-8 py-4">
            <BaseInputAdmin
                v-model="form.pembiayaan.tangguh_tgl_pembayaran"
                label="Tanggal Pembayaran Tangguh"
                type="date"
                :error="errors?.tangguh_tgl_pembayaran"
                required
                :minDate="minTangguhDate"
                hint="Tanggal pembayaran tangguh harus setelah tanggal akad"
                @change="onFieldChange('tangguh_tgl_pembayaran')"
            />
        </section>

        <section v-if="form.pembiayaan.metode_pembayaran === 'Cicilan'" class="grid grid-cols-2 gap-4 px-8 py-4">
            <BaseInputAdmin
                type="select"
                v-model.number="tenor"
                label="Jangka Waktu (Tenor)"
                :selectables="tenorOptions"
                required
                :hint="data?.tanggal_akhir_periode ? `Berdasarkan sisa waktu hingga akhir periode (${new Date(data.tanggal_akhir_periode).toLocaleDateString('id-ID')})` : ''"
                @change="onFieldChange('tenor')"
            />
            <BaseInputAdmin
                v-model="form.pembiayaan.satuan_tenor"
                label="Satuan Waktu"
                type="select"
                :selectables="[{value: 'Bulan', text: 'Bulan'}, {value: 'Minggu', text: 'Minggu'}]"
                required
                @change="onFieldChange('satuan_tenor')"
            />
        </section>

        <!-- Simulasi Cicilan (hanya jika Cicilan) -->
        <section v-if="form.pembiayaan.metode_pembayaran === 'Cicilan'" class="px-8 py-4">
            <h1 class="card-title text-lg!">Simulasi Cicilan</h1>
            <div class="bg-white dark:bg-gray-800 border rounded-2xl p-6 mt-4">
                <div class="mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Representasi Jangka Waktu</label>
                        <span class="text-lg font-semibold text-primary dark:text-secondary">{{ simulasiTenor }} {{ form.pembiayaan.satuan_tenor }}</span>
                    </div>
                    
                    <input
                        v-model.number="simulasiTenor"
                        type="range"
                        :min="Math.min(1, maxTenor)"
                        :max="maxTenor"
                        step="1"
                        class="w-full h-2 rounded-lg appearance-none cursor-pointer"
                        :style="{
                            background: maxTenor > 1 ? `linear-gradient(to right, #007943 0%, #007943 ${((simulasiTenor - Math.min(1, maxTenor)) / (maxTenor - Math.min(1, maxTenor))) * 100}%, #e5e7eb ${((simulasiTenor - Math.min(1, maxTenor)) / (maxTenor - Math.min(1, maxTenor))) * 100}%, #e5e7eb 100%)` : '#e5e7eb'
                        }"
                    />
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                        <span>{{ Math.min(1, maxTenor) }}</span><span>{{ maxTenor }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <!-- Jumlah Pembiayaan -->
                    <div>
                        <p class="text-gray-500 dark:text-gray-300 mb-2">Jumlah Pembiayaan</p>
                        <p class="text-lg font-semibold text-dark-text dark:text-gray-200">{{ parseCurrencyAmount(totalPrice) }}</p>
                    </div>

                    <!-- Perkiraan Cicilan -->
                    <div>
                        <p class="text-gray-500 dark:text-gray-300 mb-2">Perkiraan Cicilan</p>
                        <p class="text-lg font-semibold text-dark-text dark:text-gray-200">{{ parseCurrencyAmount(simulasiMonthlyInstallment)
                        }}<span class="text-sm text-gray-500 dark:text-gray-300">/{{ form.pembiayaan.satuan_tenor?.toLowerCase() }}</span></p>
                    </div>
                </div>

                <!-- Sisa Penghasilan -->
                <div class="mt-6 pt-6 border-t">
                    <p class="text-gray-500 mb-2 dark:text-gray-300">Sisa Penghasilan Bulanan (setelah cicilan)</p>
                    <p class="text-lg font-semibold" :class="remainingIncome >= 0 ? 'text-secondary' : 'text-red-600'">
                        {{ parseCurrencyAmount(remainingIncome) }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Skema Angsuran (hanya jika Cicilan) -->
        <section v-if="form.pembiayaan.metode_pembayaran === 'Cicilan'" class="px-8 py-4">
            <h1 class="card-title text-lg!">Skema Angsuran</h1>
            <div class="border rounded-2xl overflow-hidden mt-2">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-dark-text dark:text-gray-300 border-y">
                        <tr class="border-b">
                            <th class="text-left pl-6 py-4">Keterangan</th>
                            <th class="text-right pr-6 py-4">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 text-dark-text dark:text-gray-200">
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Tenor / Jangka Waktu</td>
                            <td class="text-right pr-6 py-4">{{ tenor }} {{ form.pembiayaan.satuan_tenor }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Angsuran / {{ form.pembiayaan.satuan_tenor }}</td>
                            <td class="text-right pr-6 py-4">{{ parseCurrencyAmount(monthlyInstallment) }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Jatuh Tempo Pertama</td>
                            <td class="text-right pr-6 py-4">{{ firstDueDate }}</td>
                        </tr>
                        <tr class="border-b">
                            <td class="text-left pl-6 py-4">Jatuh Tempo Terakhir</td>
                            <td class="text-right pr-6 py-4">{{ lastDueDate }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="px-8 pb-8 grid grid-cols-2 items-end gap-4">
            <a :href="form.pembiayaan.id && canDownloadDocument ? `/admin/pembiayaan/${form.pembiayaan.id}/murabahah/download?tenor=${tenor}&satuan_tenor=${form.pembiayaan.satuan_tenor || 'Bulan'}&uang_muka=${form.pembiayaan.uang_muka || 0}&margin=${form.pembiayaan.margin_keuntungan || 0}&nama_pemasok=${form.pemasok?.nama_pemasok || ''}&alamat_pemasok=${form.pemasok?.alamat_pemasok || ''}&metode_pembayaran=${form.pembiayaan.metode_pembayaran || ''}&tangguh_tgl_pembayaran=${form.pembiayaan.tangguh_tgl_pembayaran || ''}` : '#'" target="_blank"
                :class="[
                    'border flex justify-between rounded-lg p-4 transition-colors',
                    (!canDownloadDocument) ? 'border-gray-200 bg-gray-50 cursor-not-allowed pointer-events-none' : 'border-primary bg-primary/5 hover:bg-primary/10'
                ]">
                <div :class="['text-sm font-medium', (!canDownloadDocument) ? 'text-gray-400' : 'text-primary']">
                    Unduh Dokumen Akad Murabahah
                </div>
                <span :class="['icon-[tabler--download] text-xl', (!canDownloadDocument) ? 'text-gray-400' : 'text-primary']"></span>
            </a>
            <div>
                <BaseInputAdmin
                    type="file"
                    label="Upload Dokumen Akad Murabahah Tertandatangani"
                    v-model="form.akad_document_file"
                    accept="application/pdf"
                    required
                    :disabled="!canDownloadDocument"
                    :error="errors?.akad_document_file"
                    @change="onFieldChange('akad_document_file')"
                />
                <div class="flex justify-between text-xs text-gray-400 mt-1">
                    <p>Format: PDF</p>
                    <p>Max. 2 MB per file</p>
                </div>
            </div>
        </div>
    </section>
</template>
