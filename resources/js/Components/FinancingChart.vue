<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
    data: {
        type: Array,
        default: () => []
    },
    totalPrice: {
        type: Number,
        default: 0
    },
    totalPaid: {
        type: Number,
        default: 0
    }
})

// Hitung status count
const statusCounts = computed(() => {
    const remaining = Math.max((props.totalPrice || 0) - (props.totalPaid || 0), 0)

    return {
        'Total Dibayar': props.totalPaid || 0,
        'Sisa Tagihan': remaining,
    }
})

// Chart data
const chartData = computed(() => ({
    labels: Object.keys(statusCounts.value),
    datasets: [
        {
            data: Object.values(statusCounts.value),
            backgroundColor: [
                '#10b981', // Dibayar - green
                '#9ca3af', // Sisa - gray
            ],
            borderColor: '#ffffff',
            borderWidth: 2,
        }
    ]
}))

const chartOptions = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                padding: 15,
                font: {
                    size: 12,
                    weight: '500',
                },
                color: '#374151',
            },
        },
        tooltip: {
            callbacks: {
                label: function (context) {
                    const total = context.dataset.data.reduce((a, b) => a + b, 0)
                    const percentage = total === 0 ? 0 : ((context.parsed / total) * 100).toFixed(1)
                    return `${context.label}: ${context.parsed} (${percentage}%)`
                }
            }
        }
    },
}
</script>

<template>
    <div class="flex items-center justify-center">
        <div class="w-80 h-80">
            <Doughnut :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
