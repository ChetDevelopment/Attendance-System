<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  students: Array<{
    name: string
    class: string
    absence_count: number
  }>
}>()

const rows = computed(() =>
  props.students.map((student) => {
    const risk =
      student.absence_count >= 6 ? 'Critical' : student.absence_count >= 3 ? 'Warning' : 'Normal'
    return {
      ...student,
      risk,
    }
  })
)

const riskClass = (risk: string) => [
  'px-2 py-1 text-[9px] font-black rounded uppercase',
  risk === 'Critical' ? 'bg-red-100 text-red-600' : '',
  risk === 'Warning' ? 'bg-amber-100 text-amber-600' : '',
  risk === 'Normal' ? 'bg-green-100 text-green-600' : '',
]
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
          <tr v-for="student in rows" :key="`${student.name}-${student.class}`" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4">
              <div class="font-bold text-slate-900">{{ student.name }}</div>
            </td>
            <td class="px-6 py-4 font-medium text-slate-600">{{ student.class }}</td>
            <td class="px-6 py-4 font-black text-slate-900">{{ student.absence_count }}</td>
            <td class="px-6 py-4">
              <span :class="riskClass(student.risk)">{{ student.risk }}</span>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td :colspan="4" class="px-6 py-10 text-center text-slate-400 italic">No risk data available.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
