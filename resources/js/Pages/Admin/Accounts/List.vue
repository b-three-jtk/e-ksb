<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import AdminLayout from '../../../Layouts/Admin/Layout.vue'
import { reactive, watch, ref } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { toast } from 'vue3-toastify'
import PageBreadcrumb from '../../../Components/PageBreadcrumb.vue'
import BaseFunctionality from '../../../Components/Table/BaseFunctionality.vue'
import BaseTable from '../../../Components/Table/BaseTable.vue'
import Pagination from '../../../Components/Table/Pagination.vue'
import CardInfo from '../../../Components/CardInfo.vue'

const props = defineProps<{
    accounts: {
        data: any[]
        current_page: number
        per_page: number
        total: number
        links: { url: string | null; label: string; active: boolean }[]
    }
    filters: Record<string, any>
    jenisAkunOptions: string[]
    accountSummary: { 
        name: string; 
        balance: number 
    }[]
}>()

// Modal state
const showModal = ref(false)
const isSubmitting = ref(false)

const formDefault = () => ({
    nomor_akun: '',
    nama_akun: '',
    jenis_akun: '',
})

const errors = reactive({
    nomor_akun: '',
    nama_akun: '',
    jenis_akun: '',
})

const validateForm = () => {
    errors.nomor_akun = ''
    errors.nama_akun = ''
    errors.jenis_akun = ''

    let valid = true

    if (!form.nomor_akun.trim()) {
        errors.nomor_akun = 'Nomor akun wajib diisi'
        valid = false
    } else if (!/^\d+$/.test(form.nomor_akun)) {
        errors.nomor_akun = 'Nomor akun hanya boleh berisi angka'
        valid = false
    }

    if (!form.nama_akun.trim()) {
        errors.nama_akun = 'Nama akun wajib diisi'
        valid = false
    }

    if (!form.jenis_akun) {
        errors.jenis_akun = 'Jenis akun wajib dipilih'
        valid = false
    }

    return valid
}

const form = reactive(formDefault())

const openCreateModal = () => {
    Object.assign(form, formDefault())

    errors.nomor_akun = ''
    errors.nama_akun = ''
    errors.jenis_akun = ''

    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
}

const submitForm = async () => {
    if (!validateForm()) {
        toast.error('Lengkapi seluruh data terlebih dahulu', {
            position: 'bottom-right',
        })
        return
    }
    const result = await Swal.fire({
        title: 'Konfirmasi Tambah Akun',
        html: `
            <div style="text-align:left">
                <p><b>Nomor Akun:</b> ${form.nomor_akun}</p>
                <p><b>Nama Akun:</b> ${form.nama_akun}</p>
                <p><b>Jenis Akun:</b> ${form.jenis_akun}</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Tambahkan',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280',
    })

    if (!result.isConfirmed) return

    isSubmitting.value = true
    router.post(
        '/admin/accounts/create',
        {
            nomor_akun: form.nomor_akun,
            nama_akun: form.nama_akun,
            jenis_akun: form.jenis_akun,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                Object.assign(form, formDefault())

                showModal.value = false

                toast.success('Akun berhasil ditambahkan', {
                    position: 'bottom-right',
                })
            },

            onError: (serverErrors) => {

                errors.nomor_akun = serverErrors.nomor_akun ?? ''
                errors.nama_akun = serverErrors.nama_akun ?? ''
                errors.jenis_akun = serverErrors.jenis_akun ?? ''

                const firstError = Object.values(serverErrors)[0]

                toast.error(
                    typeof firstError === 'string'
                        ? firstError
                        : 'Terjadi kesalahan',
                    {
                        position: 'bottom-right',
                    }
                )
            },

            onFinish: () => {
                isSubmitting.value = false
            },
        }
    )
}

const showStatusModal = ref(false)

const statusForm = reactive({
    id: '',
    nomor_akun: '',
    nama_akun: '',
    jenis_akun: '',
    status: 'Aktif',
})

const openStatusModal = (row: any) => {
    statusForm.id = row.id
    statusForm.nomor_akun = row.nomor_akun
    statusForm.nama_akun = row.nama_akun
    statusForm.jenis_akun = row.jenis_akun
    statusForm.status = row.status

    showStatusModal.value = true
}

const updateStatus = async () => {
    const result = await Swal.fire({
        title: 'Konfirmasi Perubahan Status',
        text: `Apakah Anda yakin ingin mengubah status akun menjadi ${statusForm.status}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280',
    })

    if (!result.isConfirmed) return

    router.patch(
        `/admin/accounts/${statusForm.id}/status`,
        {
            status: statusForm.status,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Status akun berhasil diperbarui', {
                    position: 'bottom-right'
                })
                showStatusModal.value = false
            },
        }
    )
}

