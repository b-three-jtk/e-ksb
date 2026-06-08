<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/Admin/Layout.vue'
import { Icon } from '@iconify/vue'
import { ref, computed, reactive, watch } from 'vue'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'
import CardInfo from '../../../Components/CardInfo.vue'
import BaseFunctionality from '../../../Components/Table/BaseFunctionality.vue'
import BaseTable from '../../../Components/Table/BaseTable.vue'
import Pagination from '../../../Components/Table/Pagination.vue'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'

const isLoading = ref(false)

const props = defineProps({
    transactions: Object,
    summary: Array,
    filters: Object,
    akunOptions: Array,
})

const columns = [
    { key: 'no',          label: 'No' },
    { key: 'no_jurnal',   label: 'No. Jurnal' },
    { key: 'tanggal',     label: 'Tanggal', sortable: true },
    { key: 'akun',        label: 'Akun' },
    { key: 'jenis_akun',  label: 'Jenis Akun' },
    { key: 'debit',       label: 'Debit',  align: 'left' },
    { key: 'kredit',      label: 'Kredit', align: 'left' },
]

const page = usePage()

const filters = reactive({
    search:    page.props.filters?.search    ?? '',
    per_page:  page.props.filters?.per_page  ?? 10,
    periode:   page.props.filters?.periode   ?? '',
    date_from: page.props.filters?.date_from ?? '',
    date_to:   page.props.filters?.date_to   ?? '',
    sort_by:   page.props.filters?.sort_by   ?? 'tanggal',
    sort_dir:  page.props.filters?.sort_dir  ?? 'desc',
})

const showDatePicker = computed(() => {
    return filters.periode === 'custom'
})

const validateDateRange = () => {

    if (
        filters.date_from &&
        filters.date_to &&
        filters.date_from > filters.date_to
    ) {

        toast.error(
            'Tanggal dari tidak boleh melebihi tanggal sampai',
            {
                position: 'bottom-right'
            }
        )

        return false
    }

    return true
}

const exportQuery = computed(() => {
    const params = {}
    if (filters.search)    params.search    = filters.search
    if (filters.periode)   params.periode   = filters.periode
    if (filters.date_from) params.date_from = filters.date_from 
    if (filters.date_to)   params.date_to   = filters.date_to
    params.sort_by  = filters.sort_by
    params.sort_dir = filters.sort_dir
    return new URLSearchParams(params).toString()
})

const toggleSort = (column) => {
    if (filters.sort_by === column) {
        filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
    } else {
        filters.sort_by  = column
        filters.sort_dir = 'asc'
    }
    applyFilters()
}

const transactions = computed(() => page.props.transactions ?? {
    data: [], current_page: 1, per_page: 10, total: 0, links: [],
})

const summary = computed(() => page.props.summary ?? [])

const isFirstInGroup = (index) => {
    if (index === 0) return true
    const current  = transactions.value.data[index]
    const previous = transactions.value.data[index - 1]
    return String(current.no) !== String(previous.no)
}

const getJenisColor = (jenis) => {
    if (!jenis) return { bg: 'bg-gray-100 dark:bg-slate-700', text: 'text-gray-600 dark:text-slate-300' }
    switch (jenis) {
        case 'Aset':
            return { bg: 'bg-cyan-100 dark:bg-cyan-900/40', text: 'text-cyan-700 dark:text-cyan-200' }
        case 'Liabilitas':
            return { bg: 'bg-green-100 dark:bg-green-900/40', text: 'text-green-700 dark:text-green-200' }
        case 'Ekuitas':
            return { bg: 'bg-pink-100 dark:bg-pink-900/40', text: 'text-pink-700 dark:text-pink-200' }
        case 'Pendapatan':
            return { bg: 'bg-orange-100 dark:bg-orange-900/40', text: 'text-orange-700 dark:text-orange-200' }
        case 'Beban':
            return { bg: 'bg-red-100 dark:bg-red-900/40', text: 'text-red-700 dark:text-red-200' }
        default:
            return { bg: 'bg-gray-100 dark:bg-slate-700', text: 'text-gray-600 dark:text-slate-100' }
    }
}

const applyFilters = () => {

    if (!validateDateRange()) {
        return
    }

    isLoading.value = true

    router.get(
        '/admin/kas/list',
        {
            search: filters.search || undefined,
            per_page: filters.per_page,
            periode: filters.periode || undefined,
            date_from: filters.date_from || undefined,
            date_to: filters.date_to || undefined,
            sort_by: filters.sort_by,
            sort_dir: filters.sort_dir,
            page: 1,
        },
        {
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isLoading.value = false
            },
        }
    )
}

let timeout
watch(() => filters.search, () => {
    clearTimeout(timeout)
    timeout = setTimeout(applyFilters, 500)
})
watch(() => [filters.per_page, filters.periode], () => {
    // Reset date range kalau ganti ke bukan custom
    if (filters.periode !== 'custom') {
        filters.date_from = ''
        filters.date_to   = ''
    }
    applyFilters()
})
watch(
    () => [filters.date_from, filters.date_to],
    () => {

        if (filters.periode !== 'custom') {
            return
        }

        if (
            filters.date_from &&
            filters.date_to &&
            validateDateRange()
        ) {
            applyFilters()
        }
    }
)

