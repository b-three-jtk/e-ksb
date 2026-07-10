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

const remainingIncome = computed(() => monthlyIncome.value - monthlyInstallment.value)

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

// Sync tenor & simulasi ke form supaya bisa dikirim ke backend
watch([tenor, monthlyInstallment, monthlyIncome], () => {
    props.form.pembiayaan.tenor   = tenor.value
    props.form.monthly_installment = monthlyInstallment.value
    props.form.monthly_income    = monthlyIncome.value
}, { immediate: true })

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
                <Info label="Nama Barang"        :value="form.pembiayaan.name" />
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
                hint="Tanggal pembayaran tangguh harus setelah tanggal akad"
                @change="onFieldChange('tangguh_tgl_pembayaran')"
            />
        </section>

        <!-- Simulasi Cicilan (hanya jika Cicilan) -->
        <section v-if="form.pembiayaan.metode_pembayaran === 'Cicilan'" class="px-8 py-4">
            <h1 class="card-title text-lg!">Simulasi Cicilan</h1>
            <div class="bg-white dark:bg-gray-800 border rounded-2xl p-6 mt-4">
                <!-- Tenor Slider -->
                <div class="mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Jangka Waktu Cicilan</label>
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-semibold text-primary dark:text-secondary">{{ tenor }}</span>
                            <select 
                                v-model="form.pembiayaan.satuan_tenor" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                @change="onFieldChange('satuan_tenor')"
                            >
                                <option value="Bulan">Bulan</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>
                    </div>
                    
                    <input
                        v-model.number="tenor"
                        type="range"
                        :min="1"
                        :max="maxTenor"
                        step="1"
                        class="w-full h-2 rounded-lg appearance-none cursor-pointer bg-gray-200 dark:bg-gray-700"
                    />
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                        <span>1</span><span>{{ maxTenor }}</span>
                    </div>
                    <div v-if="data?.tanggal_akhir_periode" class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                        <span class="icon-[tabler--info-circle] align-middle mr-1"></span>
                        Maksimal tenor yang dapat dipilih adalah {{ maxTenor }} {{ form.pembiayaan.satuan_tenor }} (hingga akhir periode: {{ new Date(data.tanggal_akhir_periode).toLocaleDateString('id-ID') }})
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
                        <p class="text-lg font-semibold text-dark-text dark:text-gray-200">{{ parseCurrencyAmount(monthlyInstallment)
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
            <a :href="form.pembiayaan.id ? `/admin/pembiayaan/${form.pembiayaan.id}/murabahah/download?tenor=${tenor}&satuan_tenor=${form.pembiayaan.satuan_tenor || 'Bulan'}&uang_muka=${form.pembiayaan.uang_muka || 0}&margin=${form.pembiayaan.margin_keuntungan || 0}&nama_pemasok=${form.pemasok?.nama_pemasok || ''}&alamat_pemasok=${form.pemasok?.alamat_pemasok || ''}` : '#'" target="_blank"
                class="border border-gray-300 flex justify-between rounded-lg p-4">
                <div class="text-sm text-primary hover:underline">
                    Unduh Dokumen Akad Murabahah
                </div>
                <span class="icon-[tabler--download] text-green-500"></span>
            </a>
            <div>
                <BaseInputAdmin
                    type="file"
                    label="Upload Dokumen Akad Murabahah Tertandatangani"
                    v-model="form.akad_document_file"
                    accept="application/pdf"
                    required
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
