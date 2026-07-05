<script setup>
import { computed } from "vue"
import { Icon } from "@iconify/vue"
import { usePage } from "@inertiajs/vue3"
import BaseTable from "@/Components/Table/BaseTable.vue"

const props = defineProps({
    report: Object,
})

const page = usePage()

const exportUrl = computed(() => {
    const params = new URLSearchParams()

    const filters = page.props.filters ?? {}

    if (filters.periode) {
        params.append("periode", filters.periode)
    }

    if (filters.date_from) {
        params.append("date_from", filters.date_from)
    }

    if (filters.date_to) {
        params.append("date_to", filters.date_to)
    }

    return `/admin/kas/export/cashflow?${params.toString()}`
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value ?? 0)
}

const columns = [
    {
        key: "description",
        label: "Keterangan",
    },
    {
        key: "amount",
        label: "Nominal",
        align: "right",
    },
]

const rows = computed(() => {

    if (!props.report) return []

    const data = []

    // OPERASI
    data.push({
        id: "op-title",
        description: "ARUS KAS DARI AKTIVITAS OPERASI",
        amount: null,
        type: "title",
    })

    props.report.operating.items.forEach((item, i) => {
        data.push({
            id: `op-${i}`,
            ...item,
            type: "item",
        })
    })

    data.push({
        id: "op-total",
        description: "Kas Bersih Aktivitas Operasi",
        amount: props.report.operating.net,
        type: "total",
    })

    // INVESTASI
    data.push({
        id: "inv-title",
        description: "ARUS KAS DARI AKTIVITAS INVESTASI",
        amount: null,
        type: "title",
    })

    if (props.report.investing.items.length) {

        props.report.investing.items.forEach((item, i) => {
            data.push({
                id: `inv-${i}`,
                ...item,
                type: "item",
            })
        })

    } else {

        data.push({
            id: "inv-empty",
            description: "Tidak ada transaksi investasi",
            amount: 0,
            type: "item",
        })

    }

    data.push({
        id: "inv-total",
        description: "Kas Bersih Aktivitas Investasi",
        amount: props.report.investing.net,
        type: "total",
    })

    // PENDANAAN
    data.push({
        id: "fin-title",
        description: "ARUS KAS DARI AKTIVITAS PENDANAAN",
        amount: null,
        type: "title",
    })

    props.report.financing.items.forEach((item, i) => {
        data.push({
            id: `fin-${i}`,
            ...item,
            type: "item",
        })
    })

    data.push({
        id: "fin-total",
        description: "Kas Bersih Aktivitas Pendanaan",
        amount: props.report.financing.net,
        type: "total",
    })

    // RINGKASAN

    data.push({
        id: "space",
        description: "",
        amount: null,
        type: "space",
    })

    data.push({
        id: "net",
        description: "Kenaikan (Penurunan) Kas Bersih",
        amount: props.report.net_cash,
        type: "summary",
    })

    data.push({
        id: "opening",
        description: "Kas Awal Periode",
        amount: props.report.opening_balance,
        type: "summary",
    })

    data.push({
        id: "closing",
        description: "Kas Akhir Periode",
        amount: props.report.closing_balance,
        type: "grand-total",
    })

    return data

})
</script>

<template>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow overflow-hidden">

        <div
            class="px-6 py-5 border-b flex items-center justify-between"
        >

            <div>
                <h2 class="font-head text-lg font-semibold">
                    Laporan Arus Kas
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Disusun menggunakan metode langsung sesuai PSAK 2
                </p>
            </div>

            <a
                :href="exportUrl"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg
                    bg-green-600 hover:bg-green-700 text-white text-sm transition"
            >
                <Icon
                    icon="mdi:file-excel"
                    class="w-5 h-5"
                />

                Export Excel
            </a>

        </div>

        <BaseTable
            :columns="columns"
            :data="rows"
        >

            <template #cell-description="{ row }">

                <div
                    v-if="row.type=='title'"
                    class="font-bold uppercase text-primary py-2"
                >
                    {{ row.description }}
                </div>

                <div
                    v-else-if="row.type=='space'"
                    class="h-4"
                ></div>

                <div
                    v-else-if="row.type=='grand-total'"
                    class="font-bold text-lg"
                >
                    {{ row.description }}
                </div>

                <div
                    v-else-if="row.type=='total'"
                    class="font-semibold"
                >
                    {{ row.description }}
                </div>

                <div
                    v-else
                    class="pl-5"
                >
                    {{ row.description }}
                </div>

            </template>

            <template #cell-amount="{ row }">

                <div
                    v-if="row.type=='title' || row.type=='space'"
                >
                </div>

                <div
                    v-else-if="row.type=='grand-total'"
                    class="font-bold text-lg"
                >
                    {{ formatCurrency(row.amount) }}
                </div>

                <div
                    v-else-if="row.type=='total'"
                    class="font-semibold"
                >
                    {{ formatCurrency(row.amount) }}
                </div>

                <div
                    v-else
                >
                    {{ formatCurrency(row.amount) }}
                </div>

            </template>

        </BaseTable>

    </div>

</template>