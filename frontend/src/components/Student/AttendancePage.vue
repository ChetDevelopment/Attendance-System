<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import { 
  CheckCircle2 as CheckCircle2Icon, 
  XCircle as XCircleIcon, 
  Clock as ClockIcon,
  ChevronDown as ChevronDownIcon,
  Search as SearchIcon,
  Save as SaveIcon,
  AlertCircle as AlertCircleIcon,
  Check as CheckIcon
} from 'lucide-vue-next';
import { teacherService } from '../../services/teacherService';
import api from '../../services/api';

const user = JSON.parse(localStorage.getItem('user_data') || '{}');

interface Student {
  id: number;
  name: string;
  class_id?: number | string;
  class_name?: string;
  gender?: string;
  status: 'present' | 'absent' | 'late' | null;
}

const classes = ref<{ id: number | string; label: string }[]>([]);
const sessions = ref<{ id: number; label: string }[]>([]);

const selectedClass = ref<string | number>('');
const selectedSession = ref<number | null>(null);
const searchQuery = ref('');
const students = ref<Student[]>([]);
const isSubmitted = ref(false);
const showSuccess = ref(false);
const loading = ref(true);
const saving = ref(false);
const fetchError = ref('');

const filteredStudents = computed(() => {
  if (!searchQuery.value) return students.value;
  return students.value.filter(s => s.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

const attendanceStats = computed(() => {
  const total = students.value.length;
  const present = students.value.filter(s => s.status === 'present').length;
  const absent = students.value.filter(s => s.status === 'absent').length;
  const late = students.value.filter(s => s.status === 'late').length;
  const pending = students.value.filter(s => s.status === null).length;
  return { total, present, absent, late, pending };
});

const setStatus = (studentId: number, status: 'present' | 'absent' | 'late') => {
  if (isSubmitted.value) return;
  const student = students.value.find(s => s.id === studentId);
  if (student) {
    student.status = student.status === status ? null : status;
  }
};

const resetAttendance = () => {
  students.value = students.value.map(s => ({ ...s, status: null }));
  isSubmitted.value = false;
  showSuccess.value = false;
};

const submitAttendance = async () => {
  if (!selectedSession.value) {
    alert('Please select a session.');
    return;
  }
  if (!selectedClass.value) {
    alert('Please select a class.');
    return;
  }
  if (attendanceStats.value.pending > 0) {
    alert('Please mark attendance for all students before submitting.');
    return;
  }

  saving.value = true;
  fetchError.value = '';
  try {
    const payload = {
      class_id: selectedClass.value,
      session_id: selectedSession.value,
      attendance_date: new Date().toISOString().slice(0, 10),
      records: students.value.map((s) => ({
        student_id: s.id,
        status: s.status,
      })),
    };
    await api.post('/teacher/attendance', payload);
    isSubmitted.value = true;
    showSuccess.value = true;
    setTimeout(() => (showSuccess.value = false), 2500);
  } catch (error: any) {
    console.error(error);
    fetchError.value = error?.response?.data?.message || 'Failed to submit attendance.';
  } finally {
    saving.value = false;
  }
};

const loadStudentsForClass = async (classId: string | number) => {
  try {
    const { data } = await api.get(`/teacher/classes/${classId}/students`);
    const rows = Array.isArray(data) ? data : data?.students || [];
    students.value = rows.map((s: any) => ({
      id: s.id,
      name: s.fullname || s.name || s.student_name || [s.first_name, s.last_name].filter(Boolean).join(' '),
      class_id: s.class_id,
      class_name: s.class?.class_name || s.class_name,
      gender: s.gender,
      status: null,
    }));
  } catch (error: any) {
    console.error(error);
    fetchError.value = 'Failed to load students for this class.';
    students.value = [];
  }
};

const initData = async () => {
  loading.value = true;
  fetchError.value = '';
  try {
    const [schedule, roster] = await Promise.all([
      teacherService.getSchedule(),
      teacherService.getStudents(),
    ]);

    sessions.value = Array.isArray(schedule)
      ? schedule.map((s: any) => ({
          id: s.id,
          label: `${s.name || s.course_name || 'Session'} (${s.start_time || ''} - ${s.end_time || ''})`,
        }))
      : [];
    selectedSession.value = sessions.value[0]?.id || null;

    const classMap = new Map<string | number, string>();
    const normalizedStudents = Array.isArray(roster)
      ? roster.map((s: any) => {
          const classId = s.class_id ?? s.class?.id ?? s.classId ?? 'unknown';
          const className = s.class?.class_name || s.class_name || `Class ${classId}`;
          classMap.set(classId, className);
          return {
            id: s.id,
            name: s.fullname || s.name || s.student_name,
            class_id: classId,
            class_name: className,
            gender: s.gender,
            status: null,
          } as Student;
        })
      : [];

    classes.value = Array.from(classMap.entries()).map(([id, label]) => ({ id, label }));
    selectedClass.value = classes.value[0]?.id || '';
    students.value = normalizedStudents.filter((s) => s.class_id === selectedClass.value) || normalizedStudents;

    if (selectedClass.value) {
      await loadStudentsForClass(selectedClass.value);
    }
  } catch (error: any) {
    console.error(error);
    fetchError.value = 'Failed to load classes or sessions.';
  } finally {
    loading.value = false;
  }
};

onMounted(initData);
</script>

<template>
  <AppLayout title="Manual Attendance" :user="user">
    <div class="space-y-6">
      <div v-if="fetchError" class="bg-red-50 text-red-700 border border-red-200 p-3 rounded-xl">
        {{ fetchError }}
      </div>

      <div class="flex flex-col lg:flex-row gap-4 justify-between items-start lg:items-center">
        <div class="flex flex-wrap gap-4 w-full lg:w-auto">
          <div class="relative min-w-[180px]">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest absolute -top-2 left-3 bg-slate-950 px-1 z-10">Class</label>
            <select 
              v-model="selectedClass"
              :disabled="isSubmitted || loading"
              class="w-full bg-slate-900 border border-slate-800 text-white rounded-xl px-4 py-3 appearance-none focus:outline-none focus:ring-2 focus:ring-pnc-blue/50 disabled:opacity-50 transition-all"
              @change="loadStudentsForClass(selectedClass)"
            >
              <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.label }}</option>
            </select>
            <ChevronDownIcon class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500 pointer-events-none" />
          </div>

          <div class="relative min-w-[280px]">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest absolute -top-2 left-3 bg-slate-950 px-1 z-10">Session</label>
            <select 
              v-model="selectedSession"
              :disabled="isSubmitted || loading"
              class="w-full bg-slate-900 border border-slate-800 text-white rounded-xl px-4 py-3 appearance-none focus:outline-none focus:ring-2 focus:ring-pnc-blue/50 disabled:opacity-50 transition-all"
            >
              <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.label }}</option>
            </select>
            <ChevronDownIcon class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500 pointer-events-none" />
          </div>
        </div>

        <div class="flex items-center gap-3 w-full lg:w-auto">
          <div class="relative flex-1 lg:w-64">
            <SearchIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500" />
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Search students..."
              class="w-full bg-slate-900 border border-slate-800 text-white rounded-xl pl-11 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-pnc-blue/50 transition-all"
            />
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-slate-900/50 border border-slate-800 p-4 rounded-2xl">
          <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Total</p>
          <p class="text-2xl font-bold text-white">{{ attendanceStats.total }}</p>
        </div>
        <div class="bg-emerald-500/5 border border-emerald-500/20 p-4 rounded-2xl">
          <p class="text-[10px] font-bold text-emerald-500/60 uppercase tracking-widest mb-1">Present</p>
          <p class="text-2xl font-bold text-emerald-500">{{ attendanceStats.present }}</p>
        </div>
        <div class="bg-amber-500/5 border border-amber-500/20 p-4 rounded-2xl">
          <p class="text-[10px] font-bold text-amber-500/60 uppercase tracking-widest mb-1">Late</p>
          <p class="text-2xl font-bold text-amber-500">{{ attendanceStats.late }}</p>
        </div>
        <div class="bg-red-500/5 border border-red-500/20 p-4 rounded-2xl">
          <p class="text-[10px] font-bold text-red-500/60 uppercase tracking-widest mb-1">Absent</p>
          <p class="text-2xl font-bold text-red-500">{{ attendanceStats.absent }}</p>
        </div>
        <div class="bg-slate-800/50 border border-slate-700 p-4 rounded-2xl">
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pending</p>
          <p class="text-2xl font-bold text-slate-300">{{ attendanceStats.pending }}</p>
        </div>
      </div>

      <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-4"
      >
        <div v-if="showSuccess" class="bg-emerald-500 text-white p-4 rounded-xl flex items-center justify-between shadow-lg shadow-emerald-500/20">
          <div class="flex items-center gap-3">
            <CheckIcon class="h-6 w-6" />
            <div>
              <p class="font-bold">Attendance Submitted Successfully!</p>
              <p class="text-sm opacity-90">Records for {{ selectedClass }} have been saved.</p>
            </div>
          </div>
          <button @click="showSuccess = false" class="hover:bg-white/20 p-1 rounded-lg transition-colors">
            <XCircleIcon class="h-5 w-5" />
          </button>
        </div>
      </transition>

      <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-800/50 border-b border-slate-800">
              <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Student Name</th>
              <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Gender</th>
              <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Status Selection</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr v-for="student in filteredStudents" :key="student.id" class="hover:bg-slate-800/20 transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-400 group-hover:bg-pnc-blue/20 group-hover:text-pnc-blue transition-colors">
                    {{ student.name.charAt(0) }}
                  </div>
                  <span class="text-white font-medium">{{ student.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="text-slate-400 text-sm">{{ student.gender || '—' }}</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex justify-end gap-2">
                  <button 
                    @click="setStatus(student.id, 'present')"
                    :disabled="isSubmitted"
                    :class="[
                      'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all border',
                      student.status === 'present' 
                        ? 'bg-emerald-500 border-emerald-500 text-white shadow-lg shadow-emerald-500/20' 
                        : 'bg-slate-900 border-slate-800 text-slate-500 hover:border-emerald-500/50 hover:text-emerald-500'
                    ]"
                  >
                    <CheckCircle2Icon class="h-4 w-4" />
                    Present
                  </button>
                  <button 
                    @click="setStatus(student.id, 'late')"
                    :disabled="isSubmitted"
                    :class="[
                      'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all border',
                      student.status === 'late' 
                        ? 'bg-amber-500 border-amber-500 text-white shadow-lg shadow-amber-500/20' 
                        : 'bg-slate-900 border-slate-800 text-slate-500 hover:border-amber-500/50 hover:text-amber-500'
                    ]"
                  >
                    <ClockIcon class="h-4 w-4" />
                    Late
                  </button>
                  <button 
                    @click="setStatus(student.id, 'absent')"
                    :disabled="isSubmitted"
                    :class="[
                      'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all border',
                      student.status === 'absent' 
                        ? 'bg-red-500 border-red-500 text-white shadow-lg shadow-red-500/20' 
                        : 'bg-slate-900 border-slate-800 text-slate-500 hover:border-red-500/50 hover:text-red-500'
                    ]"
                  >
                    <XCircleIcon class="h-4 w-4" />
                    Absent
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="filteredStudents.length === 0">
              <td colspan="3" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center gap-2 text-slate-500">
                  <AlertCircleIcon class="h-8 w-8 opacity-20" />
                  <p>No students found matching your search.</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex justify-between items-center bg-slate-900 border border-slate-800 p-6 rounded-2xl">
        <div class="flex items-center gap-4">
          <div v-if="isSubmitted" class="flex items-center gap-2 text-slate-500 text-sm italic">
            <SaveIcon class="h-4 w-4" />
            Attendance locked for this session
          </div>
          <div v-else class="text-sm text-slate-400">
            <span class="font-bold text-white">{{ attendanceStats.pending }}</span> students remaining to mark
          </div>
        </div>
        
        <div class="flex gap-4">
          <button 
            @click="resetAttendance"
            class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl transition-all"
          >
            Reset Form
          </button>
          <button 
            @click="submitAttendance"
            :disabled="isSubmitted || attendanceStats.pending > 0 || saving"
            class="flex items-center gap-2 px-8 py-3 bg-pnc-blue hover:bg-blue-600 disabled:bg-slate-800 disabled:text-slate-600 text-white font-bold rounded-xl shadow-lg shadow-pnc-blue/20 transition-all active:scale-[0.98]"
          >
            <SaveIcon class="h-5 w-5" />
            {{ saving ? 'Saving...' : 'Submit Attendance' }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
