<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Calendar, Users, UserX, BookOpen, ChevronRight, AlertCircle } from 'lucide-vue-next'
import { ViewType } from './Sidebar.vue'
import { teacherService } from '../../services/teacherService'

interface User {
  name: string
  role: 'teacher' | 'admin'
  department?: string
  photo?: string
}

const props = defineProps<{ user: User }>()
const emit = defineEmits<{ (e: 'navigate', view: ViewType): void }>()

const loading = ref(false)
const errorMessage = ref('')
const dashboard = ref<any>({
  today_classes: [],
  active: null,
  next_today: null,
  checked_in_count: 0,
  absent_count: 0,
})
const justifications = ref<any[]>([])

const loadDashboard = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const [dashboardData, justificationData] = await Promise.all([
      teacherService.getDashboard(),
      teacherService.getJustifications(),
    ])
    dashboard.value = dashboardData || dashboard.value
    justifications.value = (Array.isArray(justificationData) ? justificationData : []).slice(0, 2)
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load teacher dashboard.'
  } finally {
    loading.value = false
  }
}

const todayClasses = computed(() => (Array.isArray(dashboard.value.today_classes) ? dashboard.value.today_classes : []))

onMounted(loadDashboard)
</script>

<template>
  <div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h2 class="text-3xl font-black tracking-tight text-slate-900">Welcome, {{ props.user.name }}</h2>
        <p class="text-slate-500 font-medium">Live data from teacher backend endpoints</p>
      </div>
      <button
        class="px-6 py-3 bg-primary text-white rounded-xl font-bold text-sm shadow-xl shadow-primary/20 hover:bg-primary/90 transition-all disabled:opacity-60"
        :disabled="loading"
        @click="loadDashboard"
      >
        {{ loading ? 'Refreshing...' : 'Refresh Dashboard' }}
      </button>
    </div>

    <p v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm">{{ errorMessage }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-4">
          <div class="p-3 rounded-xl bg-blue-50"><Calendar class="size-6 text-blue-600" /></div>
          <div class="text-right">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">My Classes Today</p>
            <h3 class="text-2xl font-black text-slate-900">{{ todayClasses.length }}</h3>
          </div>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-4">
          <div class="p-3 rounded-xl bg-indigo-50"><BookOpen class="size-6 text-indigo-600" /></div>
          <div class="text-right">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Current Session</p>
            <h3 class="text-2xl font-black text-slate-900">{{ dashboard.active?.subject || 'None' }}</h3>
          </div>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-4">
          <div class="p-3 rounded-xl bg-green-50"><Users class="size-6 text-green-600" /></div>
          <div class="text-right">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Checked-in Count</p>
            <h3 class="text-2xl font-black text-slate-900">{{ dashboard.checked_in_count || 0 }}</h3>
          </div>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start mb-4">
          <div class="p-3 rounded-xl bg-red-50"><UserX class="size-6 text-red-600" /></div>
          <div class="text-right">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Absent Count</p>
            <h3 class="text-2xl font-black text-slate-900">{{ dashboard.absent_count || 0 }}</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 space-y-6">
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-900">Today's Schedule</h3>
            <button @click="emit('navigate', 'schedule')" class="text-xs font-bold text-primary hover:underline">View Full Calendar</button>
          </div>
          <div class="space-y-3">
            <div v-for="cls in todayClasses" :key="cls.id" class="bg-white p-5 rounded-2xl border border-slate-200 flex items-center justify-between">
              <div>
                <h4 class="font-bold text-slate-900">{{ cls.subject }}</h4>
                <p class="text-xs text-slate-500">{{ cls.classCode }} | {{ cls.start_time }} - {{ cls.end_time }}</p>
              </div>
              <ChevronRight class="size-5 text-slate-300" />
            </div>
            <div v-if="!loading && todayClasses.length === 0" class="bg-slate-50 border border-dashed border-slate-300 rounded-2xl p-12 text-center">
              <AlertCircle class="size-10 text-slate-300 mx-auto mb-3" />
              <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">No classes found for today</p>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-black text-slate-900">Absence Justifications</h3>
            <button @click="emit('navigate', 'messages')" class="text-xs font-bold text-primary hover:underline">View All Reports</button>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="j in justifications" :key="j.id" class="bg-white p-5 rounded-2xl border border-slate-200">
              <div class="flex items-center gap-3 mb-4">
                <img v-if="j.studentPhoto" :src="j.studentPhoto" class="size-10 rounded-xl object-cover" alt="" referrerPolicy="no-referrer" />
                <div v-else class="size-10 rounded-xl bg-slate-100" />
                <div>
                  <p class="text-sm font-bold text-slate-900">{{ j.studentName }}</p>
                  <p class="text-[10px] font-bold text-primary uppercase tracking-widest">{{ j.classCode }}</p>
                </div>
              </div>
              <p class="text-xs text-slate-600 italic line-clamp-2">"{{ j.educationComment }}"</p>
            </div>
            <div v-if="!loading && justifications.length === 0" class="md:col-span-2 bg-slate-50 border border-dashed border-slate-300 rounded-2xl p-8 text-center">
              <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">No absence reports available</p>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
          <h3 class="text-lg font-black text-slate-900 mb-3">Session Status</h3>
          <p class="text-sm text-slate-600">
            {{ dashboard.active ? `Active: ${dashboard.active.subject}` : 'No active session at the moment.' }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
