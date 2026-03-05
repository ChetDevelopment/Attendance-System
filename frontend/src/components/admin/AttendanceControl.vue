<script setup lang="ts">
import { ref, computed } from 'vue';
import Modal from './Modal.vue';
import { Search, Unlock, Edit3, CheckCircle, AlertCircle, Filter, History as HistoryIcon, MapPin } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const initialRecords = [
  { id: 1, name: 'Sat Vichet', studentId: 'PNC2026-053', session: 'Math (10A)', status: 'Absent', teacher: 'Dr. Smith', time: '08:15 AM', location: { lat: 11.5564, lng: 104.9282, name: 'Main Gate', isOffsite: false } },
  { id: 2, name: 'Lara Croft', studentId: 'PNC2026-124', session: 'Math (10A)', status: 'Present', teacher: 'Dr. Smith', time: '08:02 AM', location: { lat: 11.5560, lng: 104.9285, name: 'Building A', isOffsite: false } },
  { id: 3, name: 'Peter Parker', studentId: 'PNC2026-088', session: 'Math (10A)', status: 'Late', teacher: 'Dr. Smith', time: '08:25 AM', location: { lat: 11.5570, lng: 104.9290, name: 'Cafeteria', isOffsite: false } },
  { id: 4, name: 'Tony Stark', studentId: 'PNC2026-112', session: 'Math (10A)', status: 'Present', teacher: 'Dr. Smith', time: '08:30 AM', location: { lat: 11.5444, lng: 104.8922, name: 'External (Home)', isOffsite: true } },
];

const records = ref(initialRecords);
const isEditModalOpen = ref(false);
const isUnlockModalOpen = ref(false);
const isLocationModalOpen = ref(false);
const selectedRecord = ref<any>(null);
const searchQuery = ref('');
const statusFilter = ref('All Status');

const stats = [
  { label: 'Total Records', value: '1,240', icon: HistoryIcon, color: 'text-blue-600', bg: 'bg-blue-50' },
  { label: 'Off-site Attendance', value: '24', icon: MapPin, color: 'text-red-600', bg: 'bg-red-50' },
  { label: 'Corrected Today', value: '5', icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-50' },
];

const handleUpdateStatus = (newStatus: string) => {
  if (selectedRecord.value) {
    records.value = records.value.map(r => r.id === selectedRecord.value.id ? { ...r, status: newStatus } : r);
    isEditModalOpen.value = false;
  }
};

const filteredRecords = computed(() => {
  return records.value.filter(r => {
    const matchesSearch = r.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                         r.studentId.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesStatus = statusFilter.value === 'All Status' || r.status === statusFilter.value;
    return matchesSearch && matchesStatus;
  });
});
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Attendance Control</h2>
        <p class="text-sm text-slate-500 font-medium">Review and correct attendance records</p>
      </div>
      <button 
        @click="isUnlockModalOpen = true"
        class="flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg font-bold text-sm shadow-lg shadow-amber-500/20 hover:bg-amber-600 transition-all"
      >
        <Unlock class="size-4" />
        Unlock Submission
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div v-for="(stat, i) in stats" :key="i" class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div :class="cn('size-12 rounded-xl flex items-center justify-center', stat.bg)">
          <component :is="stat.icon" :class="cn('size-6', stat.color)" />
        </div>
        <div>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ stat.label }}</p>
          <p class="text-2xl font-black text-slate-900">{{ stat.value }}</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 flex-1">
          <div class="relative max-w-xs w-full">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
            <input 
              type="text" 
              placeholder="Search records..." 
              v-model="searchQuery"
              class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" 
            />
          </div>
          <select 
            v-model="statusFilter"
            class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none"
          >
            <option>All Status</option>
            <option>Present</option>
            <option>Absent</option>
            <option>Late</option>
          </select>
        </div>
      </div>

      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider">
          <tr>
            <th class="px-6 py-4">Student</th>
            <th class="px-6 py-4">Session</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4">Submitted By</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="r in filteredRecords" :key="r.id" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4">
              <div class="font-bold text-slate-900">{{ r.name }}</div>
              <div class="text-[10px] text-slate-400 font-mono">ID: {{ r.studentId }}</div>
            </td>
            <td class="px-6 py-4">
              <div class="text-slate-600 font-medium">{{ r.session }}</div>
              <div class="text-[10px] text-slate-400">{{ r.time }}</div>
            </td>
            <td class="px-6 py-4">
              <span :class="cn(
                'px-2 py-1 text-[9px] font-black rounded uppercase',
                r.status === 'Present' && 'bg-green-100 text-green-600',
                r.status === 'Absent' && 'bg-red-100 text-red-600',
                r.status === 'Late' && 'bg-amber-100 text-amber-600'
              )">
                {{ r.status }}
              </span>
            </td>
            <td class="px-6 py-4 text-slate-500">{{ r.teacher }}</td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-1">
                <button 
                  @click="selectedRecord = r; isEditModalOpen = true" 
                  class="p-2 text-slate-400 hover:text-primary hover:bg-primary/5 rounded-lg"
                  title="Edit"
                >
                  <Edit3 class="size-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredRecords.length === 0">
            <td colSpan="5" class="px-6 py-10 text-center text-slate-400 italic">No records found matching your criteria.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Modal :is-open="isEditModalOpen" @close="isEditModalOpen = false" title="Edit Attendance Record">
      <div class="space-y-4">
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-4">
          <div class="size-12 rounded-full bg-slate-200 overflow-hidden">
            <img :src="`https://picsum.photos/seed/${selectedRecord?.studentId}/100/100`" alt="" />
          </div>
          <div>
            <h4 class="font-bold text-slate-900">{{ selectedRecord?.name }}</h4>
            <p class="text-xs text-slate-500">{{ selectedRecord?.session }}</p>
          </div>
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">New Status</label>
          <div class="flex gap-2">
            <button 
              v-for="s in ['Present', 'Absent', 'Late']"
              :key="s" 
              @click="handleUpdateStatus(s)"
              :class="cn(
                'flex-1 py-2 rounded-lg text-[10px] font-black uppercase transition-all',
                selectedRecord?.status === s 
                  ? 'bg-primary text-white shadow-lg shadow-primary/20' 
                  : 'bg-slate-100 text-slate-400 hover:bg-slate-200'
              )"
            >
              {{ s }}
            </button>
          </div>
        </div>
        <div class="pt-4 flex justify-end gap-3">
          <button @click="isEditModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button 
            @click="handleUpdateStatus(selectedRecord?.status)"
            class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg"
          >
            Update Record
          </button>
        </div>
      </div>
    </Modal>

    <Modal :is-open="isUnlockModalOpen" @close="isUnlockModalOpen = false" title="Unlock Attendance Submission">
      <div class="space-y-4">
        <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl flex gap-3">
          <AlertCircle class="size-5 text-amber-600 flex-shrink-0" />
          <div class="text-xs text-amber-800">
            <p class="font-bold mb-1">Confirm Unlock</p>
            <p>Unlocking will allow the assigned teacher to re-submit attendance.</p>
          </div>
        </div>
        <div class="pt-4 flex justify-end gap-3">
          <button @click="isUnlockModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button class="px-4 py-2 text-sm font-bold text-white bg-amber-500 rounded-lg">Unlock Session</button>
        </div>
      </div>
    </Modal>
  </div>
</template>
