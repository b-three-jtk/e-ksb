<script setup>
import { computed } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
    totalPrice:    { type: Number, default: 0 },
    totalPaid:     { type: Number, default: 0 },
    remainingBalance: { type: Number, default: 0 },
})

const percentage = computed(() => {
    if (!props.totalPrice) return 0
    if (props.remainingBalance <= 0) return 100
    return Math.min(Math.round((props.totalPaid / props.totalPrice) * 100), 100)
})

const series = computed(() => [props.totalPaid, props.remainingBalance])

const options = computed(() => ({
    chart: {
        type: 'donut',
        animations: { enabled: true, speed: 600 },
    },
    labels: ['Total Dibayar', 'Sisa Tagihan'],
    colors: ['#007031', '#d1d5db'],
    stroke: { width: 0 },
    dataLabels: { enabled: false },
    legend: {
        position: 'bottom',
        fontFamily: 'inherit',
        fontSize: '13px',
        fontWeight: 500,
        labels: { colors: '#fff' },
        markers: { width: 10, height: 10, radius: 3 },
        itemMargin: { horizontal: 12 },
    },
    plotOptions: {
        pie: {
            donut: {
                size: '72%',
                labels: {
                    show: true,
                    value: { show: true, fontSize: '24px', fontFamily: 'inherit', fontWeight: 600, color: '#111827'},
                    total: {
                        show: true,
                        showAlways: true,
                        label: 'Terbayar',
                        fontSize: '14px',
                        fontFamily: 'inherit',
                        fontWeight: 500,
                        color: '#6b7280',
                        formatter: () => `${percentage.value} %`,
                    },
                },
            },
        },
    },
    tooltip: {
        theme: 'light',
        style: { fontSize: '14px', fontFamily: 'inherit' },
        y: {
            formatter: (val) =>
                new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val),
        },
    },
}))
</script>

<template>
    <div class="flex items-center justify-center">
        <div class="w-72 financing-chart">
            <VueApexCharts type="donut" :series="series" :options="options" />
        </div>
    </div>
</template>

<style scoped>
:deep(.apexcharts-tooltip-series-group) {
    background-color: #ffffff !important;
}

:deep(.apexcharts-tooltip-text) {
    color: #1f2937 !important;
}
</style>
