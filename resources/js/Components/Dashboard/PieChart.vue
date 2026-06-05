<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed } from 'vue'
import { Chart, ArcElement, Tooltip, PieController } from 'chart.js'

Chart.register(PieController, ArcElement, Tooltip)

const props = defineProps({
    title: String,
    data: Object,
    filter: String,
})

const canvasRef = ref(null)
let chartInstance = null

const COLORS = ['#008E43', '#F4BE37', '#CE4F41']
const LABELS = ['Lancar', 'Kurang Lancar', 'Macet']

const legendItems = computed(() => {
    if (!props.data) return []
    return LABELS.map((label, i) => ({
        label,
        value: props.data[label] ?? 0,
        color: COLORS[i],
    }))
})

const total = computed(() =>
    legendItems.value.reduce((sum, item) => sum + item.value, 0)
)

const buildChart = () => {
    if (!canvasRef.value || !props.data) return

    const values = LABELS.map((l) => props.data[l] ?? 0)

    const isEmpty = values.every((v) => v === 0)
    const chartData = isEmpty ? [1] : values.filter((v) => v > 0)
    const chartColors = isEmpty
        ? ['#E0E0E0']
        : COLORS.filter((_, i) => (props.data[LABELS[i]] ?? 0) > 0)
    const chartLabels = isEmpty
        ? ['Tidak ada data']
        : LABELS.filter((l) => (props.data[l] ?? 0) > 0)

    if (chartInstance) chartInstance.destroy()

    chartInstance = new Chart(canvasRef.value, {
        type: 'pie',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartData,
                backgroundColor: chartColors,
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            animation: { duration: 400 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: !isEmpty,
                    callbacks: {
                        label: (ctx) => {
                            const val = ctx.parsed
                            const pct = total.value > 0
                                ? Math.round((val / total.value) * 100)
                                : 0
                            return ` ${val} Pembiayaan (${pct}%)`
                        },
                    },
                },
            },
        },
        plugins: [{
            id: 'sliceLabels',
            afterDatasetsDraw(chart) {
                if (isEmpty) return
                const { ctx, data } = chart
                const meta = chart.getDatasetMeta(0)
                meta.data.forEach((arc, i) => {
                    const { startAngle, endAngle, outerRadius, x, y } = arc
                    const mid = (startAngle + endAngle) / 2
                    const r = outerRadius * 0.65
                    const lx = x + r * Math.cos(mid)
                    const ly = y + r * Math.sin(mid)
                    const val = data.datasets[0].data[i]
                    const pct = total.value > 0
                        ? Math.round((val / total.value) * 100)
                        : 0

                    ctx.save()
                    ctx.fillStyle = '#ffffff'
                    ctx.font = '600 13px Manrope, sans-serif'
                    ctx.textAlign = 'center'
                    ctx.textBaseline = 'middle'
                    ctx.fillText(pct + '%', lx, ly)
                    ctx.restore()
                })
            },
        }],
    })
}

watch(() => [props.data, props.filter], buildChart, { deep: true })
onMounted(buildChart)
onBeforeUnmount(() => chartInstance?.destroy())
</script>

<template>
    <div class="w-full">
        <h3 class="text-lg font-semibold mb-4">{{ title }}</h3>

        <div class="flex items-center gap-8">
            <div style="width: 300px; height: 300px; flex-shrink: 0;">
                <canvas ref="canvasRef" />
            </div>

            <div class="flex flex-col gap-3">
                <div
                    v-for="item in legendItems"
                    :key="item.label"
                    class="flex items-start gap-2"
                >
                    <span
                        class="w-3 h-3 rounded-full mt-1 shrink-0"
                        :style="{ backgroundColor: item.color }"
                    />
                    <div>
                        <p class="text-sm font-medium leading-tight">{{ item.label }}</p>
                        <p class="text-xs text-gray-500">
                            {{ item.value }} Pembiayaan
                            ({{ total > 0 ? Math.round((item.value / total) * 100) : 0 }}%)
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
