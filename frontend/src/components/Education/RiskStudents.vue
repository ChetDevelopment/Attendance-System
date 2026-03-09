<script setup lang="ts">
import { AlertTriangle, UserCircle, ArrowRight } from 'lucide-vue-next';

defineProps<{
  students: any[];
}>();

const emit = defineEmits<{
  (e: 'viewAll'): void;
  (e: 'quickFollowUp', attendanceId: number): void;
}>();
</script>

<template>
  <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-bold text-lg text-slate-900">Risk Students</h3>
      <button 
        @click="emit('viewAll')"
        class="text-[10px] font-black text-[#135bec] uppercase tracking-widest hover:underline"
      >
        VIEW ALL
      </button>
    </div>
    <div class="space-y-3">
      <div 
        v-for="(student, i) in students" 
        :key="i" 
        @click="emit('quickFollowUp', student.latest_attendance_id)"
        class="flex items-center justify-between p-3.5 rounded-xl border border-rose-100 bg-rose-50 transition-all hover:scale-[1.02] cursor-pointer"
      >
        <div class="flex items-center gap-3">
          <div class="size-10 rounded-full border-2 border-rose-500 p-0.5">
            <div class="size-full rounded-full bg-white flex items-center justify-center text-slate-400">
              <UserCircle :size="20" />
            </div>
          </div>
          <div>
            <p class="text-sm font-bold text-slate-900">{{ student.name }}</p>
            <p class="text-xs font-bold text-rose-600">{{ student.absence_count }} Absences (3+ Risk)</p>
          </div>
        </div>
        <AlertTriangle :size="18" class="text-rose-500" />
      </div>
    </div>
    <button 
      @click="emit('viewAll')"
      class="w-full mt-6 py-3 text-[10px] font-bold text-slate-500 hover:text-[#135bec] transition-all flex items-center justify-center gap-2 group tracking-widest"
    >
      VIEW DETAILED ANALYSIS 
      <ArrowRight :size="14" class="group-hover:translate-x-1 transition-transform" />
    </button>
  </div>
</template>
