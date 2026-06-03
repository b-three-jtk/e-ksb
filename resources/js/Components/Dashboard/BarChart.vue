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
        name: 'Simpanan',
        data: [52,21,10,10,5],
    },
])

const chartOptions = ref({
    colors: ['#C3DC6D'],
    chart: {
        fontFamily: 'Manrope, sans-serif',
        type: 'bar',
        toolbar: {
            show: false,
        },
    },
    plotOptions: {
        bar: {
            horizontal: true,
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
        categories: ['Simpanan Pokok', 'Simpanan Wajib', 'Tabungan Anggota', 'Tabungan Berjangka', 'Tabungan Ibadah'],
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
        fontFamily: 'Manrope',
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
    series.value = [{ name: 'Keuntungan', data: [...values] }]
}

watch(() => props.filter, updateChart, { immediate: true })
watch(() => props.data, updateChart, { deep: true })
</script>

<template>
    <div class="w-xl">
        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <div id="chartOne" class="-ml-5 min-w-162.5 xl:min-w-full pl-2">
                <VueApexCharts type="bar" height="300" :key="filter" :options="chartOptions" :series="series" />
            </div>
        </div>
    </div>
</template>
