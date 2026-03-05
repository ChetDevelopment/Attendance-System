<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import * as d3 from 'd3';
import { MoreVertical } from 'lucide-vue-next';

const data = [
  { day: '30d', absences: 12 },
  { day: '28d', absences: 8 },
  { day: '26d', absences: 15 },
  { day: '24d', absences: 22 },
  { day: '22d', absences: 7 },
  { day: '20d', absences: 10 },
  { day: '18d', absences: 4 },
  { day: '16d', absences: 14 },
  { day: '14d', absences: 18 },
  { day: '12d', absences: 12 },
  { day: '10d', absences: 9 },
  { day: '8d', absences: 24 },
  { day: '6d', absences: 13 },
  { day: '4d', absences: 16 },
  { day: '2d', absences: 5 },
  { day: '1d', absences: 8 },
  { day: 'Today', absences: 3 },
];

const chartRef = ref<HTMLElement | null>(null);

const renderChart = () => {
  if (!chartRef.value) return;

  // Clear previous chart
  d3.select(chartRef.value).selectAll('*').remove();

  const margin = { top: 10, right: 10, bottom: 10, left: 10 };
  const width = chartRef.value.clientWidth - margin.left - margin.right;
  const height = chartRef.value.clientHeight - margin.top - margin.bottom;

  const svg = d3.select(chartRef.value)
    .append('svg')
    .attr('width', width + margin.left + margin.right)
    .attr('height', height + margin.top + margin.bottom)
    .append('g')
    .attr('transform', `translate(${margin.left},${margin.top})`);

  const x = d3.scaleBand()
    .range([0, width])
    .domain(data.map(d => d.day))
    .padding(0.3);

  const y = d3.scaleLinear()
    .domain([0, d3.max(data, d => d.absences) || 0])
    .range([height, 0]);

  svg.selectAll('rect')
    .data(data)
    .enter()
    .append('rect')
    .attr('x', d => x(d.day) || 0)
    .attr('y', d => y(d.absences))
    .attr('width', x.bandwidth())
    .attr('height', d => height - y(d.absences))
    .attr('fill', d => d.day === 'Today' ? '#135bec' : '#135bec33')
    .attr('rx', 2)
    .attr('ry', 2)
    .on('mouseover', function(event, d) {
      d3.select(this).attr('fill', '#135bec');
      // Simple tooltip logic could go here
    })
    .on('mouseout', function(event, d) {
      d3.select(this).attr('fill', d.day === 'Today' ? '#135bec' : '#135bec33');
    });
};

onMounted(() => {
  renderChart();
  window.addEventListener('resize', renderChart);
});

watch(data, renderChart);
</script>

<template>
  <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
    <div class="flex items-center justify-between mb-10">
      <div>
        <h3 class="text-lg font-bold text-slate-900">Monthly Absence Trends</h3>
        <p class="text-sm text-slate-500">Total absences tracked over the last 30 days</p>
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

    <div ref="chartRef" class="h-64 w-full"></div>

    <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between text-[10px] text-slate-400 font-bold uppercase tracking-widest px-2">
      <span>30 Days Ago</span>
      <span>15 Days Ago</span>
      <span>Today</span>
    </div>
  </div>
</template>
