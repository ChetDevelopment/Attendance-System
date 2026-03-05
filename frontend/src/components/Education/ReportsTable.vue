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
  <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
    <div class="flex justify-between items-center mb-6">
      <h3 class="font-bold text-lg text-slate-900">Class Attendance Reports</h3>
      <button 
        @click="emit('export')"
        class="px-4 py-2 bg-[#135bec] text-white text-xs font-bold rounded-xl hover:bg-[#135bec]/90 transition-all flex items-center gap-2"
      >
        <FileText :size="16" />
        Export CSV
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-slate-50/50 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
            <th class="px-6 py-4">Class</th>
            <th class="px-6 py-4">Attendance %</th>
            <th class="px-6 py-4">Present</th>
            <th class="px-6 py-4">Absent</th>
            <th class="px-6 py-4">Late</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="(report, i) in reports" :key="i" class="hover:bg-slate-50/50 transition-colors">
            <td class="px-6 py-4 font-bold text-slate-900">{{ report.class }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div class="h-full bg-[#135bec]" :style="{ width: `${calculatePercentage(report)}%` }"></div>
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
