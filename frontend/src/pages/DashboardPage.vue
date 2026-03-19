<script setup>
import { computed, onMounted, ref } from 'vue'
import { buildStudentDashboardSummary } from '../services/api'
import { getStudentPortalData } from '../services/studentPortalService'

const stats = ref(null)
const history = ref([])
const loading = ref(true)
const error = ref(null)

const dashboard = computed(() => buildStudentDashboardSummary(stats.value, history.value))
const recentRecords = computed(() => history.value.slice(0, 5))

const fetchDashboard = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await getStudentPortalData()
    stats.value = data.stats
    history.value = data.history
  } catch (err) {
    console.error('Failed to load dashboard:', err)
    error.value = 'Unable to load your dashboard right now.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchDashboard)
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Student Dashboard</h1>
            <p class="text-gray-600 mt-1">Live stats from your attendance data.</p>
          </div>
          <button
            class="text-sm text-blue-600 hover:text-blue-700 underline disabled:opacity-50"
            :disabled="loading"
            @click="fetchDashboard"
          >
            Refresh
          </button>
        </div>

        <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-700">
          {{ error }}
        </div>

        <div v-if="loading" class="flex items-center gap-3 text-gray-600">
          <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
          Loading dashboard...
        </div>

        <template v-else>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="p-4 rounded-xl border bg-gray-50">
              <p class="text-sm text-gray-500">Monthly Rate</p>
              <p class="text-2xl font-bold text-gray-900">{{ dashboard.monthlyPercentage }}%</p>
            </div>
            <div class="p-4 rounded-xl border bg-gray-50">
              <p class="text-sm text-gray-500">Present</p>
              <p class="text-2xl font-bold text-gray-900">{{ dashboard.presentCount }}</p>
            </div>
            <div class="p-4 rounded-xl border bg-gray-50">
              <p class="text-sm text-gray-500">Late</p>
              <p class="text-2xl font-bold text-gray-900">{{ dashboard.lateCount }}</p>
            </div>
            <div class="p-4 rounded-xl border bg-gray-50">
              <p class="text-sm text-gray-500">Absences</p>
              <p class="text-2xl font-bold text-gray-900">{{ dashboard.absencesCount }}</p>
            </div>
          </div>

          <div>
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Recent Attendance</h2>
            <div v-if="recentRecords.length === 0" class="text-gray-500 text-sm">No records yet.</div>
            <div v-else class="space-y-2">
              <div
                v-for="record in recentRecords"
                :key="record.id"
                class="flex items-center justify-between p-3 border rounded-lg"
              >
                <div>
                  <p class="font-medium text-gray-900">{{ record.courseName }}</p>
                  <p class="text-xs text-gray-500">{{ record.date }} • {{ record.timeSlot || 'N/A' }}</p>
                </div>
                <span
                  class="px-2 py-1 rounded text-xs font-semibold uppercase"
                  :class="{
                    'bg-green-100 text-green-700': record.status === 'PRESENT',
                    'bg-amber-100 text-amber-700': record.status === 'LATE',
                    'bg-red-100 text-red-700': record.status === 'ABSENT',
                  }"
                >
                  {{ record.status }}
                </span>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
