<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'
import moneyParser from '@/Composables/moneyParser.js'

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
    height: {
        type: [String, Number],
        default: 300,
    }
})

const categories = computed(() => (props.data ? Object.keys(props.data) : []))
const values = computed(() => (props.data ? Object.values(props.data).map(v => Number(v) || 0) : []))

const series = computed(() => [
    {
        name: 'Keuntungan',
        data: values.value,
    },
])

const chartOptions = computed(() => ({
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
        categories: categories.value,
        axisBorder: {
            show: false,
        },
        axisTicks: {
            show: false,
        },
        labels: {
            style: {
                fontSize: '14px',
            },
        },
    },
    legend: {
        show: true,
        position: 'top',
        horizontalAlign: 'left',
        fontFamily: 'Manrope',
        fontSize: '14px',
        markers: {
            radius: 99,
        },
    },
    yaxis: {
        labels: {
            style: {
                fontSize: '14px',
            },
            formatter: function (value) {
                if (value === undefined || value === null) return 'Rp0';
                return moneyParser(value);
            }
        }
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
        style: {
            fontSize: '16px',
        },
        x: {
            show: false,
        },
        y: {
            formatter: function (value) {
                if (value === undefined || value === null) return 'Rp0';
                return moneyParser(value);
            }
        },
    },
}))
</script>

<template>
    <div class="w-full">
        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <div id="chartOne" class="-ml-5 min-w-162.5 xl:min-w-full pl-2">
                <VueApexCharts type="bar" :height="height" :key="filter" :options="chartOptions" :series="series" />
            </div>
        </div>
    </div>
</template>
