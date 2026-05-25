<script setup>
import { ref, watch } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
    title: {
        type: String,
    },
    data: {
        type: Object,
    },
    filter: {
        type: String,
    },
})

const series = ref([
    {
        name: 'Permohonan',
        data: [],
    },
])

const chartOptions = ref({
    colors: ['#50B2D5'],
    chart: {
        fontFamily: 'Manrope, sans-serif',
        type: 'bar',
        toolbar: {
            show: false,
        },
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '39%',
            borderRadius: 5,
            borderRadiusApplication: 'end',
        },
    },
    dataLabels: {
        enabled: false,
    },
    stroke: {
        show: true,
        width: 4,
        colors: ['transparent'],
    },
    xaxis: {
        categories: [],
        axisBorder: {
            show: false,
        },
        axisTicks: {
            show: false,
        },
    },
    legend: {
        show: true,
        position: 'top',
        horizontalAlign: 'left',
        fontFamily: 'Outfit',
        markers: {
            radius: 99,
        },
    },
    yaxis: {
        title: false,
    },
    grid: {
        yaxis: {
            lines: {
                show: true,
            },
        },
    },
    fill: {
        opacity: 1,
    },
    tooltip: {
        x: {
            show: false,
        },
        y: {
            formatter: function (val) {
                return val.toString()
            },
        },
    },
})

const updateChart = () => {
    if (!props.data || Object.keys(props.data).length === 0) return
    const categories = Object.keys(props.data)
    const values = Object.values(props.data)

    chartOptions.value = {
        ...chartOptions.value,
        xaxis: { ...chartOptions.value.xaxis, categories }
    }
    series.value = [{ name: 'Permohonan', data: [...values] }]
}

watch(() => props.filter, updateChart, { immediate: true })
watch(() => props.data, updateChart, { deep: true })
</script>

<template>
    <div class="w-full card-layout flex flex-col gap-10">
        <h1 class="card-title">{{ title }}</h1>
        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <div id="chartOne" class="-ml-5 min-w-162.5 xl:min-w-full pl-2">
                <VueApexCharts type="bar" height="300" :key="filter" :options="chartOptions" :series="series" />
            </div>
        </div>
    </div>
</template>
