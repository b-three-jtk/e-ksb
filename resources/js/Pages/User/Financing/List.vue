<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import BaseLayout from '../../../Layouts/Base.vue'
import BaseFunctionality from '../../../Components/Table/BaseFunctionality.vue'
import Pagination from '../../../Components/Table/Pagination.vue'
import dateParser from '../../../Composables/dateParser.js'
import moneyParser from '../../../Composables/moneyParser.js'

defineOptions({
	layout: (h: any, page: any) => h(BaseLayout, { title: 'List Pembiayaan' }, () => page),
})

type LoanInfo = {
	tenor?: number
	monthly_installment?: number
	remaining_margin?: number
	remaining_principal?: number
	next_due_date?: string
}

type FinancingItem = {
	id: string
	transaction_code?: string
	akad_date?: string
	product_type?: string
	product_name?: string
	status?: string
	remaining_balance?: number
	loan?: LoanInfo | null
}

type FinancingPagination = {
	data: FinancingItem[]
	current_page: number
	per_page: number
	total: number
	last_page: number
	links: Array<{ url: string | null; label: string; active: boolean }>
}

const props = withDefaults(defineProps<{
	financings?: FinancingPagination
	activeFinancing?: FinancingItem | null
	productTypes?: string[]
	filters?: {
		search?: string
		product_type?: string
		per_page?: number
	}
}>(), {
	financings: () => ({
		data: [],
		current_page: 1,
		per_page: 10,
		total: 0,
		last_page: 1,
		links: [],
	}),
	activeFinancing: null,
	productTypes: () => [],
	filters: () => ({
		search: '',
		product_type: '',
		per_page: 10,
	}),
})

const filterState = ref({
	search: props.filters?.search ?? '',
	product_type: props.filters?.product_type ?? '',
	per_page: props.filters?.per_page ?? 10,
})

const productTypeOptions = computed(() => {
	return props.productTypes.map((productType) => ({
		key: productType,
		label: productType,
	}))
})

const selectFilters = computed(() => ([
	{
		key: 'product_type',
		label: 'Semua Kategori Produk',
		options: productTypeOptions.value,
		optionLabel: 'label',
		optionValue: 'key',
	},
]))

const currentFinancing = computed(() => {
	const status = String(props.activeFinancing?.status || '').trim().toLowerCase()

	if (status === 'angsuran berjalan') {
		return props.activeFinancing
	}

	return null
})

const currentTenor = computed(() => {
	const tenor = Number(currentFinancing.value?.loan?.tenor ?? 0)
	return tenor > 0 ? `${tenor} bulan` : '-'
})

const currentInstallment = computed(() => {
	return moneyParser(currentFinancing.value?.loan?.monthly_installment ?? 0)
})

const currentRemaining = computed(() => {
	const remaining = Number(currentFinancing.value?.remaining_balance ?? 0)
	if (remaining > 0) return moneyParser(remaining)

	const remainingFromLoan =
		Number(currentFinancing.value?.loan?.remaining_margin ?? 0) +
		Number(currentFinancing.value?.loan?.remaining_principal ?? 0)

	return moneyParser(remainingFromLoan)
})

const applyFilters = () => {
	router.get('/user/financing', {
		search: filterState.value.search || undefined,
		product_type: filterState.value.product_type || undefined,
		per_page: filterState.value.per_page,
	}, {
		preserveScroll: true,
		replace: true,
	})
}

const handleSearch = (value: string) => {
	filterState.value.search = value
}

const getStatusClass = (status?: string) => {
	const base = 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold'

	switch (status) {
		case 'Lunas':
			return `${base} bg-green-100 text-green-700`
		case 'Angsuran Berjalan':
			return `${base} bg-blue-100 text-blue-700`
		case 'Barang Diterima':
			return `${base} bg-emerald-100 text-emerald-700`
		case 'Ditolak':
			return `${base} bg-red-100 text-red-700`
		case 'Belum Ditinjau':
			return `${base} bg-gray-100 text-gray-700`
		default:
			return `${base} bg-amber-100 text-amber-700`
	}
}
</script>

