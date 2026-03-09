<script setup lang="ts">
import { cn } from '@/lib/utils';

const students = [
  { name: 'Sat Vichet', id: 'PNC2026-053', class: '10A', total: 14, risk: 'Critical' },
  { name: 'Lara Croft', id: 'PNC2026-124', class: '12B', total: 11, risk: 'Critical' },
  { name: 'Peter Parker', id: 'PNC2026-088', class: '10A', total: 9, risk: 'Warning' },
  { name: 'Tony Stark', id: 'PNC2026-112', class: '11C', total: 7, risk: 'Warning' },
  { name: 'Bruce Wayne', id: 'PNC2026-045', class: '12A', total: 4, risk: 'Normal' },
];

const handleDownloadReport = () => {
  const headers = ['Student Name', 'Student ID', 'Class', 'Total Absences', 'Risk Level'];
  const csvContent = [
    headers.join(','),
    ...students.map(s => `${s.name},${s.id},${s.class},${s.total},${s.risk}`)
  ].join('\n');

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `Risk_Report_${new Date().toISOString().split('T')[0]}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
</script>

<template>
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col h-full">
    <div class="p-6 border-b border-slate-200">
      <h3 class="text-lg font-bold text-slate-900">Highest Absences</h3>
      <p class="text-sm text-slate-500">Student risk level assessment</p>
    </div>
    
    <div class="flex-1 overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider">
          <tr>
            <th class="px-6 py-3">Student</th>
            <th class="px-6 py-3">Class</th>
            <th class="px-6 py-3">Total</th>
            <th class="px-6 py-3">Risk</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="student in students" :key="student.id" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4">
              <div class="font-bold text-slate-900">{{ student.name }}</div>
              <div class="text-[10px] text-slate-400 font-mono">ID: {{ student.id }}</div>
            </td>
            <td class="px-6 py-4 font-medium text-slate-600">{{ student.class }}</td>
            <td class="px-6 py-4 font-black text-slate-900">{{ student.total }}</td>
            <td class="px-6 py-4">
              <span :class="cn(
                'px-2 py-1 text-[9px] font-black rounded uppercase',
                student.risk === 'Critical' && 'bg-red-100 text-red-600',
                student.risk === 'Warning' && 'bg-amber-100 text-amber-600',
                student.risk === 'Normal' && 'bg-green-100 text-green-600'
              )">
                {{ student.risk }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div class="p-4 border-t border-slate-200 bg-slate-50">
      <button 
        @click="handleDownloadReport"
        class="w-full py-2 bg-white border border-slate-200 rounded-lg text-[10px] font-bold text-slate-600 hover:bg-slate-50 transition-colors"
      >
        Download Full Risk Report
      </button>
    </div>
  </div>
</template>
