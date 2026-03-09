<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Search, Bell, ShieldCheck, MessageSquare } from 'lucide-vue-next'
import { cn } from '../lib/utils'
import { teacherService } from '../../services/teacherService'

interface User {
  name: string
  role: 'teacher' | 'admin'
  department?: string
  photo?: string
}

defineProps<{ user: User }>()

const justifications = ref<any[]>([])
const selectedId = ref<string | null>(null)
const searchQuery = ref('')
const loading = ref(false)
const errorMessage = ref('')

const loadJustifications = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const data = await teacherService.getJustifications()
    justifications.value = Array.isArray(data) ? data : []
    selectedId.value = justifications.value[0]?.id || null
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load reports.'
  } finally {
    loading.value = false
  }
}

const activeJustification = computed(() => justifications.value.find((j) => j.id === selectedId.value))
const filteredJustifications = computed(() =>
  justifications.value.filter((j) => {
    const q = searchQuery.value.toLowerCase()
    return (
      String(j.studentName || '').toLowerCase().includes(q) ||
      String(j.classCode || '').toLowerCase().includes(q) ||
      String(j.subject || '').toLowerCase().includes(q)
    )
  })
)

onMounted(loadJustifications)
</script>

<template>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex h-[calc(100vh-180px)]">
    <div class="w-96 border-r border-slate-100 flex flex-col bg-slate-50/30">
      <div class="p-6 border-b border-slate-100 bg-white">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-black text-slate-900 tracking-tight">Absence Reports</h2>
          <span class="px-2 py-1 bg-primary/10 text-primary text-[10px] font-black rounded-lg uppercase">
            {{ justifications.length }} Reports
          </span>
        </div>
        <div class="relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
          <input
            type="text"
            placeholder="Search by student or class..."
            v-model="searchQuery"
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/10 transition-all"
          />
        </div>
      </div>

      <div class="flex-1 overflow-y-auto">
        <div v-if="loading" class="p-6 text-sm text-slate-500">Loading reports...</div>
        <template v-else-if="filteredJustifications.length > 0">
          <button
            v-for="j in filteredJustifications"
            :key="j.id"
            @click="selectedId = j.id"
            :class="cn(
              'w-full p-5 flex items-start gap-4 hover:bg-white transition-all border-b border-slate-100 text-left relative group',
              selectedId === j.id ? 'bg-white shadow-sm z-10' : ''
            )"
          >
            <div v-if="selectedId === j.id" class="absolute left-0 top-0 bottom-0 w-1 bg-primary" />
            <img v-if="j.studentPhoto" :src="j.studentPhoto" alt="" class="size-12 rounded-2xl object-cover border border-slate-200 shrink-0" referrerPolicy="no-referrer" />
            <div v-else class="size-12 rounded-2xl bg-slate-100 border border-slate-200 shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="font-bold text-sm text-slate-900 truncate">{{ j.studentName }}</p>
              <p class="text-[10px] font-black text-primary uppercase tracking-widest mb-2">{{ j.classCode }} | {{ j.subject }}</p>
              <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">{{ j.educationComment }}</p>
            </div>
          </button>
        </template>
        <div v-else class="p-12 text-center space-y-3">
          <div class="size-16 bg-slate-100 rounded-3xl flex items-center justify-center mx-auto text-slate-300">
            <ShieldCheck :size="32" />
          </div>
          <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">No reports found</p>
        </div>
      </div>
    </div>

    <div class="flex-1 flex flex-col bg-white">
      <p v-if="errorMessage" class="m-6 p-3 rounded-lg bg-rose-50 text-rose-700 text-sm">{{ errorMessage }}</p>
      <div v-if="activeJustification" class="flex-1 flex flex-col">
        <div class="p-8 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-5">
            <img v-if="activeJustification.studentPhoto" :src="activeJustification.studentPhoto" alt="" class="size-16 rounded-[2rem] object-cover border-4 border-slate-50 shadow-sm" referrerPolicy="no-referrer" />
            <div v-else class="size-16 rounded-[2rem] bg-slate-100 border-4 border-slate-50 shadow-sm" />
            <div>
              <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ activeJustification.studentName }}</h3>
              <div class="flex items-center gap-3 mt-1">
                <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-[10px] font-black rounded uppercase">ID: {{ activeJustification.studentId }}</span>
                <span class="px-2 py-0.5 bg-primary/10 text-primary text-[10px] font-black rounded uppercase">{{ activeJustification.classCode }}</span>
              </div>
            </div>
          </div>
          <div class="text-right">
            <div class="flex items-center gap-2 text-emerald-600 bg-emerald-50 px-4 py-2 rounded-xl border border-emerald-100">
              <ShieldCheck :size="18" />
              <span class="text-xs font-black uppercase tracking-widest">Verified</span>
            </div>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto p-10 space-y-8">
          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <div class="size-8 bg-primary text-white rounded-lg flex items-center justify-center">
                <MessageSquare :size="16" />
              </div>
              <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest">Education Comment</h4>
            </div>
            <div class="bg-primary/5 border border-primary/10 rounded-3xl p-8">
              <p class="text-lg text-slate-700 font-medium leading-relaxed italic">
                "{{ activeJustification.educationComment || 'No comment provided.' }}"
              </p>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="flex-1 flex flex-col items-center justify-center p-12 text-center space-y-6">
        <div class="size-32 bg-slate-50 rounded-[3rem] flex items-center justify-center text-slate-200">
          <Bell :size="64" />
        </div>
        <div class="max-w-xs space-y-2">
          <h3 class="text-xl font-black text-slate-900 tracking-tight">Select a Report</h3>
          <p class="text-sm text-slate-400 font-medium">Choose an absence justification from the sidebar.</p>
        </div>
      </div>
    </div>
  </div>
</template>
