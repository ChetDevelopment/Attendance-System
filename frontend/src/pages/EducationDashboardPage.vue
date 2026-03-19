<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { UserMinus, Clock, FileText, AlertTriangle } from 'lucide-vue-next'
import api from '../services/api'
import Sidebar from '../components/Education/Sidebar.vue'
import Header from '../components/Education/Header.vue'
import StatsCard from '../components/Education/StatsCard.vue'
import TrendsChart from '../components/Education/TrendsChart.vue'
import AttendanceTable from '../components/Education/AttendanceTable.vue'
import RiskStudents from '../components/Education/RiskStudents.vue'
import FollowUpModal from '../components/Education/FollowUpModal.vue'
import ReportsTable from '../components/Education/ReportsTable.vue'
import ProfileView from '../components/Education/ProfileView.vue'
import AccountSettingsView from '../components/Education/AccountSettingsView.vue'
import { DashboardStats, TrendData, ClassReport } from '../components/Education/types'

const activeNav = ref('Dashboard')
const theme = ref('light')
const dashboardStats = ref<DashboardStats>({
  absentToday: 0,
  lateToday: 0,
  highRisk: 0,
  pendingFollowUp: 0,
})
const absentToday = ref<any[]>([])
const allAbsent = ref<any[]>([])
const riskStudents = ref<any[]>([])
const classReports = ref<ClassReport[]>([])
const trendData = ref<TrendData[]>([])
const isLoading = ref(true)
const errorMessage = ref('')
const selectedAttendance = ref<any>(null)
const isModalOpen = ref(false)
const isNotificationOpen = ref(false)
const isSettingsOpen = ref(false)
const isProfileOpen = ref(false)

const followUpForm = ref({
  reason: '',
  comment: '',
  note: '',
  actionTaken: '',
  resolved: false,
  isExcused: false,
  status: 'Not Contacted',
})

const fetchJson = async (url: string) => {
  const { data } = await api.get(url.replace('/api', ''))
  return data
}

const fetchUserProfile = async () => {
  try {
    const data = await fetchJson('/api/user/profile')
    if (data.theme) theme.value = data.theme
  } catch {
    theme.value = 'light'
  }
}

const fetchData = async () => {
  isLoading.value = true
  errorMessage.value = ''
  const results = await Promise.allSettled([
    fetchJson('/api/education/dashboard/stats'),
    fetchJson('/api/education/students/absent-today'),
    fetchJson('/api/education/students/all-absent'),
    fetchJson('/api/education/students/risk'),
    fetchJson('/api/education/reports/class-summary'),
    fetchJson('/api/admin/reports/trends'),
  ])

  const [stats, today, all, risk, reports, trends] = results

  dashboardStats.value = stats.status === 'fulfilled' ? stats.value : {
    absentToday: 0,
    lateToday: 0,
    highRisk: 0,
    pendingFollowUp: 0,
  }
  absentToday.value = today.status === 'fulfilled' ? today.value : []
  allAbsent.value = all.status === 'fulfilled' ? all.value : []
  riskStudents.value = risk.status === 'fulfilled' ? risk.value : []
  classReports.value = reports.status === 'fulfilled' ? reports.value : []
  trendData.value = trends.status === 'fulfilled' ? trends.value : []

  const failed = results.some((r) => r.status === 'rejected')
  if (failed) {
    errorMessage.value = 'Some data could not be loaded from server. Showing available data only.'
  }

  isLoading.value = false
}

const handleOpenDetail = async (attendanceId: number) => {
  try {
    const data = await fetchJson(`/api/education/attendance/detail/${attendanceId}`)
    selectedAttendance.value = data
    followUpForm.value = {
      reason: data.reason || '',
      comment: '',
      note: '',
      actionTaken: '',
      resolved: data.followUps?.[0]?.resolved === 1,
      isExcused: data.is_excused === 1,
      status: data.followUps?.[0]?.status || 'Not Contacted',
    }
    isModalOpen.value = true
  } catch {
    errorMessage.value = 'Failed to load attendance detail from server.'
  }
}

const handleSubmitFollowUp = async () => {
  try {
    const { status } = await api.post('/education/attendance/follow-up', {
      attendanceId: selectedAttendance.value.id,
      ...followUpForm.value,
      updatedBy: 'Education Team',
    })
    if (status < 200 || status >= 300) throw new Error('Failed to save follow-up')

    alert('Follow-up saved successfully!')
    isModalOpen.value = false
    fetchData()
  } catch {
    alert('Failed to save follow-up.')
  }
}

const handleSendAlert = async () => {
  try {
    const { data } = await api.post('/education/attendance/alert', {
      attendanceId: selectedAttendance.value.id,
      studentName: selectedAttendance.value.name,
      className: selectedAttendance.value.class,
      date: selectedAttendance.value.date,
    })
    if (data.success) {
      alert('Alert sent successfully!')
      return
    }
    throw new Error('Alert failed')
  } catch {
    alert('Error sending alert.')
  }
}

