<script setup lang="ts">
import { computed, ref } from 'vue';
import Modal from './Modal.vue';
import {
  Search,
  Unlock,
  Edit3,
  CheckCircle,
  AlertCircle,
  Filter,
  History as HistoryIcon,
  MapPin,
} from 'lucide-vue-next';

type AttendanceStatus = 'Present' | 'Absent' | 'Late' | 'Excused';

type AttendanceRecord = {
  id: number;
  name: string;
  studentId: string;
  session: string;
  status: AttendanceStatus | string;
  teacher: string;
  time: string;
  location: {
    lat: number;
    lng: number;
    name: string;
    isOffsite: boolean;
  };
};

const initialRecords: AttendanceRecord[] = [
  {
    id: 1,
    name: 'Sat Vichet',
    studentId: 'PNC2026-053',
    session: 'Math (10A)',
    status: 'Absent',
    teacher: 'Dr. Smith',
    time: '08:15 AM',
    location: { lat: 11.5564, lng: 104.9282, name: 'Main Gate', isOffsite: false },
  },
  {
    id: 2,
    name: 'Lara Croft',
    studentId: 'PNC2026-124',
    session: 'Math (10A)',
    status: 'Present',
    teacher: 'Dr. Smith',
    time: '08:02 AM',
    location: { lat: 11.556, lng: 104.9285, name: 'Building A', isOffsite: false },
  },
  {
    id: 3,
    name: 'Peter Parker',
    studentId: 'PNC2026-088',
    session: 'Math (10A)',
    status: 'Late',
    teacher: 'Dr. Smith',
    time: '08:25 AM',
    location: { lat: 11.557, lng: 104.929, name: 'Cafeteria', isOffsite: false },
  },
  {
    id: 4,
    name: 'Tony Stark',
    studentId: 'PNC2026-112',
    session: 'Math (10A)',
    status: 'Present',
    teacher: 'Dr. Smith',
    time: '08:30 AM',
    location: { lat: 11.5444, lng: 104.8922, name: 'External (Home)', isOffsite: true },
  },
];

const records = ref<AttendanceRecord[]>(initialRecords);
const isEditModalOpen = ref(false);
const isUnlockModalOpen = ref(false);
const isLocationModalOpen = ref(false);
const selectedRecord = ref<AttendanceRecord | null>(null);
const searchQuery = ref('');
const statusFilter = ref('All Status');

