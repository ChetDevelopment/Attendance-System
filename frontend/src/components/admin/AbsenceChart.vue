<script setup lang="ts">
import { computed } from 'vue';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps<{
  data: Array<{ name: string; value: number }>;
}>();

const totalAbsences = computed(() =>
  props.data.reduce((total, entry) => total + Number(entry?.value || 0), 0),
);

const peakDay = computed(() =>
  props.data.reduce(
    (peak, entry) => (Number(entry?.value || 0) > Number(peak?.value || 0) ? entry : peak),
    props.data[0] ?? null,
  ),
);

const chartData = computed(() => ({
  labels: props.data.map((entry) => entry.name),
  datasets: [
    {
      label: 'Absences',
      data: props.data.map((entry) => entry.value),
      backgroundColor: props.data.map((entry, index) =>
        index === props.data.length - 1 ? '#135bec' : 'rgba(19, 91, 236, 0.18)',
      ),
      borderColor: '#135bec',
      borderRadius: 10,
      borderSkipped: false,
      maxBarThickness: 32,
    },
  ],
}));

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0f172a',
      titleFont: { weight: '700' as const },
      bodyFont: { weight: '600' as const },
      padding: 12,
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: {
        color: '#64748b',
        font: {
          size: 11,
          weight: '600' as const,
        },
      },
    },
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(148, 163, 184, 0.14)',
        drawBorder: false,
      },
      ticks: {
        color: '#94a3b8',
        font: {
          size: 10,
          weight: '600' as const,
        },
      },
    },
  },
}));
</script>

<template>
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
      <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-primary">Trend Monitor</p>
        <h3 class="mt-2 text-xl font-black tracking-tight text-slate-900">Absence Trends</h3>
        <p class="mt-1 text-sm text-slate-500">
          Daily absence movement from the reporting endpoint for the selected period.
        </p>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Total Absences</p>
          <p class="mt-2 text-lg font-black text-slate-900">{{ totalAbsences }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Peak Day</p>
          <p class="mt-2 text-lg font-black text-slate-900">
            {{ peakDay?.name || 'No data' }}
          </p>
        </div>
      </div>
    </div>

    <div class="mt-6 h-72 w-full">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>
