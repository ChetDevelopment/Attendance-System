<script setup lang="ts">
import { ref, computed } from 'vue'
import { Download, Calendar, ChevronDown, User, Clock, AlertCircle } from 'lucide-vue-next'

// Props
interface Props {
  students?: StudentAttendance[]
  isLoading?: boolean
  isExporting?: boolean
  academicYears?: { id: string; name: string }[]
  classes?: { id: string; name: string }[]
}

const props = withDefaults(defineProps<Props>(), {
  students: () => [],
  isLoading: false,
  isExporting: false,
  academicYears: () => [
    { id: '2025-2026', name: 'Academic Year 2025-2026' },
    { id: '2024-2025', name: 'Academic Year 2024-2025' },
  ],
  classes: () => [
    { id: '1', name: 'Web A' },
    { id: '2', name: 'Web B' },
    { id: '3', name: 'Mobile A' },
    { id: '4', name: 'Mobile B' },
  ],
})

const emit = defineEmits<{
  (e: 'export'): void
  (e: 'update:period', value: string): void
  (e: 'update:academicYear', value: string): void
  (e: 'update:classId', value: string): void
}>()

// Period toggle
const periods = ['Today', 'Weekly', 'Monthly']
const activePeriod = ref('Today')

// Dropdown options - will be passed from parent or use defaults
const selectedAcademicYear = ref('')
const selectedClass = ref('')

// Methods
const setPeriod = (period: string) => {
  activePeriod.value = period
  emit('update:period', period)
}

const onAcademicYearChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  selectedAcademicYear.value = target.value
  emit('update:academicYear', target.value)
}

const onClassChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  selectedClass.value = target.value
  emit('update:classId', target.value)
}

// Format student code
const formatStudentCode = (code: string | undefined) => {
  return code || 'N/A'
}

// Get late count
const getLateCount = (student: StudentAttendance) => {
  return student.late_count || 0
}

// Get absent count
const getAbsentCount = (student: StudentAttendance) => {
  return student.absent_count || 0
}

// Student type
interface StudentAttendance {
  no?: number
  photo?: string
  name: string
  code: string
  late_count?: number
  absent_count?: number
}
</script>

<template>
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <!-- Header Section -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <!-- Title -->
      <h3 class="text-lg font-black text-slate-900">Class Attendance Report</h3>
      
      <!-- Period Toggle -->
      <div class="flex rounded-xl bg-slate-100 p-1">
        <button
          v-for="period in periods"
          :key="period"
          @click="setPeriod(period)"
          :class="[
            'px-4 py-2 text-xs font-bold rounded-lg transition-all',
            activePeriod === period
              ? 'bg-white text-primary shadow-sm'
              : 'text-slate-500 hover:text-slate-700'
          ]"
        >
          {{ period }}
        </button>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row">
      <!-- Academic Year Dropdown -->
      <div class="relative flex-1">
        <select
          :value="selectedAcademicYear"
          @change="onAcademicYearChange"
          class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 pr-10 text-sm font-medium text-slate-700 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
        >
          <option v-for="year in props.academicYears" :key="year.id" :value="year.id">
            {{ year.name }}
          </option>
        </select>
        <ChevronDown class="absolute right-3 top-1/2 size-4 -translate-y-1/2 text-slate-400 pointer-events-none" />
      </div>

      <!-- Class Dropdown -->
      <div class="relative flex-1">
        <select
          :value="selectedClass"
          @change="onClassChange"
          class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 pr-10 text-sm font-medium text-slate-700 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
        >
          <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
            {{ cls.name }}
          </option>
        </select>
        <ChevronDown class="absolute right-3 top-1/2 size-4 -translate-y-1/2 text-slate-400 pointer-events-none" />
      </div>

      <!-- Export CSV Button -->
      <button
        @click="emit('export')"
        :disabled="isExporting"
        class="flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary/20 transition-all hover:bg-primary/95 disabled:cursor-not-allowed disabled:opacity-60"
      >
        <Download :size="18" />
        {{ isExporting ? 'Exporting...' : 'Export CSV' }}
      </button>
    </div>

    <!-- Data Table -->
    <div class="overflow-x-auto rounded-xl border border-slate-100">
      <table class="w-full text-left">
        <thead>
          <tr class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
            <th class="px-4 py-4 text-center">No</th>
            <th class="px-4 py-4">Photos</th>
            <th class="px-4 py-4">Student Name</th>
            <th class="px-4 py-4">Code</th>
            <th class="px-4 py-4 text-center">Late</th>
            <th class="px-4 py-4 text-center">Absent</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <!-- Loading State -->
          <tr v-if="isLoading">
            <td colspan="6" class="px-4 py-12 text-center">
              <div class="flex flex-col items-center gap-2">
                <div class="size-6 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
                <span class="text-sm font-medium text-slate-400">Loading students...</span>
              </div>
            </td>
          </tr>
          
          <!-- Empty State -->
          <tr v-else-if="students.length === 0">
            <td colspan="6" class="px-4 py-12 text-center">
              <div class="flex flex-col items-center gap-2">
                <User :size="32" class="text-slate-300" />
                <span class="text-sm font-medium text-slate-400">No students found</span>
              </div>
            </td>
          </tr>
          
          <!-- Data Rows -->
          <tr 
            v-for="(student, index) in students" 
            :key="student.code || index"
            class="transition-colors hover:bg-slate-50/60"
          >
            <!-- No -->
            <td class="px-4 py-4 text-center">
              <span class="text-sm font-bold text-slate-400">{{ student.no || index + 1 }}</span>
            </td>
            
            <!-- Photo (Circular Avatar) -->
            <td class="px-4 py-4">
              <div class="relative flex size-10 items-center justify-center overflow-hidden rounded-full bg-slate-100 ring-2 ring-slate-100">
                <img 
                  v-if="student.photo" 
                  :src="student.photo" 
                  :alt="student.name"
                  class="size-full object-cover"
                />
                <User v-else :size="20" class="text-slate-400" />
              </div>
            </td>
            
            <!-- Student Name -->
            <td class="px-4 py-4">
              <span class="text-sm font-bold text-slate-900">{{ student.name }}</span>
            </td>
            
            <!-- Code -->
            <td class="px-4 py-4">
              <span class="text-sm font-medium text-slate-500">{{ formatStudentCode(student.code) }}</span>
            </td>
            
            <!-- Late -->
            <td class="px-4 py-4 text-center">
              <span 
                :class="[
                  'inline-flex items-center justify-center rounded-lg px-2 py-1 text-xs font-bold',
                  getLateCount(student) > 0 
                    ? 'bg-orange-100 text-orange-700' 
                    : 'bg-slate-100 text-slate-400'
                ]"
              >
                <Clock v-if="getLateCount(student) > 0" :size="12" class="mr-1" />
                {{ getLateCount(student) }}
              </span>
            </td>
            
            <!-- Absent -->
            <td class="px-4 py-4 text-center">
              <span 
                :class="[
                  'inline-flex items-center justify-center rounded-lg px-2 py-1 text-xs font-bold',
                  getAbsentCount(student) > 0 
                    ? 'bg-rose-100 text-rose-700' 
                    : 'bg-slate-100 text-slate-400'
                ]"
              >
                <AlertCircle v-if="getAbsentCount(student) > 0" :size="12" class="mr-1" />
                {{ getAbsentCount(student) }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