const stats = [
  { label: 'Total Records', value: '1,240', icon: HistoryIcon, color: 'text-blue-600', bg: 'bg-blue-50' },
  { label: 'Off-site Attendance', value: '24', icon: MapPin, color: 'text-red-600', bg: 'bg-red-50' },
  { label: 'Corrected Today', value: '5', icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-50' },
];

const filteredRecords = computed(() =>
  records.value.filter((r) => {
    const matchesSearch =
      r.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      r.studentId.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesStatus = statusFilter.value === 'All Status' || r.status === statusFilter.value;
    return matchesSearch && matchesStatus;
  })
);

const handleUpdateStatus = (newStatus: string) => {
  if (!selectedRecord.value) return;

  records.value = records.value.map((r) =>
    r.id === selectedRecord.value!.id ? { ...r, status: newStatus } : r
  );
  selectedRecord.value = { ...selectedRecord.value, status: newStatus };
  isEditModalOpen.value = false;
};

const openLocationModal = (record: AttendanceRecord) => {
  selectedRecord.value = record;
  isLocationModalOpen.value = true;
};

const openEditModal = (record: AttendanceRecord) => {
  selectedRecord.value = record;
  isEditModalOpen.value = true;
};

const statusPillClass = (status: string) => [
  'px-2 py-1 text-[9px] font-black rounded uppercase',
  status === 'Present' ? 'bg-green-100 text-green-600' : '',
  status === 'Absent' ? 'bg-red-100 text-red-600' : '',
  status === 'Late' ? 'bg-amber-100 text-amber-600' : '',
  status === 'Excused' ? 'bg-blue-100 text-blue-600' : '',
];

const mapIconClass = (isOffsite: boolean) => ['size-3', isOffsite ? 'text-red-500' : 'text-primary'];

const locationTextClass = (isOffsite: boolean) => [
  'text-[11px] font-medium',
  isOffsite ? 'text-red-600 font-bold' : 'text-slate-500',
];

const statusButtons: AttendanceStatus[] = ['Present', 'Absent', 'Late', 'Excused'];
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
        <div :class="['size-12 rounded-xl flex items-center justify-center', stat.bg]">
          <component :is="stat.icon" :class="['size-6', stat.color]" />
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
              v-model="searchQuery"
              type="text"
              placeholder="Search records..."
              class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
            />
          </div>
          <select v-model="statusFilter" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none">
            <option>All Status</option>
            <option>Present</option>
            <option>Absent</option>
            <option>Late</option>
          </select>
          <input type="date" class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none" value="2024-02-24" />
        </div>
        <div class="flex items-center gap-2">
          <button class="flex items-center gap-2 px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50">
            <Filter class="size-4" />
            Filters
          </button>
        </div>
      </div>

      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider">
          <tr>
            <th class="px-6 py-4">Student</th>
            <th class="px-6 py-4">Session</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4">Location</th>
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
              <span :class="statusPillClass(r.status)">{{ r.status }}</span>
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-1.5">
                <MapPin :class="mapIconClass(r.location?.isOffsite)" />
                <span :class="locationTextClass(r.location?.isOffsite)">
                  {{ r.location?.name || 'Unknown' }}
                  {{ r.location?.isOffsite ? ' (OFF-SITE)' : '' }}
                </span>
              </div>
            </td>
            <td class="px-6 py-4 text-slate-500">{{ r.teacher }}</td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-1">
                <button
                  @click="openLocationModal(r)"
                  class="p-2 text-slate-400 hover:text-primary hover:bg-primary/5 rounded-lg"
                  title="View Location"
                >
                  <MapPin class="size-4" />
                </button>
                <button
                  @click="openEditModal(r)"
                  class="p-2 text-slate-400 hover:text-primary hover:bg-primary/5 rounded-lg"
                  title="Edit"
                >
                  <Edit3 class="size-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredRecords.length === 0">
            <td :colspan="5" class="px-6 py-10 text-center text-slate-400 italic">No records found matching your criteria.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
      <h3 class="text-lg font-bold text-slate-900">Manual Correction Form</h3>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Student ID</label>
          <input
            type="text"
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
            placeholder="e.g. S-1029"
          />
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">New Status</label>
          <select class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20">
            <option>Present</option>
            <option>Absent</option>
            <option>Late</option>
            <option>Excused</option>
          </select>
        </div>
        <div class="space-y-1 md:col-span-2">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Reason for Change</label>
          <input
            type="text"
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
            placeholder="e.g. Medical certificate provided"
          />
        </div>
      </div>
      <div class="flex justify-end">
        <button class="px-6 py-2 bg-slate-900 text-white rounded-lg font-bold text-sm shadow-xl">Apply Correction</button>
      </div>
    </div>
    <Modal :is-open="isEditModalOpen" title="Edit Attendance Record" @close="isEditModalOpen = false">
      <div class="space-y-4">
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-4">
          <div class="size-12 rounded-full bg-slate-200 overflow-hidden">
            <img :src="`https://picsum.photos/seed/${selectedRecord?.studentId}/100/100`" alt="" />
          </div>
          <div>
            <h4 class="font-bold text-slate-900">{{ selectedRecord?.name }}</h4>
            <p class="text-xs text-slate-500">{{ selectedRecord?.session }} - Feb 24, 2024</p>
          </div>
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Current Status</label>
          <div class="flex gap-2">
            <button
              v-for="s in statusButtons"
              :key="s"
              @click="handleUpdateStatus(s)"
              :class="[
                'flex-1 py-2 rounded-lg text-[10px] font-black uppercase transition-all',
                selectedRecord?.status === s
                  ? 'bg-primary text-white shadow-lg shadow-primary/20'
                  : 'bg-slate-100 text-slate-400 hover:bg-slate-200',
              ]"
            >
              {{ s }}
            </button>
          </div>
        </div>
        <div class="pt-4 flex justify-end gap-3">
          <button @click="isEditModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button
            @click="handleUpdateStatus(selectedRecord?.status || 'Present')"
            class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg"
          >
            Update Record
          </button>
        </div>
      </div>
    </Modal>
    <Modal :is-open="isLocationModalOpen" title="Attendance Location" @close="isLocationModalOpen = false">
      <div class="space-y-6">
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-4">
          <div class="size-12 rounded-full bg-primary/10 flex items-center justify-center">
            <MapPin class="size-6 text-primary" />
          </div>
          <div>
            <h4 class="font-bold text-slate-900">{{ selectedRecord?.location?.name }}</h4>
            <p class="text-xs text-slate-500">
              Coordinates: {{ selectedRecord?.location?.lat }}, {{ selectedRecord?.location?.lng }}
            </p>
          </div>
        </div>

        <div class="aspect-video bg-slate-100 rounded-2xl border border-slate-200 relative overflow-hidden flex items-center justify-center">
          <div class="absolute inset-0 opacity-20">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
              <path
                d="M0 20 L100 20 M0 40 L100 40 M0 60 L100 60 M0 80 L100 80 M20 0 L20 100 M40 0 L40 100 M60 0 L60 100 M80 0 L80 100"
                stroke="currentColor"
                fill="none"
                stroke-width="0.5"
              />
            </svg>
          </div>
          <div class="relative">
            <div class="size-4 bg-primary rounded-full animate-ping absolute -inset-0"></div>
            <MapPin class="size-8 text-primary relative z-10" />
          </div>
          <div class="absolute bottom-4 left-4 right-4 bg-white/90 backdrop-blur p-3 rounded-lg border border-slate-200 shadow-sm">
            <p class="text-[10px] font-bold text-slate-500 uppercase">Verification Status</p>
            <p
              v-if="selectedRecord?.location?.isOffsite"
              class="text-xs font-bold text-red-600 flex items-center gap-1"
            >
              <AlertCircle class="size-3" />
              Outside Geofence (Off-site)
            </p>
            <p v-else class="text-xs font-bold text-green-600 flex items-center gap-1">
              <CheckCircle class="size-3" />
              Within Geofence (School Perimeter)
            </p>
          </div>
        </div>

        <div class="pt-4 flex justify-end">
          <button @click="isLocationModalOpen = false" class="px-6 py-2 bg-slate-900 text-white rounded-lg font-bold text-sm shadow-xl">Close Map</button>
        </div>
      </div>
    </Modal>
    <Modal
      :is-open="isUnlockModalOpen"
      title="Unlock Attendance Submission"
      @close="isUnlockModalOpen = false"
    >
      <div class="space-y-4">
        <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl flex gap-3">
          <AlertCircle class="size-5 text-amber-600 flex-shrink-0" />
          <div class="text-xs text-amber-800">
            <p class="font-bold mb-1">Confirm Unlock</p>
            <p>
              Unlocking will allow the assigned teacher to re-submit attendance for this session. All previous
              records for this session will be preserved until re-submission.
            </p>
          </div>
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Select Session</label>
          <select class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20">
            <option>Math (10A) - Dr. Smith</option>
            <option>English (10B) - Ms. Johnson</option>
          </select>
        </div>
        <div class="pt-4 flex justify-end gap-3">
          <button @click="isUnlockModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button class="px-4 py-2 text-sm font-bold text-white bg-amber-500 rounded-lg">Unlock Session</button>
        </div>
      </div>
    </Modal>
  </div>
</template>