watch(
    () => filters.date_from,
    () => {
        if (
            filters.date_to &&
            filters.date_to < filters.date_from
        ) {
            filters.date_to = ''
        }
    }
)

// Modal Tambah Alokasi
const showModal    = ref(false)
const isSubmitting = ref(false)

const formDefault = () => ({
    nominal: '',
    akun_debit: '',
    akun_kredit: '',
})

const form   = reactive(formDefault())
const errors = reactive({
    nominal: '',
    akun_debit: '',
    akun_kredit: '',
})

const validateForm = () => {
    errors.nominal     = form.nominal     ? '' : 'Nominal wajib diisi'
    errors.akun_debit  = form.akun_debit  ? '' : 'Akun debit wajib dipilih'
    errors.akun_kredit = form.akun_kredit ? '' : 'Akun kredit wajib dipilih'
    return !Object.values(errors).some(Boolean)
}

const openModal = () => {
    Object.assign(form, formDefault())
    Object.assign(errors, {
        nominal: '',
        akun_debit: '',
        akun_kredit: '',
    })
    showModal.value = true
}

const closeModal = () => { showModal.value = false }

const nominalDisplay = computed({
    get() {
        return form.nominal
            ? 'Rp ' + Number(form.nominal).toLocaleString('id-ID')
            : ''
    },

    set(value) {
        form.nominal = value.replace(/\D/g, '')
    },
})

const submitForm = async () => {

    if (!validateForm()) return

    const result = await Swal.fire({
        title: 'Posting Alokasi Kas?',
        text: 'Data yang diposting akan dicatat ke jurnal.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Posting',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#16a34a',
    })

    if (!result.isConfirmed) {
        return
    }

    isSubmitting.value = true

    router.post(
        '/admin/kas/store',
        { ...form },
        {
            preserveScroll: true,

            onSuccess: () => {

                closeModal()

                toast.success(
                    'Alokasi kas berhasil diposting',
                    {position: 'bottom-right'}
                )
            },

            onError: (serverErrors) => {

                errors.nominal =
                    serverErrors.nominal ?? ''

                errors.akun_debit =
                    serverErrors.akun_debit ?? ''

                errors.akun_kredit =
                    serverErrors.akun_kredit ?? ''

                toast.error(
                    'Gagal memposting alokasi kas',
                    {
                        position: 'bottom-right'
                    }
                )
            },

            onFinish: () => {
                isSubmitting.value = false
            },
        }
    )
}

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengelolaan Alokasi Kas' },
]

// Periode kustom ajahh
const periodeOptions = [
    { label: '1 Minggu', value: '1_minggu' },
    { label: '1 Bulan',  value: '1_bulan'  },
    { label: '3 Bulan',  value: '3_bulan'  },
    { label: '1 Tahun',  value: '1_tahun'  },
    { label: 'Pilih Tanggal', value: 'custom' },
]
</script>