const handleExportCSV = () => {
  const csv = [
    ['Class', 'Attendance %', 'Present', 'Absent', 'Late'],
    ...classReports.value.map((r) => {
      const total = r.present_count + r.absent_count + r.late_count
      const percentage = total > 0 ? Math.round((r.present_count / total) * 100) : 0
      return [r.class, `${percentage}%`, r.present_count, r.absent_count, r.late_count]
    }),
  ]
    .map((e) => e.join(','))
    .join('\n')

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.setAttribute('download', 'attendance_report.csv')
  link.click()
}

onMounted(() => {
  fetchData()
  fetchUserProfile()

  const params = new URLSearchParams(window.location.search)
  const attendanceId = params.get('attendanceId')
  if (attendanceId) handleOpenDetail(parseInt(attendanceId, 10))
})

watch(
  theme,
  (newTheme) => {
    if (newTheme === 'dark') {
      document.documentElement.classList.add('dark')
      return
    }
    document.documentElement.classList.remove('dark')
  },
  { immediate: true }
)
</script>

<template>
  <div class="flex min-h-screen bg-slate-50">
    <Sidebar v-model:activeNav="activeNav" />

    <main class="ml-64 flex-1 flex flex-col">
      <Header
        :isLoading="isLoading"
        @refresh="fetchData"
        v-model:isNotificationOpen="isNotificationOpen"
        v-model:isSettingsOpen="isSettingsOpen"
        v-model:isProfileOpen="isProfileOpen"
        @setActiveNav="(val) => (activeNav = val)"
      />

      <div class="p-8 max-w-[1600px] mx-auto w-full">
        <div v-if="errorMessage" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
          {{ errorMessage }}
        </div>
        <div v-if="activeNav === 'Dashboard'" class="space-y-8">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatsCard
              label="Today Absent"
              :value="dashboardStats.absentToday"
              :icon="UserMinus"
              color="text-[#135bec]"
              bgColor="bg-[#135bec]/10"
              trend="+2%"
              :trendUp="true"
            />
            <StatsCard
              label="Late Today"
              :value="dashboardStats.lateToday"
              :icon="Clock"
              color="text-orange-500"
              bgColor="bg-orange-500/10"
              trend="-1%"
              :trendUp="false"
            />
            <StatsCard
              label="Follow-up Pending"
              :value="dashboardStats.pendingFollowUp"
              :icon="FileText"
              color="text-blue-500"
              bgColor="bg-blue-500/10"
              badge="URGENT"
            />
            <StatsCard
              label="High Risk Students"
              :value="dashboardStats.highRisk"
              :icon="AlertTriangle"
              color="text-rose-600"
              bgColor="bg-rose-600/10"
              subtext="Absent 3+ times"
            />
          </div>

          <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <div class="xl:col-span-2 space-y-8">
              <TrendsChart :data="trendData" />
              <AttendanceTable
                title="Today's Absent Students"
                :data="absentToday"
                :isLoading="isLoading"
                @openDetail="handleOpenDetail"
                @viewAll="activeNav = 'Absence Follow-up'"
              />
            </div>
            <div>
              <RiskStudents
                :students="riskStudents"
                @viewAll="activeNav = 'Risk Monitoring'"
                @quickFollowUp="handleOpenDetail"
              />
            </div>
          </div>
        </div>

        <AttendanceTable
          v-else-if="activeNav === 'Absence Follow-up'"
          title="Absence Follow-up Module"
          :data="allAbsent"
          :isLoading="isLoading"
          @openDetail="handleOpenDetail"
          :showDate="true"
        />

        <ReportsTable v-else-if="activeNav === 'Reports'" :reports="classReports" @export="handleExportCSV" />

        <div v-else-if="activeNav === 'Risk Monitoring'" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
          <h3 class="font-bold text-lg text-slate-900 mb-6">Risk Students Monitoring</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="(student, i) in riskStudents" :key="i" class="p-6 rounded-2xl border border-rose-100 bg-rose-50/50 space-y-4">
              <div class="flex items-center gap-4">
                <div class="size-12 rounded-full bg-white border-2 border-rose-500 flex items-center justify-center text-slate-400">
                  <UserMinus :size="24" />
                </div>
                <div>
                  <h4 class="font-bold text-slate-900">{{ student.name }}</h4>
                  <p class="text-sm text-slate-500">{{ student.class }}</p>
                </div>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-rose-600 uppercase tracking-wider">High Absence Risk</span>
                <span class="text-lg font-bold text-rose-700">{{ student.absence_count }} Days</span>
              </div>
              <button
                @click="handleOpenDetail(student.latest_attendance_id)"
                class="w-full py-2 bg-white border border-rose-200 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-500 hover:text-white transition-all"
              >
                Quick Follow-up
              </button>
            </div>
          </div>
        </div>

        <ProfileView v-else-if="activeNav === 'My Profile'" />
        <AccountSettingsView v-else-if="activeNav === 'Account Settings'" />
      </div>
    </main>

    <FollowUpModal
      :isOpen="isModalOpen"
      @close="isModalOpen = false"
      :selectedAttendance="selectedAttendance"
      v-model:followUpForm="followUpForm"
      @submit="handleSubmitFollowUp"
      @sendAlert="handleSendAlert"
    />
  </div>
</template>
