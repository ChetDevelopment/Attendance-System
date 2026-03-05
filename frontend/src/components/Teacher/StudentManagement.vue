<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import Modal from './Modal.vue'
import { Search, QrCode, Printer, LayoutGrid, List } from 'lucide-vue-next'
import { cn } from '../lib/utils'
import { teacherService } from '../../services/teacherService'

type Student = {
  id: number
  name: string
  student_code: string
  class?: string
  contact?: string
  avatar?: string
  role?: string
}

const students = ref<Student[]>([])
const loading = ref(false)
const errorMessage = ref('')

const isPreviewModalOpen = ref(false)
const selectedStudent = ref<Student | null>(null)
const searchQuery = ref('')
const classFilter = ref('All Classes')
const viewMode = ref<'table' | 'grid'>('table')

const loadStudents = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await teacherService.getStudents()
    students.value = Array.isArray(data) ? data : []
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load students.'
  } finally {
    loading.value = false
  }
}

const classOptions = computed(() => {
  const set = new Set<string>()
  students.value.forEach((s) => {
    if (s.class) set.add(s.class)
  })
  return ['All Classes', ...Array.from(set).sort((a, b) => a.localeCompare(b))]
})

const filteredStudents = computed(() =>
  students.value.filter((s) => {
    const matchesSearch =
      String(s.name || '').toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      String(s.student_code || '').toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesClass = classFilter.value === 'All Classes' || s.class === classFilter.value
    return matchesSearch && matchesClass
  })
)

const generationOf = (code: string) => {
  const raw = String(code || '').toUpperCase()
  const matched = raw.match(/PNC\d{4}/)
  if (matched) return matched[0]

  const year = raw.match(/\d{4}/)
  if (year) return `PNC${year[0]}`

  return 'PNC2026'
}
const roleOf = (student: Student | null) => String(student?.role || 'student').toUpperCase()

const handlePrint = () => {
  window.print()
}

