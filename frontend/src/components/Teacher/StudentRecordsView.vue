<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Search, Filter, Mail, Phone } from 'lucide-vue-next'
import { teacherService } from '../../services/teacherService'

const searchQuery = ref('')
const students = ref<any[]>([])
const loading = ref(false)
const errorMessage = ref('')

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

const filteredStudents = computed(() =>
  students.value.filter((s) => {
    const q = searchQuery.value.toLowerCase()
    return (
      String(s.name || '').toLowerCase().includes(q) ||
      String(s.student_code || '').toLowerCase().includes(q)
    )
  })
)

onMounted(loadStudents)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <h2 class="text-2xl font-bold">Student Records</h2>
      <div class="flex gap-2">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
          <input
            type="text"
            placeholder="Search students..."
            v-model="searchQuery"
            class="pl-9 pr-4 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-primary/20 outline-none w-64"
          />
        </div>
        <button class="p-2 bg-white text-slate-600 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors flex items-center gap-2 px-4">
          <Filter :size="16" />
          <span class="text-sm font-medium">Filter</span>
        </button>
      </div>
    </div>

    <p v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm">{{ errorMessage }}</p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-if="loading" class="col-span-full text-center text-slate-500 py-10">Loading students...</div>
      <div v-for="student in filteredStudents" :key="student.id" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-4">
          <img
            v-if="student.avatar"
            :src="student.avatar"
            alt=""
            class="size-16 rounded-2xl object-cover bg-slate-100 border-2 border-white shadow-sm"
            referrerPolicy="no-referrer"
          />
          <div v-else class="size-16 rounded-2xl bg-slate-100 border-2 border-white shadow-sm" />
        </div>

        <div class="space-y-1 mb-6">
          <h3 class="font-bold text-lg">{{ student.name }}</h3>
          <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">{{ student.student_code }}</p>
        </div>

        <div class="space-y-3 pt-4 border-t border-slate-50">
          <div class="flex items-center gap-3 text-slate-500">
            <Mail :size="14" />
            <span class="text-xs">{{ student.email || '-' }}</span>
          </div>
          <div class="flex items-center gap-3 text-slate-500">
            <Phone :size="14" />
            <span class="text-xs">{{ student.contact || '-' }}</span>
          </div>
        </div>
      </div>
      <div v-if="!loading && filteredStudents.length === 0" class="col-span-full text-center text-slate-400 py-10">No students found.</div>
    </div>
  </div>
</template>
