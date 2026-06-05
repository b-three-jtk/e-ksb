<script setup>
import { ref, watch, computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import parseCurrencyAmount from '@/Composables/moneyParser.js'

const props = defineProps({
    title: String,
    data: Object,
    filter: String,
})

const series = ref([{ name: 'Simpanan', data: [] }])

const chartOptions = computed(() => ({
    colors: ['#044B27', '#097939', '#0D9F4A', '#72A36B', '#C3DC6D'],
    chart: {
        fontFamily: 'Manrope, sans-serif',
        type: 'bar',
        toolbar: { show: false },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 5,
            borderRadiusApplication: 'end',
            distributed: true,
        },
    },
    dataLabels: { enabled: false },
    stroke: { show: false },
    xaxis: {
        categories: props.data ? Object.keys(props.data) : [],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            formatter: (val) => {
                if (val >= 1000000000) return (val / 1000000000).toFixed(1) + ' M'
                if (val >= 1000000) return (val / 1000000).toFixed(0) + ' Jt'
                return val
            },
        },
    },
    yaxis: { show: true },
    legend: { show: false },
    grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
    fill: { opacity: 1 },
    tooltip: {
        y: {
            formatter: (val) => 'Rp ' + new Intl.NumberFormat('id-ID').format(val),
        },
    },
}))

const totalSimpanan = computed(() => {
    if (!props.data) return 0
    return Object.values(props.data).reduce((a, b) => a + b, 0)
})

const updateChart = () => {
    if (!props.data) return
    series.value = [{ name: 'Simpanan', data: Object.values(props.data) }]
}

watch(() => props.filter, updateChart, { immediate: true })
watch(() => props.data, updateChart, { deep: true })
</script>

<template>
    <div class="flex gap-4">
        <div class="flex-1 min-w-0">
            <VueApexCharts
                type="bar"
                height="300"
                :key="filter"
                :options="chartOptions"
                :series="series"
            />
        </div>

        <ul class="flex flex-col gap-2 mt-2 shrink-0">
            <li class="text-gray-400 text-xs font-semibold uppercase tracking-wide px-3">Jumlah</li>

            <li
                v-for="(value, name) in data"
                :key="name"
                class="bg-gray-100 text-sm px-3 py-1.5 rounded-lg text-gray-700 whitespace-nowrap"
            >
                {{ parseCurrencyAmount(value) }}
                <span class="text-gray-500 font-medium ml-1">
                    ({{ totalSimpanan > 0 ? ((value / totalSimpanan) * 100).toFixed(1).replace('.', ',') : '0' }}%)
                </span>
            </li>
        </ul>
    </div>
</template>