onMounted(loadStudents)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Student Management</h2>
        <p class="text-sm text-slate-500 font-medium">View and manage student identification</p>
      </div>
      <button class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm font-bold" :disabled="loading" @click="loadStudents">
        Refresh
      </button>
    </div>

    <p v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm">{{ errorMessage }}</p>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <div class="relative max-w-xs w-full">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
            <input
              type="text"
              placeholder="Search students..."
              v-model="searchQuery"
              class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
            />
          </div>
          <select
            v-model="classFilter"
            class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/20 min-w-[140px]"
          >
            <option v-for="cls in classOptions" :key="cls">{{ cls }}</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <button @click="handlePrint" class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 transition-all" title="Print List">
            <Printer class="size-4 text-slate-500" />
          </button>
          <div class="flex items-center bg-slate-100 p-1 rounded-lg border border-slate-200">
            <button
              @click="viewMode = 'table'"
              :class="cn('p-1.5 rounded-md transition-all', viewMode === 'table' ? 'bg-white shadow-sm text-primary' : 'text-slate-400 hover:text-slate-600')"
            >
              <List class="size-4" />
            </button>
            <button
              @click="viewMode = 'grid'"
              :class="cn('p-1.5 rounded-md transition-all', viewMode === 'grid' ? 'bg-white shadow-sm text-primary' : 'text-slate-400 hover:text-slate-600')"
            >
              <LayoutGrid class="size-4" />
            </button>
          </div>
        </div>
      </div>

      <div v-if="loading" class="p-10 text-center text-sm text-slate-500">Loading students...</div>

      <div v-else-if="viewMode === 'table'" class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider">
            <tr>
              <th class="px-6 py-4">Student</th>
              <th class="px-6 py-4">Class</th>
              <th class="px-6 py-4">Parent Contact</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="s in filteredStudents" :key="s.id" class="hover:bg-slate-50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="size-8 rounded-full bg-slate-100 overflow-hidden">
                    <img v-if="s.avatar" :src="s.avatar" alt="" class="w-full h-full object-cover" />
                  </div>
                  <div>
                    <div class="font-bold text-slate-900">{{ s.name }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">{{ s.student_code }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 font-medium text-slate-600">{{ s.class || '-' }}</td>
              <td class="px-6 py-4 text-slate-500">{{ s.contact || '-' }}</td>
              <td class="px-6 py-4 text-right">
                <button @click="selectedStudent = s; isPreviewModalOpen = true" class="p-2 text-slate-400 hover:text-primary hover:bg-primary/5 rounded-lg" title="ID Preview">
                  <QrCode class="size-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 bg-slate-50/30">
        <div v-for="s in filteredStudents" :key="s.id" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
          <div class="flex flex-col items-center text-center space-y-4">
            <div class="size-24 rounded-full border-4 border-slate-50 p-1 bg-white shadow-inner overflow-hidden">
              <img v-if="s.avatar" :src="s.avatar" alt="" class="w-full h-full object-cover rounded-full" />
            </div>
            <div>
              <h4 class="font-bold text-slate-900">{{ s.name }}</h4>
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ s.student_code }}</p>
            </div>
            <div class="flex items-center gap-2 w-full">
              <div class="flex-1 px-3 py-1.5 bg-slate-50 rounded-lg text-[10px] font-bold text-slate-600 border border-slate-100">
                CLASS {{ s.class || '-' }}
              </div>
              <button @click="selectedStudent = s; isPreviewModalOpen = true" class="p-1.5 bg-primary/10 text-primary rounded-lg hover:bg-primary/20 transition-colors">
                <QrCode class="size-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!loading && filteredStudents.length === 0" class="p-12 text-center">
        <div class="size-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
          <Search class="size-8 text-slate-300" />
        </div>
        <p class="text-slate-400 italic">No students found matching your criteria.</p>
      </div>
    </div>

    <Modal :isOpen="isPreviewModalOpen" @close="isPreviewModalOpen = false" title="Student ID Card" size="sm">
      <div v-if="selectedStudent" class="space-y-6 flex flex-col items-center">
        <div class="w-[280px] h-[400px] bg-white rounded-xl shadow-2xl overflow-hidden relative border border-slate-200 flex flex-col items-center p-6 text-slate-900 shrink-0">
          <svg class="absolute inset-0 w-full h-full opacity-10 pointer-events-none" viewBox="0 0 100 100" preserveAspectRatio="none">
            <path d="M0 20 L20 20 L30 10 M80 0 L80 20 L100 40 M0 80 L20 80 L40 100" stroke="currentColor" fill="none" strokeWidth="0.5" />
            <circle cx="20" cy="20" r="1" fill="currentColor" />
            <circle cx="30" cy="10" r="1" fill="currentColor" />
            <circle cx="80" cy="20" r="1" fill="currentColor" />
          </svg>
          <div class="w-full relative flex items-start z-10">
            <div class="flex flex-col items-center">
              <div class="size-10 bg-slate-900 rounded-full flex items-center justify-center text-white font-black text-[12px] tracking-tight relative">
                PNC
                <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-6 h-1 bg-sky-500"></div>
              </div>
            </div>
            <p class="absolute left-1/2 -translate-x-1/2 text-xl font-black text-slate-800 tracking-tighter">
              {{ generationOf(selectedStudent.student_code) }}
            </p>
          </div>
          <div class="mt-8 relative z-10">
            <div class="size-32 rounded-full border-4 border-sky-400 p-1 bg-white overflow-hidden">
              <div class="w-full h-full rounded-full overflow-hidden bg-slate-100">
                <img v-if="selectedStudent.avatar" :src="selectedStudent.avatar" alt="" class="w-full h-full object-cover" />
              </div>
            </div>
            <div class="absolute -inset-2 border-t-4 border-l-4 border-sky-500 rounded-full opacity-50"></div>
          </div>
          <div class="mt-8 text-center z-10 space-y-1">
            <h3 class="text-2xl font-black text-sky-900 leading-tight">{{ selectedStudent.name }}</h3>
            <p class="text-[10px] font-bold text-slate-500 tracking-[0.2em] uppercase">{{ roleOf(selectedStudent) }}</p>
          </div>
          <div class="mt-auto z-10 pb-2">
            <p class="text-base font-bold text-slate-800 tracking-tight">
              ID NB: <span class="font-black">{{ selectedStudent.student_code }}</span>
            </p>
          </div>
        </div>
        <button class="w-full flex items-center justify-center gap-2 py-3 bg-slate-900 text-white rounded-xl font-bold text-sm shadow-xl hover:bg-slate-800 transition-all">
          <Printer class="size-4" />
          Print ID Card
        </button>
      </div>
    </Modal>
  </div>
</template>
