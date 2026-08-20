<template>
  <div class="app-chart">
    <div class="app-chart-header">
      <h4>{{ title }}</h4>
      <slot name="actions" />
    </div>
    <p v-if="subtitle" class="app-chart-sub">{{ subtitle }}</p>
    <div v-if="!chartData" class="app-chart-empty">{{ emptyText }}</div>
    <div v-else class="app-chart-wrap" :class="{ 'is-pie': type === 'doughnut' }">
      <Bar v-if="type === 'bar'" :data="chartData" :options="resolvedOptions" />
      <Doughnut v-else-if="type === 'doughnut'" :data="chartData" :options="resolvedOptions" />
      <Line v-else-if="type === 'line'" :data="chartData" :options="resolvedOptions" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Bar, Doughnut, Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarController,
  BarElement,
  DoughnutController,
  ArcElement,
  LineController,
  PointElement,
  LineElement,
  Filler,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'
import { chartOptionsBar, chartOptionsDoughnut, chartOptionsLine } from '@/composables/useChart'

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarController,
  BarElement,
  DoughnutController,
  ArcElement,
  LineController,
  PointElement,
  LineElement,
  Filler,
  Title,
  Tooltip,
  Legend
)

const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  type: { type: String, default: 'bar' },
  chartData: { type: Object, default: null },
  options: { type: Object, default: null },
  emptyText: { type: String, default: 'Belum ada data untuk ditampilkan.' },
})

const resolvedOptions = computed(() => {
  if (props.options) return props.options
  if (props.type === 'doughnut') return chartOptionsDoughnut
  if (props.type === 'line') return chartOptionsLine
  return chartOptionsBar
})
</script>

<style scoped>
.app-chart {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1rem 1.1rem;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
  min-width: 0;
}
.app-chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.35rem;
}
.app-chart-header h4 {
  margin: 0;
  font-size: 0.92rem;
  font-weight: 650;
  color: #334155;
}
.app-chart-sub {
  margin: 0 0 0.65rem;
  font-size: 0.78rem;
  color: #94a3b8;
}
.app-chart-wrap {
  height: 220px;
  position: relative;
}
.app-chart-wrap.is-pie {
  height: 240px;
}
.app-chart-empty {
  min-height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #94a3b8;
  font-size: 0.85rem;
  background: #f8fafc;
  border-radius: 10px;
}
</style>
