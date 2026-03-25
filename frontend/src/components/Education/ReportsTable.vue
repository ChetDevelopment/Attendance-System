<script setup lang="ts">
import { FileText } from 'lucide-vue-next';
import { ClassReport } from './types';

defineProps<{
  reports: ClassReport[];
}>();

const emit = defineEmits<{
  (e: 'export'): void;
}>();

const calculatePercentage = (report: ClassReport) => {
  const total = report.present_count + report.absent_count + report.late_count;
  return total > 0 ? Math.round((report.present_count / total) * 100) : 0;
};
</script>

<template>
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6 flex items-center justify-between">
      <h3 class="text-lg font-black text-slate-900">Class Attendance Reports</h3>
      <button 
        @click="emit('export')"
        class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white transition-all hover:bg-primary/90"
      >
        <FileText :size="16" />
        Export CSV
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
            <th class="px-6 py-4">Class</th>
            <th class="px-6 py-4">Attendance %</th>
            <th class="px-6 py-4">Present</th>
            <th class="px-6 py-4">Absent</th>
            <th class="px-6 py-4">Late</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="(report, i) in reports" :key="i" class="transition-colors hover:bg-slate-50/60">
            <td class="px-6 py-4 font-bold text-slate-900">{{ report.class }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div class="h-full bg-primary" :style="{ width: `${calculatePercentage(report)}%` }"></div>
                </div>
                <span class="text-sm font-bold text-slate-700">{{ calculatePercentage(report) }}%</span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-emerald-600 font-bold">{{ report.present_count }}</td>
            <td class="px-6 py-4 text-sm text-rose-600 font-bold">{{ report.absent_count }}</td>
            <td class="px-6 py-4 text-sm text-orange-600 font-bold">{{ report.late_count }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
