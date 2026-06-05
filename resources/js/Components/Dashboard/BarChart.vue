<script setup>
import { ref, watch, computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import parseCurrencyAmount from '@/Composables/moneyParser.js';

const props = defineProps({
    title: String,
    data: Object,
    filter: String,
})

const series = ref([
    {
        name: 'Simpanan',
        data: [],
    },
])

const chartOptions = ref({
    colors: ['#044B27', '#097939', '#0D9F4A', '#72A36B', '#C3DC6D'],
    chart: {
        fontFamily: 'Manrope, sans-serif',
        type: 'bar',
        toolbar: { show: false },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            columnWidth: '39%',
            borderRadius: 5,
            borderRadiusApplication: 'end',
            distributed: true,
        },
    },
    dataLabels: { enabled: false },
    stroke: { show: true, width: 4, colors: ['transparent'] },
    xaxis: {
        categories: [],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            formatter: function (val) {
                if (val >= 1000000) return (val / 1000000).toFixed(0) + ' Jt';
                return val;
            }
        }
    },
    legend: {
        show: false,
    },
    yaxis: { title: false },
    grid: { yaxis: { lines: { show: true } } },
    fill: { opacity: 1 },
    tooltip: {
        y: {
            formatter: function (val) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(val)
            },
        },
    },
})

const totalSimpanan = computed(() => {
    if (!props.data) return 0;
    return Object.values(props.data).reduce((a, b) => a + b, 0);
});

const updateChart = () => {
    if (!props.data) return

    const sourceData = props.data;
    const categories = Object.keys(sourceData);
    const values = Object.values(sourceData);

    chartOptions.value = {
        ...chartOptions.value,
        xaxis: { ...chartOptions.value.xaxis, categories }
    }
    series.value = [{ name: 'Simpanan', data: values }]
}

watch(() => props.filter, updateChart, { immediate: true })
watch(() => props.data, updateChart, { deep: true })
</script>


<template>
    <div class="flex gap-2">
        <div class="w-xl">
            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <div id="chartOne" class="-ml-5 min-w-162.5 xl:min-w-full pl-2">
                    <VueApexCharts type="bar" height="300" :key="filter" :options="chartOptions" :series="series" />
                </div>
            </div>
        </div>
        <ul class="flex flex-col gap-3 mt-2">
            <li class="text-gray-400 text-sm font-semibold">JUMLAH</li>

            <li v-for="(value, name) in data" :key="name" class="bg-gray-100 text-sm px-3 py-1.5 font-body rounded-lg text-gray-700 whitespace-nowrap">
                {{ parseCurrencyAmount(value) }}

                <span class="text-gray-500 font-medium ml-1">
                    ({{ totalSimpanan > 0 ? ((value / totalSimpanan) * 100).toFixed(1).replace('.', ',') : 0 }}%)
                </span>
            </li>

        </ul>
    </div>
</template>