<template>
    <AdminLayout title="Pengelolaan Alokasi Kas">
        <PageBreadcrumb page-title="Pengelolaan Alokasi Kas" :items="breadcrumbItems" />

        <!-- Ringkasan -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Ringkasan
                </h2>

                <button
                    @click="openModal"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-secondary rounded-lg hover:bg-primary transition"
                >
                    <Icon icon="mdi:plus" class="w-4 h-4" />
                    Tambah Alokasi Kas
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <CardInfo
                    v-for="item in summary"
                    :key="item.title"
                    :title="item.title"
                    :content="item.value"
                    :percentage="item.percentage"
                />
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-hidden">
            <!-- Header -->
            <div class="px-6 pt-6 pb-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100 mb-0.5">
                    Data Arus Kas
                </h2>
                <p class="text-sm text-gray-500 dark:text-slate-400">
                    Lacak transaksi kas masuk dan kas keluar koperasi di sini
                </p>
            </div>

            <!-- Functionality -->
            <BaseFunctionality
                :per-page="filters.per_page"
                :search="filters.search"
                @update:per-page="val => filters.per_page = Number(val)"
                @update:search="val => filters.search = val"
            >
                <template #actions>
                    <!-- Pilih Periode -->
                    <div class="relative">
                        <select
                            v-model="filters.periode"
                            class="appearance-none border rounded-lg px-3 py-2 pr-8 text-sm
                                bg-white text-gray-900
                                dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Pilih Periode</option>
                            <option v-for="p in periodeOptions" :key="p.value" :value="p.value">
                                {{ p.label }}
                            </option>
                        </select>
                        <Icon
                            icon="mdi:calendar-outline"
                            class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                        />
                    </div>

                    <!-- Range Tanggal — muncul kalau pilih "Pilih Tanggal" -->
                    <template v-if="showDatePicker">
                        <div class="flex items-center gap-2">
                            <input
                                v-model="filters.date_from"
                                type="date"
                                class="border rounded-lg px-3 py-2 text-sm
                                    bg-white text-gray-900
                                    dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                    focus:ring-2 focus:ring-blue-500"
                            />
                            <span class="text-sm text-gray-500">s/d</span>
                            <input
                                v-model="filters.date_to"
                                type="date"
                                :min="filters.date_from"
                                class="border rounded-lg px-3 py-2 text-sm
                                    bg-white text-gray-900
                                    dark:bg-gray-700 dark:border-gray-600 dark:text-white
                                    focus:ring-2 focus:ring-blue-500"
                            />
                            <button
                                @click="applyFilters"
                                class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                            >
                                Terapkan
                            </button>
                        </div>
                    </template>

                    <!-- Export CSV -->
                    <a
                        :href="`/admin/kas/export/csv?${exportQuery}`"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                    >
                        <Icon icon="mdi:file-delimited-outline" class="w-4 h-4" />
                        Export CSV
                    </a>
                </template>
            </BaseFunctionality>

            <!-- Table -->
            <BaseTable
                :columns="columns"
                :data="transactions.data"
                :is-loading="isLoading"
                :pagination="transactions"
                :sort-by="filters.sort_by"
                :sort-dir="filters.sort_dir"
                @sort="toggleSort"
            >
                <template #cell-no="{ row, index }">
                    {{ isFirstInGroup(index) ? row.no : '' }}
                </template>

                <template #cell-jenis_akun="{ row }">
                    <span
                        class="px-3 py-1 text-xs rounded-full font-medium"
                        :class="[getJenisColor(row.jenis_akun).bg, getJenisColor(row.jenis_akun).text]"
                    >
                        {{ row.jenis_akun }}
                    </span>
                </template>

                <template #cell-debit="{ row }">
                    <span v-if="row.debit" class="font-medium text-gray-800 dark:text-gray-100">
                        Rp{{ Number(row.debit).toLocaleString('id-ID') }}
                    </span>
                </template>

                <template #cell-kredit="{ row }">
                    <span v-if="row.kredit" class="font-medium text-gray-800 dark:text-gray-100">
                        Rp{{ Number(row.kredit).toLocaleString('id-ID') }}
                    </span>
                </template>
            </BaseTable>

            <!-- Pagination -->
            <Pagination
                :links="transactions.links"
                :total="transactions.total"
            />
        </div>

        <!-- ─── Modal Tambah Alokasi Kas ──────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center"
                >
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50" @click="closeModal" />

                    <!-- Dialog -->
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4">

                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-head font-semibold text-base tracking-wide text-gray-800 dark:text-gray-100 uppercase">
                                Tambah Alokasi Kas
                            </h3>
                            <button
                                type="button"
                                @click="closeModal"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                            >
                                <Icon icon="mdi:close" class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="px-6 py-5 space-y-5">

                            <!-- Row: Tanggal + Nominal -->
                            <div>
                                <!-- Nominal -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Nominal Alokasi
                                    </label>
                                    <input
                                        v-model="nominalDisplay"
                                        type="text"
                                        placeholder="Rp 12.000.000"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm
                                               bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                               placeholder-gray-400 dark:placeholder-gray-500
                                               focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                    />
                                    <p v-if="errors.nominal" class="mt-1 text-xs text-red-500">{{ errors.nominal }}</p>
                                </div>
                            </div>

                            <!-- Row: Akun Debit + Akun Kredit -->
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Akun Debit -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Akun Debit
                                    </label>
                                    <select
                                        v-model="form.akun_debit"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm
                                               bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                               focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                    >
                                        <option value="" disabled>Pilih akun debit</option>
                                        <option
                                            v-for="akun in akunOptions"
                                            :key="akun.nomor_akun"
                                            :value="akun.nomor_akun"
                                        >
                                            {{ akun.nomor_akun }} - {{ akun.nama_akun }}
                                        </option>
                                    </select>
                                    <p v-if="errors.akun_debit" class="mt-1 text-xs text-red-500">{{ errors.akun_debit }}</p>
                                </div>

                                <!-- Akun Kredit -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Akun Kredit
                                    </label>
                                    <select
                                        v-model="form.akun_kredit"
                                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm
                                               bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                               focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                    >
                                        <option value="" disabled>Pilih akun kredit</option>
                                        <option
                                            v-for="akun in akunOptions"
                                            :key="akun.nomor_akun"
                                            :value="akun.nomor_akun"
                                        >
                                            {{ akun.nomor_akun }} - {{ akun.nama_akun }}
                                        </option>
                                    </select>
                                    <p v-if="errors.akun_kredit" class="mt-1 text-xs text-red-500">{{ errors.akun_kredit }}</p>
                                </div>
                            </div>

                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 flex justify-center border-t border-gray-100 dark:border-gray-700">
                            <button
                                type="button"
                                @click="submitForm"
                                :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 disabled:opacity-60
                                       text-white font-semibold px-8 py-2.5 rounded-lg transition-colors"
                            >
                                <Icon v-if="isSubmitting" icon="tabler:loader-2" class="w-4 h-4 animate-spin" />
                                Posting
                            </button>
                        </div>

                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}
.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.96);
}
</style>