<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { ChevronDown, Loader2, Calendar, Clock, User } from 'lucide-vue-next'
import { teacherService } from '../../services/teacherService'

interface User {
  name: string
  role: 'teacher' | 'admin'
  department?: string
  photo?: string
}

interface GoogleEvent {
  id: string
  summary: string
  description?: string
  start: {
    dateTime?: string
    date?: string
    timeZone?: string
  }
  end: {
    dateTime?: string
    date?: string
    timeZone?: string
  }
  location?: string
  creator?: {
    email: string
  }
}

defineProps<{ user: User }>()

const loading = ref(false)
const errorMessage = ref('')
const teachers = ref<any[]>([])
const sessions = ref<any[]>([])
const googleEvents = ref<GoogleEvent[]>([])
const selectedTeacherId = ref<string>('')
const showGoogleCalendar = ref(false)

const loadSchedule = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    // Load traditional schedule data
    const data = await teacherService.getSchedule()
    teachers.value = Array.isArray(data.teachers) ? data.teachers : []
    sessions.value = Array.isArray(data.sessions) ? data.sessions : []
    
    // Load Google Calendar events
    try {
      console.log('Attempting to load Google Calendar events...')
      const calendarData = await teacherService.getGoogleCalendarEvents()
      console.log('Google Calendar API response:', calendarData)
      
      if (calendarData && calendarData.items) {
        googleEvents.value = Array.isArray(calendarData.items) ? calendarData.items : []
        console.log('Successfully loaded', googleEvents.value.length, 'Google Calendar events')
      } else {
        console.warn('Google Calendar API returned unexpected data structure:', calendarData)
      }
    } catch (calendarError) {
      console.error('Failed to load Google Calendar events:', calendarError)
      const errorObj = calendarError as any
      console.error('Error details:', {
        message: errorObj?.message,
        status: errorObj?.response?.status,
        statusText: errorObj?.response?.statusText,
        data: errorObj?.response?.data
      })
    }
    
    if (!selectedTeacherId.value && teachers.value.length > 0) {
      selectedTeacherId.value = String(teachers.value[0].id)
    }
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load schedule.'
  } finally {
    loading.value = false
  }
}

const sortedSessions = computed(() =>
  [...sessions.value].sort((a, b) => String(a.start_time).localeCompare(String(b.start_time)))
)

const sortedGoogleEvents = computed(() => {
  return [...googleEvents.value].sort((a, b) => {
    const aStart = a.start.dateTime || a.start.date || ''
    const bStart = b.start.dateTime || b.start.date || ''
    return aStart.localeCompare(bStart)
  })
})

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    weekday: 'short', 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

const formatTime = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

const formatEventTime = (event: GoogleEvent) => {
  if (event.start.dateTime && event.end.dateTime) {
    return {
      start: formatTime(event.start.dateTime),
      end: formatTime(event.end.dateTime),
      date: formatDate(event.start.dateTime)
    }
  }
  return null
}

const isAllDayEvent = (event: GoogleEvent) => {
  return event.start.date && event.end.date
}

onMounted(loadSchedule)
</script>

<template>
  <div class="space-y-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm space-y-4">
      <div class="flex justify-center">
        <div class="relative w-full max-w-2xl">
          <select
            v-model="selectedTeacherId"
            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-sm appearance-none focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
          >
            <option v-for="teacher in teachers" :key="teacher.id" :value="String(teacher.id)">
              {{ teacher.name }}
            </option>
          </select>
          <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" :size="18" />
        </div>
      </div>
    </div>

    <p v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm">{{ errorMessage }}</p>

    <!-- Debug Section - Shows current Google Calendar status -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <h4 class="font-bold text-blue-900 mb-2">Google Calendar Status</h4>
      <p class="text-sm text-blue-700">Events loaded: {{ googleEvents.length }}</p>
      <p class="text-xs text-blue-600 mt-1">Check browser console for detailed API response and errors</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden relative min-h-[420px]">
      <div v-if="loading" class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-20 flex flex-col items-center justify-center gap-3">
        <Loader2 class="size-10 text-primary animate-spin" />
        <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Fetching Timetable...</p>
      </div>

      <div class="p-6 space-y-6">
        <!-- Traditional Sessions Section -->
        <div>
          <h3 class="text-xl font-black text-slate-900 mb-4">Traditional Sessions</h3>
          <div v-if="sortedSessions.length === 0 && !loading" class="text-sm text-slate-500">No sessions found.</div>
          <div v-for="session in sortedSessions" :key="session.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4 mb-3">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-bold text-slate-900">{{ session.name }}</p>
                <p class="text-xs text-slate-500">{{ session.start_time }} - {{ session.end_time }}</p>
              </div>
              <div class="flex items-center gap-2 text-xs text-slate-400">
                <Clock class="size-4" />
                <span>Local Schedule</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Google Calendar Events Section -->
        <div v-if="googleEvents.length > 0">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-black text-slate-900">Google Calendar Events</h3>
            <button 
              @click="showGoogleCalendar = !showGoogleCalendar"
              class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-sm font-medium hover:bg-blue-100 transition-colors"
            >
              <Calendar class="size-4" />
              {{ showGoogleCalendar ? 'Hide' : 'Show' }} Calendar
            </button>
          </div>
          
          <div v-if="showGoogleCalendar || googleEvents.length <= 5" class="space-y-3">
            <div 
              v-for="event in sortedGoogleEvents" 
              :key="event.id" 
              class="rounded-xl border border-slate-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-4"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-2">
                    <Calendar class="size-4 text-blue-600" />
                    <h4 class="font-semibold text-slate-900">{{ event.summary }}</h4>
                  </div>
                  
                  <div class="text-sm text-slate-600 space-y-1">
                    <div v-if="event.start.date" class="flex items-center gap-2">
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        All Day
                      </span>
                      <span>{{ formatDate(event.start.date) }}</span>
                    </div>
                    
                    <div v-else class="flex items-center gap-2">
                      <Clock class="size-4 text-slate-500" />
                      <span v-if="formatEventTime(event)" class="text-sm">
                        {{ formatEventTime(event)?.date }} • {{ formatEventTime(event)?.start }} - {{ formatEventTime(event)?.end }}
                      </span>
                    </div>
                    
                    <div v-if="event.location" class="flex items-center gap-2">
                      <User class="size-4 text-slate-500" />
                      <span>{{ event.location }}</span>
                    </div>
                    
                    <div v-if="event.description" class="text-xs text-slate-500 mt-1">
                      {{ event.description }}
                    </div>
                  </div>
                </div>
                
                <div v-if="event.creator" class="text-xs text-slate-400 mt-1">
                  by {{ event.creator.email }}
                </div>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8">
            <Calendar class="size-12 text-slate-300 mx-auto mb-4" />
            <p class="text-sm text-slate-500">Click "Show Calendar" to view all {{ googleEvents.length }} events</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
