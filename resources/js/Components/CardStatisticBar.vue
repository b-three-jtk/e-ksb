<script setup>
import { defineProps, ref } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
    title: {
        type: String,
    },
    data: {
        type: Array,
    },
})

const series = ref([
    {
        name: 'Permohonan',
        data: props.data,
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
        categories: [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec',
        ],
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
</script>

<template>
    <div
        class="w-full card-layout flex flex-col gap-10">
        <h1 class="card-title">{{ title }}</h1>
        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <div id="chartOne" class="-ml-5 min-w-[650px] xl:min-w-full pl-2">
                <VueApexCharts type="bar" height="300" :options="chartOptions" :series="series" />
            </div>
        </div>
    </div>
</template>
