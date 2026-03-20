<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { 
  Search, 
  Download, 
  ChevronLeft, 
  ChevronRight,
  CheckCircle2,
  Clock,
  X,
  AlertCircle,
  Info
} from 'lucide-vue-next';
import { getStudentHistory } from '../../services/studentPortalService';
import { AttendanceRecord } from '../types';

const attendanceHistory = ref<AttendanceRecord[]>([]);
const searchQuery = ref('');
const statusFilter = ref<'ALL' | 'PRESENT' | 'LATE' | 'ABSENT'>('ALL');
const dateRange = ref({ start: '', end: '' });
const sortBy = ref<keyof AttendanceRecord>('date');
const sortOrder = ref<'asc' | 'desc'>('desc');
const notification = ref<{ message: string; type: 'success' | 'error' } | null>(null);

const showNotification = (message: string, type: 'success' | 'error' = 'success') => {
  notification.value = { message, type };
  setTimeout(() => {
    notification.value = null;
  }, 3000);
};

const filteredHistory = computed(() => {
  let records = [...attendanceHistory.value];

  if (searchQuery.value.trim()) {
    const query = searchQuery.value.trim().toLowerCase();
    records = records.filter((record) =>
      [record.courseName, record.instructor, record.status]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(query))
    );
  }

  if (statusFilter.value !== 'ALL') {
    records = records.filter(r => r.status === statusFilter.value);
  }

  if (dateRange.value.start) {
    const start = new Date(dateRange.value.start);
    records = records.filter(r => new Date(r.date) >= start);
  }
  if (dateRange.value.end) {
    const end = new Date(dateRange.value.end);
    records = records.filter(r => new Date(r.date) <= end);
  }

  records.sort((a, b) => {
    let valA: any = a[sortBy.value] || '';
    let valB: any = b[sortBy.value] || '';

    if (sortBy.value === 'date') {
      valA = new Date(a.date).getTime();
      valB = new Date(b.date).getTime();
    }

    if (valA < valB) return sortOrder.value === 'asc' ? -1 : 1;
    if (valA > valB) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });

  return records;
});

const toggleSort = (field: keyof AttendanceRecord) => {
  if (sortBy.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = field;
    sortOrder.value = 'desc';
  }
};

onMounted(async () => {
  try {
    attendanceHistory.value = await getStudentHistory();
  } catch (err) {
    showNotification("Unable to load attendance history.", "error");
  }
});
</script>

<template>
  <div class="p-8 space-y-8">
    <!-- Notification Toast -->
    <transition name="fade">
      <div v-if="notification" :class="[
        'fixed top-6 left-1/2 -translate-x-1/2 z-[100] px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border backdrop-blur-md',
        notification.type === 'success' ? 'bg-green-500/90 border-green-400 text-white' : 'bg-red-500/90 border-red-400 text-white'
      ]">
        <CheckCircle2 v-if="notification.type === 'success'" :size="20" />
        <AlertCircle v-else :size="20" />
        <span class="font-bold text-sm">{{ notification.message }}</span>
      </div>
    </transition>

    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold dark:text-white">Attendance History</h1>
        <p class="text-slate-500 mt-2">View and filter your past attendance records.</p>
      </div>
      <button class="bg-white dark:bg-slate-900 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-2 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
        <Download :size="18" class="text-primary" />
        Export CSV
      </button>
    </div>

    <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
      <div class="flex flex-col lg:flex-row gap-6 mb-8">
        <div class="flex-1 relative">
          <Search class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Search by course or instructor..." 
            class="w-full pl-12 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-primary outline-none dark:text-white"
          />
        </div>
        <div class="flex flex-wrap gap-4">
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Status:</span>
            <select v-model="statusFilter" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none dark:text-white">
              <option value="ALL">All Status</option>
              <option value="PRESENT">Present</option>
              <option value="LATE">Late</option>
              <option value="ABSENT">Absent</option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">From:</span>
            <input v-model="dateRange.start" type="date" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none dark:text-white" />
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">To:</span>
            <input v-model="dateRange.end" type="date" class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2 text-sm outline-none dark:text-white" />
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="text-left border-b border-slate-100 dark:border-slate-800">
              <th @click="toggleSort('date')" class="pb-4 font-bold text-xs text-slate-400 uppercase tracking-widest px-4 cursor-pointer hover:text-primary transition-colors">Date</th>
              <th @click="toggleSort('courseName')" class="pb-4 font-bold text-xs text-slate-400 uppercase tracking-widest px-4 cursor-pointer hover:text-primary transition-colors">Course Session</th>
              <th class="pb-4 font-bold text-xs text-slate-400 uppercase tracking-widest px-4">Time</th>
              <th @click="toggleSort('status')" class="pb-4 font-bold text-xs text-slate-400 uppercase tracking-widest px-4 cursor-pointer hover:text-primary transition-colors text-center">Status</th>
              <th class="pb-4 font-bold text-xs text-slate-400 uppercase tracking-widest px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
            <tr v-for="record in filteredHistory" :key="record.id" class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
              <td class="py-5 px-4">
                <p class="text-sm font-bold dark:text-white">{{ record.date }}</p>
                <p class="text-[10px] text-slate-500 font-mono">ID: #{{ record.id.slice(-6) }}</p>
              </td>
              <td class="py-5 px-4">
                <p class="text-sm font-bold dark:text-white">{{ record.courseName }}</p>
                <p class="text-[10px] text-slate-500">Instructor: {{ record.instructor }}</p>
              </td>
              <td class="py-5 px-4">
                <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                  <Clock :size="14" />
                  <span class="text-xs font-medium">{{ record.timeSlot }}</span>
                </div>
              </td>
              <td class="py-5 px-4 text-center">
                <span :class="[
                  'px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider',
                  record.status === 'PRESENT' ? 'bg-green-500/10 text-green-600' : 
                  record.status === 'LATE' ? 'bg-amber-500/10 text-amber-600' : 'bg-red-500/10 text-red-600'
                ]">
                  {{ record.status }}
                </span>
              </td>
              <td class="py-5 px-4 text-right">
                <button class="p-2 hover:bg-white dark:hover:bg-slate-700 rounded-xl transition-all border border-transparent hover:border-slate-200 dark:hover:border-slate-600">
                  <Info :size="18" class="text-slate-400" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-8 pt-8 border-t border-slate-100 dark:border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4">
        <p class="text-xs text-slate-500 font-medium">Showing <span class="font-bold text-slate-900 dark:text-white">{{ filteredHistory.length }}</span> of <span class="font-bold text-slate-900 dark:text-white">{{ attendanceHistory.length }}</span> records</p>
        <div class="flex items-center gap-2">
          <button class="p-2 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-all border border-slate-200 dark:border-slate-700 disabled:opacity-50">
            <ChevronLeft :size="18" />
          </button>
          <button class="w-8 h-8 flex items-center justify-center bg-primary text-white rounded-lg font-bold shadow-sm shadow-primary/30">1</button>
          <button class="p-2 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-all border border-slate-200 dark:border-slate-700">
            <ChevronRight :size="18" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
