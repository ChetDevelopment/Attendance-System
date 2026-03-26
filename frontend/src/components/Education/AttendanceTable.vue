<script setup lang="ts">
import { Plus } from 'lucide-vue-next';
import { cn } from '../../utils/cn';
import { setImageFallback } from '../../utils/imageFallback';

const props = withDefaults(defineProps<{
  title: string;
  data: any[];
  isLoading: boolean;
  showDate?: boolean;
}>(), {
  showDate: false
});

const emit = defineEmits<{
  (e: 'openDetail', id: number): void;
  (e: 'viewAll'): void;
}>();
</script>

<template>
  <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 p-6">
      <h3 class="text-lg font-black text-slate-900">{{ title }}</h3>
      <button 
        v-if="$attrs.onViewAll"
        @click="emit('viewAll')"
        class="text-xs font-bold uppercase tracking-wider text-primary transition-all hover:underline"
      >
        View All
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
            <th v-if="showDate" class="px-6 py-4">Date</th>
            <th class="px-6 py-4">Student Name</th>
            <th class="px-6 py-4">Class</th>
            <th v-if="!showDate" class="px-6 py-4">Status</th>
            <th v-if="showDate" class="px-6 py-4">Reason</th>
            <th v-if="showDate" class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="isLoading">
            <td :colspan="showDate ? 6 : 4" class="px-6 py-12 text-center text-sm font-medium text-slate-400">
              Loading attendance data...
            </td>
          </tr>
          <tr v-else-if="data.length === 0">
            <td :colspan="showDate ? 6 : 4" class="px-6 py-12 text-center text-sm font-medium text-slate-400">
              No records found.
            </td>
          </tr>
          <tr v-else v-for="(student, i) in data" :key="i" class="group transition-colors hover:bg-slate-50/60">
            <td v-if="showDate" class="px-6 py-4 text-sm font-medium text-slate-500">{{ student.date }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center overflow-hidden rounded-full bg-primary/10 text-xs font-bold text-primary">
                  <img
                    v-if="student.studentPhoto"
                    :src="student.studentPhoto"
                    :alt="student.name || 'Student photo'"
                    class="size-full object-cover"
                    referrerPolicy="no-referrer"
                    @error="(event) => setImageFallback(event, '/default-student.svg')"
                  />
                  <template v-else>
                    {{ (student.name || 'Unknown').split(' ').map((n: any) => n[0]).join('') }}
                  </template>
                </div>
                <span class="text-sm font-semibold text-slate-900">{{ student.name || 'Unknown Student' }}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm font-medium text-slate-500">{{ student.class || 'Unknown Class' }}</td>
            <td v-if="showDate" class="px-6 py-4 text-sm font-medium italic text-slate-500">{{ student.reason || 'Not specified' }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <span :class="cn('size-2 rounded-full', student.resolved ? 'bg-emerald-500' : 'bg-rose-500')"></span>
                <span :class="cn('text-xs font-bold', student.resolved ? 'text-emerald-500' : 'text-rose-500')">
                  {{ student.resolved ? "Resolved" : "Pending" }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4 text-right">
              <button 
                @click="emit('openDetail', Number(student.attendance_id))"
                class="rounded-lg p-2 text-slate-400 transition-all hover:bg-primary/5 hover:text-primary"
                :disabled="!student.attendance_id"
              >
                <Plus :size="18" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
