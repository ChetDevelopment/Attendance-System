<script setup lang="ts">
import { computed } from 'vue'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
} from 'chart.js'
import { Bar } from 'vue-chartjs'
ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)
import { MoreVertical } from 'lucide-vue-next'

const props = defineProps<{
  data: Array<{ name: string; value: number }>
}>()

const chartData = computed(() => ({
  labels: props.data.map((d) => d.name),
  datasets: [
    {
      label: 'Absences',
      data: props.data.map((d) => d.value),
      backgroundColor: props.data.map((entry, idx) => (idx === props.data.length - 1 ? '#135bec' : '#135bec33')),
      borderColor: props.data.map((entry, idx) => (idx === props.data.length - 1 ? '#135bec' : 'transparent')),
      borderWidth: props.data.map((entry, idx) => (idx === props.data.length - 1 ? 2 : 0)),
      borderRadius: 2,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    x: { display: false, grid: { display: false } },
    y: { display: false, grid: { display: false } },
  },
}
</script>

<template>
  <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
    <div class="flex items-center justify-between mb-10">
      <div>
        <h3 class="text-lg font-bold text-slate-900">Absence Trends</h3>
        <p class="text-sm text-slate-500">Data from backend reports endpoint</p>
      </div>
      <div class="flex items-center gap-2">
        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 rounded-lg text-[10px] font-bold">
          <span class="size-2 bg-primary rounded-full"></span> Absences
        </div>
        <button class="p-2 text-slate-400 hover:text-slate-600">
          <MoreVertical class="size-5" />
        </button>
      </div>
    </div>

    <div class="h-64 w-full">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>
