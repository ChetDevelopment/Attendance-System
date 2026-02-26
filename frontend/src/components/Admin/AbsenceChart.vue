<script setup lang="ts">
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
} from "chart.js";
import { Bar } from "vue-chartjs";
import { MoreVertical } from "lucide-vue-next";

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
);

const rawData = [
  { day: "30d", absences: 12 },
  { day: "28d", absences: 8 },
  { day: "26d", absences: 15 },
  { day: "24d", absences: 22 },
  { day: "22d", absences: 7 },
  { day: "20d", absences: 10 },
  { day: "18d", absences: 4 },
  { day: "16d", absences: 14 },
  { day: "14d", absences: 18 },
  { day: "12d", absences: 12 },
  { day: "10d", absences: 9 },
  { day: "8d", absences: 24 },
  { day: "6d", absences: 13 },
  { day: "4d", absences: 16 },
  { day: "2d", absences: 5 },
  { day: "1d", absences: 8 },
  { day: "Today", absences: 3 },
];

const chartData = {
  labels: rawData.map((d) => d.day),
  datasets: [
    {
      label: "Absences",
      data: rawData.map((d) => d.absences),
      backgroundColor: rawData.map((entry) =>
        entry.day === "Today" ? "#135bec" : "#135bec33"
      ),
      borderColor: rawData.map((entry) =>
        entry.day === "Today" ? "#135bec" : "transparent"
      ),
      borderWidth: rawData.map((entry) => (entry.day === "Today" ? 2 : 0)),
      borderRadius: 2,
    },
  ],
};

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      enabled: true,
      backgroundColor: "#0f172a", // slate-900
      titleColor: "white",
      bodyColor: "white",
      bodyFont: {
        weight: "bold",
        size: 10,
      },
      displayColors: false,
      padding: {
        x: 8,
        y: 4,
      },
      callbacks: {
        title: () => "", // Hides the title
        label: (tooltipItem: any) => {
          return tooltipItem.raw;
        },
      },
    },
  },
  scales: {
    x: {
      display: false,
      grid: {
        display: false,
      },
    },
    y: {
      display: false,
      grid: {
        display: false,
      },
    },
  },
};
</script>

<template>
  <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
    <div class="flex items-center justify-between mb-10">
      <div>
        <h3 class="text-lg font-bold text-slate-900">Monthly Absence Trends</h3>
        <p class="text-sm text-slate-500">
          Total absences tracked over the last 30 days
        </p>
      </div>
      <div class="flex items-center gap-2">
        <div
          class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 rounded-lg text-[10px] font-bold"
        >
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

    <div
      class="mt-4 pt-4 border-t border-slate-100 flex justify-between text-[10px] text-slate-400 font-bold uppercase tracking-widest px-2"
    >
      <span>30 Days Ago</span>
      <span>15 Days Ago</span>
      <span>Today</span>
    </div>
  </div>
</template>
