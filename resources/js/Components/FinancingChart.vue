<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const props = defineProps({
    paymentSchedules: {
        type: Array,
        required: true
    }
})

// Hitung status count
const statusCounts = computed(() => {
    const counts = {
        'Dibayar': 0,
        'Terjadwal': 0,
        'Menunggu Konfirmasi': 0,
        'Terlambat': 0,
        'Ditolak': 0,
        'Dibatalkan': 0,
    }

    props.paymentSchedules.forEach(schedule => {
        if (counts.hasOwnProperty(schedule.status)) {
            counts[schedule.status]++
        }
    })

    return counts
})

// Chart data
const chartData = computed(() => ({
    labels: Object.keys(statusCounts.value),
    datasets: [
        {
            data: Object.values(statusCounts.value),
            backgroundColor: [
                '#10b981', // Dibayar - green
                '#3b82f6', // Terjadwal - blue
                '#f59e0b', // Menunggu Konfirmasi - amber
                '#ef4444', // Terlambat - red
                '#dc2626', // Ditolak - dark red
                '#9ca3af', // Dibatalkan - gray
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
                    const percentage = ((context.parsed / total) * 100).toFixed(1)
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
