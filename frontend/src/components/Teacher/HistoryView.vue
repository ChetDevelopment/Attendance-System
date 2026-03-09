<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Search, Filter } from 'lucide-vue-next'
import { teacherService } from '../../services/teacherService'

const searchQuery = ref('')
const history = ref<any[]>([])
const loading = ref(false)
const errorMessage = ref('')

const loadHistory = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await teacherService.getHistory()
    history.value = Array.isArray(data) ? data : []
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load history.'
  } finally {
    loading.value = false
  }
}

const filteredHistory = computed(() =>
  history.value.filter((h) => {
    const q = searchQuery.value.toLowerCase()
    return (
      String(h.subject || '').toLowerCase().includes(q) ||
      String(h.classCode || '').toLowerCase().includes(q)
    )
  })
)

onMounted(loadHistory)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <h2 class="text-2xl font-bold">Attendance History</h2>
      <div class="flex gap-2">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
          <input
            type="text"
            placeholder="Search history..."
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

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mt-6">
      <table class="w-full text-left">
        <thead class="bg-slate-50 text-slate-500 text-xs font-bold uppercase tracking-wider">
          <tr>
            <th class="px-6 py-4">Date</th>
            <th class="px-6 py-4">Class</th>
            <th class="px-6 py-4">Attendance Rate</th>
            <th class="px-6 py-4">Present/Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td :colspan="4" class="px-6 py-10 text-center text-slate-400 italic">Loading history...</td>
          </tr>
          <tr v-for="session in filteredHistory" :key="session.id" class="hover:bg-slate-50/50 transition-colors">
            <td class="px-6 py-4 text-sm font-medium">{{ session.date }}</td>
            <td class="px-6 py-4">
              <div>
                <p class="font-bold text-sm">{{ session.subject }}</p>
                <p class="text-xs text-slate-500">{{ session.classCode }}</p>
              </div>
            </td>
            <td class="px-6 py-4 text-sm font-bold">{{ session.attendanceRate }}%</td>
            <td class="px-6 py-4 text-sm text-slate-500">{{ session.presentCount }} / {{ session.totalStudents }}</td>
          </tr>
          <tr v-if="!loading && filteredHistory.length === 0">
            <td :colspan="4" class="px-6 py-10 text-center text-slate-400 italic">No history found.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
