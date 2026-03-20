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
  <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-lg hover:shadow-xl transition-shadow duration-300">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-base font-semibold text-slate-900 tracking-tight">Absence Trends</h3>
        <p class="text-xs text-slate-400 font-medium mt-0.5">Data from backend reports endpoint</p>
      </div>
      <div class="flex items-center gap-2">
        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 rounded-full">
          <span class="size-2.5 bg-[#135bec] rounded-full ring-4 ring-[#135bec]/10"></span>
          <span class="text-xs font-medium text-slate-600">Absences</span>
        </div>
        <button class="p-1.5 text-slate-300 hover:text-slate-500 hover:bg-slate-50 rounded-lg transition-colors">
          <MoreVertical class="size-4" />
        </button>
      </div>
    </div>

    <div class="h-64 w-full relative">
      <div class="absolute inset-0">
        <Bar :data="chartData" :options="chartOptions" />
      </div>
    </div>
  </div>
</template>