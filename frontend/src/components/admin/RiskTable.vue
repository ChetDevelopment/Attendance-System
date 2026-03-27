<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  students: Array<{
    name: string;
    class: string;
    absence_count: number;
  }>;
}>();

const rows = computed(() =>
  props.students.map((student) => {
    const risk =
      student.absence_count >= 6 ? 'Critical' : student.absence_count >= 3 ? 'Warning' : 'Normal';

    return {
      ...student,
      risk,
    };
  }),
);

const criticalCount = computed(() =>
  rows.value.filter((student) => student.risk === 'Critical').length,
);

const riskClass = (risk: string) => [
  'rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wider',
  risk === 'Critical' ? 'bg-rose-50 text-rose-700' : '',
  risk === 'Warning' ? 'bg-amber-50 text-amber-700' : '',
  risk === 'Normal' ? 'bg-emerald-50 text-emerald-700' : '',
];
</script>

<template>
  <div class="flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-primary">Risk Watch</p>
        <h3 class="mt-2 text-lg font-black text-slate-900">Highest Absences</h3>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-right">
        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Critical Cases</p>
        <p class="mt-1 text-lg font-black text-rose-600">{{ criticalCount }}</p>
      </div>
    </div>

    <div class="mt-6 flex-1 overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-slate-200 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
          <tr>
            <th class="pb-3">Student</th>
            <th class="pb-3">Class</th>
            <th class="pb-3">Total</th>
            <th class="pb-3">Risk</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="student in rows" :key="`${student.name}-${student.class}`" class="hover:bg-slate-50/70">
            <td class="py-4">
              <div class="font-bold text-slate-900">{{ student.name }}</div>
            </td>
            <td class="py-4 font-medium text-slate-600">{{ student.class }}</td>
            <td class="py-4 text-lg font-black text-slate-900">{{ student.absence_count }}</td>
            <td class="py-4">
              <span :class="riskClass(student.risk)">{{ student.risk }}</span>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td :colspan="4" class="py-10 text-center text-sm italic text-slate-400">No risk data available.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