<template>
	<div class="min-h-screen bg-gray-50 dark:bg-gray-900 pt-24 pb-12 max-w-8xl px-4 sm:px-6 lg:px-8">
		<div class="space-y-6 p-6">
			<div class="mb-6 w-full">
				<h1 class="text-3xl font-bold font-head text-green-800 dark:text-green-500 mb-2">
					Pembiayaan Murabahah
				</h1>
			</div>

			<div
				v-if="currentFinancing"
				class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 overflow-hidden"
			>
				<div class="px-6 py-5 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
					<div class="space-y-2">
						<div class="flex flex-wrap items-center gap-3">
							<span :class="getStatusClass(currentFinancing.status)">
								{{ currentFinancing.status || 'Status tidak tersedia' }}
							</span>
							<h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
								{{ currentFinancing.transaction_code || '-' }} - {{ currentFinancing.product_type || currentFinancing.product_name || '-' }}
							</h2>
						</div>
						<p class="text-sm text-gray-500 dark:text-gray-400">
							Tanggal Akad: {{ dateParser(currentFinancing.akad_date) }}
							<span class="mx-2">•</span>
							Sisa tenor: {{ currentTenor }}
						</p>
					</div>

					<Link
						:href="`/user/financing/show/${currentFinancing.id}`"
						class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors"
					>
						<Icon icon="mdi:eye-outline" class="w-4 h-4" />
						Lihat Detail
					</Link>
				</div>

				<div class="border-t border-gray-100 dark:border-gray-700 px-6 py-4 grid grid-cols-1 md:grid-cols-3 gap-4">
					<div>
						<div class="text-xs text-gray-500 dark:text-gray-400">Angsuran/bulan</div>
						<div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ currentInstallment }}</div>
					</div>
					<div>
						<div class="text-xs text-gray-500 dark:text-gray-400">Jatuh Tempo</div>
						<div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ dateParser(currentFinancing.loan?.next_due_date) }}</div>
					</div>
					<div>
						<div class="text-xs text-gray-500 dark:text-gray-400">Sisa angsuran</div>
						<div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ currentRemaining }}</div>
					</div>
				</div>
			</div>

			<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden w-full">
				<div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
					<h2 class="text-xl font-head font-semibold text-gray-900 dark:text-gray-100">
						Riwayat Pembiayaan
					</h2>
				</div>

				<BaseFunctionality
					:search="filterState.search"
					:per-page="filterState.per_page"
					:filters="{ product_type: filterState.product_type }"
					:selects="selectFilters"
					:search-tooltip="['No Transaksi', 'Tanggal Akad']"
					:show-search-button="true"
					:show-border="true"
					@update:search="handleSearch"
					@submit:search="applyFilters"
					@update:perPage="val => { filterState.per_page = val; applyFilters() }"
					@update:filters="val => { filterState.product_type = val.product_type; applyFilters() }"
				/>

				<div class="px-6 py-6 overflow-x-auto">
					<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
						<thead class="bg-gray-50 dark:bg-gray-700">
							<tr>
								<th class="px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-200">No</th>
								<th class="px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-200">No Transaksi</th>
								<th class="px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-200">Tanggal Akad</th>
								<th class="px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-200">Kategori Produk</th>
								<th class="px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-200">Status</th>
								<th class="px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-200">Aksi</th>
							</tr>
						</thead>

						<tbody class="divide-y divide-gray-100 dark:divide-gray-700">
							<tr v-if="!financings.data.length">
								<td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
									Belum ada data pembiayaan.
								</td>
							</tr>

							<tr
								v-for="(item, index) in financings.data"
								:key="item.id"
								class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
							>
								<td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-200">
									{{ ((financings.current_page - 1) * financings.per_page) + index + 1 }}
								</td>
								<td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-gray-100 font-medium">
									{{ item.transaction_code || '-' }}
								</td>
								<td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-200">
									{{ dateParser(item.akad_date) }}
								</td>
								<td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-200">
									{{ item.product_type || item.product_name || '-' }}
								</td>
								<td class="px-4 py-3 text-center text-sm">
									<span :class="getStatusClass(item.status)">
										{{ item.status || '-' }}
									</span>
								</td>
								<td class="px-4 py-3 text-center">
									<Link
										:href="`/user/financing/show/${item.id}`"
										class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700 transition-colors"
									>
										<Icon icon="mdi:eye-outline" class="w-4 h-4" />
										Lihat Detail
									</Link>
								</td>
							</tr>
						</tbody>
					</table>

					<Pagination :links="financings.links" :total="financings.total" />
				</div>
			</div>
		</div>
	</div>
</template>
