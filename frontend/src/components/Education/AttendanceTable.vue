<script setup lang="ts">
import { Plus } from 'lucide-vue-next';
import { cn } from '../../utils/cn';

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
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
      <h3 class="font-bold text-lg text-slate-900">{{ title }}</h3>
      <button 
        v-if="$attrs.onViewAll"
        @click="emit('viewAll')"
        class="text-[#135bec] text-sm font-bold hover:underline transition-all"
      >
        View All
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-slate-50/50 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
            <th v-if="showDate" class="px-6 py-4">Date</th>
            <th class="px-6 py-4">Student Name</th>
            <th class="px-6 py-4">Class</th>
            <th v-if="!showDate" class="px-6 py-4">Status</th>
            <th v-if="showDate" class="px-6 py-4">Reason</th>
            <th v-if="showDate" class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-if="isLoading">
            <td :colspan="showDate ? 6 : 4" class="px-6 py-12 text-center text-slate-400 text-sm font-medium">
              Loading attendance data...
            </td>
          </tr>
          <tr v-else-if="data.length === 0">
            <td :colspan="showDate ? 6 : 4" class="px-6 py-12 text-center text-slate-400 text-sm font-medium">
              No records found.
            </td>
          </tr>
          <tr v-else v-for="(student, i) in data" :key="i" class="hover:bg-slate-50/50 transition-colors group">
            <td v-if="showDate" class="px-6 py-4 text-sm text-slate-500 font-medium">{{ student.date }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="size-8 rounded-full bg-[#135bec]/5 flex items-center justify-center text-[#135bec] font-bold text-xs">
                  {{ (student.name || 'Unknown').split(' ').map((n: any) => n[0]).join('') }}
                </div>
                <span class="text-sm font-semibold text-slate-900">{{ student.name || 'Unknown Student' }}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-slate-500 font-medium">{{ student.class || 'Unknown Class' }}</td>
            <td v-if="showDate" class="px-6 py-4 text-sm text-slate-500 font-medium italic">{{ student.reason || 'Not specified' }}</td>
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
                class="p-2 text-slate-400 hover:text-[#135bec] hover:bg-[#135bec]/5 rounded-lg transition-all"
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