// Filters
const selectFilters = [
    {
        key: 'jenis_akun',
        label: 'Semua Jenis Akun',
        options: props.jenisAkunOptions.map((j) => ({
            label: j,
            value: j,
        })),
        optionLabel: 'label',
        optionValue: 'value',
    },
    {
        key: 'status',
        label: 'Semua Status',
        options: [
            { label: 'Aktif', value: 'Aktif' },
            { label: 'Non-Aktif', value: 'Non-Aktif' },
        ],
        optionLabel: 'label',
        optionValue: 'value',
    },
]

const filters = reactive({
    search: props.filters?.search ?? '',
    jenis_akun: props.filters?.jenis_akun ?? '',
    status: props.filters?.status ?? '',
    per_page: props.filters?.per_page ?? 10,
    sort_by: props.filters?.sort_by ?? 'nomor_akun',
    sort_dir: props.filters?.sort_dir ?? 'asc',
})

const applyFilters = () => {
    router.get(
        '/admin/accounts/list',
        {
            search: filters.search || undefined,
            jenis_akun: filters.jenis_akun || undefined,
            status: filters.status || undefined,
            per_page: filters.per_page,
            sort_by: filters.sort_by,
            sort_dir: filters.sort_dir,
        },
        { preserveScroll: true, replace: true, preserveState: false }
    )
}

let timeout: ReturnType<typeof setTimeout>
watch(() => filters.search, () => {
    clearTimeout(timeout)
    timeout = setTimeout(applyFilters, 500)
})

watch(
    () => [
        filters.per_page,
        filters.jenis_akun,
        filters.status,
    ],
    applyFilters
)

const toggleSort = (column: string) => {
    if (filters.sort_by === column) {
        filters.sort_dir = filters.sort_dir === 'asc' ? 'desc' : 'asc'
    } else {
        filters.sort_by = column
        filters.sort_dir = 'asc'
    }
    applyFilters()
}

// Kolom tabel
const columns = [
    { key: 'no', label: 'No', align: 'left' as const },
    { key: 'nomor_akun', label: 'Nomor Akun', sortable: true, align: 'left' as const },
    { key: 'nama_akun', label: 'Nama Akun', sortable: true },
    { key: 'jenis_akun', label: 'Jenis Akun', align: 'left' as const },
    { key: 'saldo', label: 'Saldo', align: 'left' as const },
    { key: 'status', label: 'Status', align: 'left' as const },
    { key: 'aksi', label: 'Aksi', align: 'left' as const },
]

// Format untuk saldo
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0)
}

// Badge class berdasarkan jenis akun
const jenisClass = (jenis: string) => {
    switch (jenis) {
        case 'Aset':
            return 'bg-cyan-100 text-cyan-700 border border-cyan-200'
        case 'Liabilitas':
            return 'bg-green-100 text-green-700 border border-green-200'
        case 'Ekuitas':
            return 'bg-pink-100 text-pink-700 border border-pink-200'
        case 'Pendapatan':
            return 'bg-orange-100 text-orange-700 border border-orange-200'
        case 'Beban':
            return 'bg-red-100 text-red-700 border border-red-200'
        default:
            return 'bg-gray-100 text-gray-600 border border-gray-200'
    }
}

const breadcrumbItems = [
    { name: 'Dashboard', link: '/admin' },
    { name: 'Pengelolaan Akun', link: '' },
]

const nomorAkunGuide = [
    { kode: '1xx', label: 'Aset' },
    { kode: '2xx', label: 'Liabilitas' },
    { kode: '3xx', label: 'Ekuitas' },
    { kode: '4xx', label: 'Pendapatan' },
    { kode: '5xx', label: 'Beban' },
]
</script>

