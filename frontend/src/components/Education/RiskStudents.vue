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
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-black text-slate-900">Risk Students</h3>
      <button 
        @click="emit('viewAll')"
        class="text-[10px] font-bold uppercase tracking-[0.22em] text-primary hover:underline"
      >
        VIEW ALL
      </button>
    </div>
    <div class="space-y-3">
      <div 
        v-for="(student, i) in students" 
        :key="i" 
        @click="emit('quickFollowUp', student.latest_attendance_id)"
        class="flex cursor-pointer items-center justify-between rounded-2xl border border-rose-100 bg-rose-50/70 p-4 transition-colors hover:bg-rose-100/80"
      >
        <div class="flex items-center gap-3">
          <div class="rounded-full border-2 border-rose-500 p-0.5">
            <div class="flex size-10 items-center justify-center rounded-full bg-white text-slate-400">
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
      class="group mt-6 flex w-full items-center justify-center gap-2 py-3 text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500 transition-all hover:text-primary"
    >
      VIEW DETAILED ANALYSIS 
      <ArrowRight :size="14" class="group-hover:translate-x-1 transition-transform" />
    </button>
  </div>
</template>