<template>
    <AdminLayout title="Pengelolaan Data Akun">
        <!-- Breadcrumb -->
        <PageBreadcrumb page-title="Pengelolaan Data Akun" :items="breadcrumbItems" />

        <!-- Ringkasan -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Ringkasan
                </h2>

                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-semibold font-head px-5 py-2 rounded-xl shadow-sm transition-colors"
                >
                    <Icon icon="mdi:plus" class="w-5 h-5" />
                    Tambah Akun
                </button>
            </div>

            <!-- Card Summary Info -->
            <div>
                <div
                    class="flex gap-4 overflow-x-auto pb-2 w-full"
                >
                    <div
                        v-for="item in props.accountSummary"
                        :key="item.name"
                        class="min-w-[280px] flex-none"
                    >
                        <CardInfo
                            :title="`Total ${item.name}`"
                            :content="formatCurrency(item.balance)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <!-- Card Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h2 class="font-head text-lg font-semibold text-gray-900 dark:text-gray-100">Data Akun</h2>
                </div>
            </div>

            <!-- Filter & Search -->
            <BaseFunctionality
                :per-page="filters.per_page"
                :search="filters.search"
                :filters="{
                    jenis_akun: filters.jenis_akun,
                    status: filters.status
                }"
                :selects="selectFilters"
                @update:per-page="val => filters.per_page = val"
                @update:search="val => filters.search = val"
                @update:filters="val => {
                    filters.jenis_akun = val.jenis_akun
                    filters.status = val.status
                }"
            />

            <!-- Table -->
            <BaseTable
                :columns="columns"
                :data="accounts.data"
                :pagination="accounts"
                :sort-by="filters.sort_by"
                :sort-dir="filters.sort_dir"
                @sort="toggleSort"
            >

                <template #cell-no="{ index }">
                    {{ (accounts.current_page - 1) * accounts.per_page + index + 1 }}
                </template>

                <!-- Nomor Akun centered -->
                <template #cell-nomor_akun="{ row }">
                    <span class="font-mono">{{ row.nomor_akun }}</span>
                </template>

                <!-- Jenis Akun Badge -->
                <template #cell-jenis_akun="{ row }">
                    <span
                        class="px-4 py-1 text-xs rounded-full font-medium"
                        :class="jenisClass(row.jenis_akun)"
                    >
                        {{ row.jenis_akun }}
                    </span>
                </template>

                <template #cell-saldo="{ row }">
                    <span
                        class="font-semibold"
                        :class="row.saldo < 0 ? 'text-red-600' : 'text-green-600'"
                    >
                        {{ formatCurrency(row.saldo) }}
                    </span>
                </template>

                <template #cell-status="{ row }">
                    <span
                        class="px-4 py-1 text-xs rounded-full font-medium"
                        :class="
                            row.status === 'Aktif'
                                ? 'bg-green-100 text-green-700 border border-green-200'
                                : 'bg-red-100 text-red-700 border border-red-200'
                        "
                    >
                        {{ row.status }}
                    </span>
                </template>

                <!-- Aksi -->
                <template #cell-aksi="{ row }">
                    <button
                        @click="openStatusModal(row)"
                        title="Ubah Status"
                    >
                        <Icon icon="mdi:pencil-outline" class="w-5 h-5" />
                    </button>
                </template>
            </BaseTable>

            <!-- Pagination -->
            <Pagination :links="accounts.links" :total="accounts.total" />
        </div>

        <!--Modal Tambah Akun-->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center"
                >
                    <!-- Backdrop -->
                    <div
                        class="absolute inset-0 bg-black/50"
                        @click="closeModal"
                    />

                    <!-- Dialog -->
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-head font-semibold text-base tracking-wide text-gray-800 dark:text-gray-100 uppercase">
                                Tambah Akun
                            </h3>
                            <button
                                type="button"
                                @click="closeModal"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                            >
                                <Icon icon="mdi:close" class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Panduan Nomor Akun -->
                        <div class="border border-primary dark:border-green-700 rounded-lg px-4 py-3 mr-6 ml-6 mt-6">
                            <p class="flex items-center gap-1.5 text-xs font-semibold text-primary dark:text-green-400 mb-2">
                                <Icon icon="tabler:info-circle" class="w-3.5 h-3.5" />
                                Panduan penomoran akun
                            </p>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                                <div v-for="item in nomorAkunGuide" :key="item.kode" class="flex items-baseline gap-2">
                                    <span class="font-mono text-xs font-medium bg-light-accent dark:bg-green-800 text-green-700 dark:text-green-300 rounded px-1.5 py-0.5">
                                        {{ item.kode }}
                                    </span>
                                    <span class="text-xs text-green-800 dark:text-green-200">{{ item.label }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Body -->
                        <div class="px-6 py-5 space-y-5">
                            <!-- Nomor Akun -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nomor Akun
                                </label>
                                <input
                                    v-model="form.nomor_akun"
                                    type="text"
                                    placeholder="201"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm
                                           bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                           placeholder-gray-400 dark:placeholder-gray-500
                                           focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                />
                                <p
                                    v-if="errors.nomor_akun"
                                    class="mt-1 text-sm text-red-500"
                                >
                                    {{ errors.nomor_akun }}
                                </p>
                            </div>

                            <!-- Nama Akun -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nama Akun
                                </label>
                                <input
                                    v-model="form.nama_akun"
                                    type="text"
                                    placeholder="Tabungan Anggota"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm
                                           bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                           placeholder-gray-400 dark:placeholder-gray-500
                                           focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                />
                                <p
                                    v-if="errors.nama_akun"
                                    class="mt-1 text-sm text-red-500"
                                >
                                    {{ errors.nama_akun }}
                                </p>
                            </div>

                            <!-- Jenis Akun -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Jenis Akun
                                </label>
                                <select
                                    v-model="form.jenis_akun"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2.5 text-sm
                                           bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                           focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                >
                                    <option value="" disabled>Pilih jenis akun</option>
                                    <option
                                        v-for="jenis in props.jenisAkunOptions"
                                        :key="jenis"
                                        :value="jenis"
                                    >
                                        {{ jenis }}
                                    </option>
                                    <p
                                        v-if="errors.jenis_akun"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ errors.jenis_akun }}
                                    </p>
                                </select>
                            </div>
                        </div>

                        <!-- Modal Footer -->
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
        <!-- Modal Ubah Status -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showStatusModal"
                    class="fixed inset-0 z-50 flex items-center justify-center"
                >
                    <div
                        class="absolute inset-0 bg-black/50"
                        @click="showStatusModal = false"
                    />

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4">

                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b">
                            <h3 class="font-semibold text-base uppercase">
                                Ubah Status Akun
                            </h3>

                            <button
                                @click="showStatusModal = false"
                                class="text-gray-400 hover:text-gray-600"
                            >
                                <Icon icon="mdi:close" class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="px-6 py-5 space-y-4">

                            <!-- Nomor Akun -->
                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    Nomor Akun
                                </label>

                                <input
                                    :value="statusForm.nomor_akun"
                                    readonly
                                    class="w-full bg-gray-100 border rounded-lg px-4 py-2.5 text-sm"
                                />
                            </div>

                            <!-- Nama Akun -->
                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    Nama Akun
                                </label>

                                <input
                                    :value="statusForm.nama_akun"
                                    readonly
                                    class="w-full bg-gray-100 border rounded-lg px-4 py-2.5 text-sm"
                                />
                            </div>

                            <!-- Jenis Akun -->
                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    Jenis Akun
                                </label>

                                <input
                                    :value="statusForm.jenis_akun"
                                    readonly
                                    class="w-full bg-gray-100 border rounded-lg px-4 py-2.5 text-sm"
                                />
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    Status
                                </label>

                                <select
                                    v-model="statusForm.status"
                                    class="w-full border rounded-lg px-4 py-2.5 text-sm"
                                >
                                    <option value="Aktif">Aktif</option>
                                    <option value="Non-Aktif">Non-Aktif</option>
                                </select>
                            </div>

                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 flex justify-end gap-2 border-t">
                            <button
                                @click="showStatusModal = false"
                                class="px-4 py-2 border rounded-lg"
                            >
                                Batal
                            </button>

                            <button
                                @click="updateStatus"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg"
                            >
                                Simpan
                            </button>
                        </div>

                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
/* Modal transition */
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