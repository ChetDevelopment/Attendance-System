<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
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
import { getUser, setUser } from '../services/auth'

const route = useRoute()
const router = useRouter()
const EDUCATION_NAV_STORAGE_KEY = 'education_dashboard_active_nav'
const VALID_EDUCATION_NAVS = [
  'Dashboard',
  'Absence Follow-up',
  'Reports',
  'Risk Monitoring',
  'My Profile',
  'Account Settings',
]

const normalizeEducationNav = (value: unknown): string | null => {
  const raw = Array.isArray(value) ? value[0] : value

  if (typeof raw !== 'string') {
    return null
  }

  return VALID_EDUCATION_NAVS.includes(raw) ? raw : null
}

const getStoredEducationNav = () => {
  if (typeof window === 'undefined') {
    return null
  }

  return normalizeEducationNav(window.localStorage.getItem(EDUCATION_NAV_STORAGE_KEY))
}

const activeNav = ref(normalizeEducationNav(route.query.nav) ?? getStoredEducationNav() ?? 'Dashboard')
const theme = ref('light')
const currentUser = ref<any>(
  getUser() || {
    name: 'Education Team',
    role: 'education',
  },
)
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

const welcomeName = computed(() => currentUser.value?.name || 'Education Team')
const welcomeRole = computed(() => {
  const role = currentUser.value?.role

  if (typeof role === 'string' && role.trim()) {
    return role
  }

  if (role?.name) {
    return role.name
  }

  return 'Education Team'
})

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
    currentUser.value = data
    setUser(data)
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

  dashboardStats.value =
    stats.status === 'fulfilled'
      ? stats.value
      : {
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

  if (results.some((result) => result.status === 'rejected')) {
    errorMessage.value =
      'Some data could not be loaded from server. Showing available data only.'
  }

  isLoading.value = false
}

const handleOpenDetail = async (attendanceId: number) => {
  try {
    const data = await fetchJson(`/api/education/attendance/detail/${attendanceId}`)
    const latestFollowUp = Array.isArray(data.followUps) ? data.followUps[0] : null

    selectedAttendance.value = data
    followUpForm.value = {
      reason: data.reason || '',
      comment: '',
      note: '',
      actionTaken: '',
      resolved: Boolean(latestFollowUp?.resolved ?? data.resolved),
      isExcused: Boolean(data.is_excused),
      status: latestFollowUp?.status || data.status || 'Not Contacted',
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
    await fetchData()
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
    ...classReports.value.map((report) => {
      const total = report.present_count + report.absent_count + report.late_count
      const percentage = total > 0 ? Math.round((report.present_count / total) * 100) : 0
      return [
        report.class,
        `${percentage}%`,
        report.present_count,
        report.absent_count,
        report.late_count,
      ]
    }),
  ]
    .map((entry) => entry.join(','))
    .join('\n')

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.setAttribute('download', 'attendance_report.csv')
  link.click()
}

const persistActiveNav = (nav: string) => {
  if (typeof window !== 'undefined') {
    window.localStorage.setItem(EDUCATION_NAV_STORAGE_KEY, nav)
  }

  const routeNav = normalizeEducationNav(route.query.nav)
  if (routeNav !== nav) {
    router.replace({
      name: 'education-dashboard',
      query: {
        ...route.query,
        nav,
      },
    })
  }
}

onMounted(() => {
  fetchData()
  fetchUserProfile()
  persistActiveNav(activeNav.value)

  const params = new URLSearchParams(window.location.search)
  const attendanceId = params.get('attendanceId')
  if (attendanceId) handleOpenDetail(parseInt(attendanceId, 10))
})

watch(
  () => route.query.nav,
  (navFromRoute) => {
    const normalizedNav = normalizeEducationNav(navFromRoute)

    if (normalizedNav && normalizedNav !== activeNav.value) {
      activeNav.value = normalizedNav
      return
    }

    if (!normalizedNav) {
      persistActiveNav(activeNav.value)
    }
  },
)

watch(activeNav, (nav) => {
  persistActiveNav(nav)
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
  { immediate: true },
)
</script>

<template>
  <div class="flex min-h-screen bg-slate-50">
    <Sidebar v-model:activeNav="activeNav" :user="currentUser" />

    <main class="flex min-w-0 flex-1 flex-col overflow-hidden">
      <Header
        :user="currentUser"
        :isLoading="isLoading"
        @refresh="fetchData"
        v-model:isNotificationOpen="isNotificationOpen"
        v-model:isSettingsOpen="isSettingsOpen"
        v-model:isProfileOpen="isProfileOpen"
        @setActiveNav="(val) => (activeNav = val)"
      />

      <div class="flex-1 overflow-y-auto p-8">
        <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-8">
          <section class="rounded-3xl border border-slate-200 bg-white px-6 py-6 shadow-sm md:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
              <div class="space-y-2">
                <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-primary">
                  Education Dashboard
                </p>
                <h2 class="text-3xl font-black tracking-tight text-slate-900">
                  Welcome, {{ welcomeName }}
                </h2>
                <p class="max-w-2xl text-sm font-medium text-slate-500">
                  Track attendance exceptions, follow-up workload, and student risk
                  using the same streamlined workspace style as the Teacher portal.
                </p>
              </div>

              <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:min-w-[360px]">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                  <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">
                    Role
                  </p>
                  <p class="mt-2 text-sm font-bold text-slate-900">
                    {{ welcomeRole }}
                  </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                  <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">
                    Pending Actions
                  </p>
                  <p class="mt-2 text-sm font-bold text-slate-900">
                    {{ dashboardStats.pendingFollowUp }} cases to review
                  </p>
                </div>
              </div>
            </div>
          </section>

          <div
            v-if="errorMessage"
            class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
          >
            {{ errorMessage }}
          </div>

          <div v-if="activeNav === 'Dashboard'" class="space-y-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
              <StatsCard
                label="Today Absent"
                :value="dashboardStats.absentToday"
                :icon="UserMinus"
                color="text-blue-600"
                bgColor="bg-blue-50"
                trend="Needs review"
                :trendUp="true"
              />
              <StatsCard
                label="Late Today"
                :value="dashboardStats.lateToday"
                :icon="Clock"
                color="text-amber-600"
                bgColor="bg-amber-50"
                trend="Monitor trend"
                :trendUp="false"
              />
              <StatsCard
                label="Follow-up Pending"
                :value="dashboardStats.pendingFollowUp"
                :icon="FileText"
                color="text-indigo-600"
                bgColor="bg-indigo-50"
                badge="Open Cases"
              />
              <StatsCard
                label="High Risk Students"
                :value="dashboardStats.highRisk"
                :icon="AlertTriangle"
                color="text-rose-600"
                bgColor="bg-rose-50"
                subtext="Absent 3+ times"
              />
            </div>

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-3">
              <div class="space-y-8 xl:col-span-2">
                <TrendsChart :data="trendData" />
                <AttendanceTable
                  title="Today's Absent Students"
                  :data="absentToday"
                  :isLoading="isLoading"
                  @openDetail="handleOpenDetail"
                  @viewAll="activeNav = 'Absence Follow-up'"
                />
              </div>

              <div class="space-y-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                  <h3 class="text-lg font-black text-slate-900">Operations Snapshot</h3>
                  <p class="mt-2 text-sm text-slate-500">
                    A quick summary of today's workload for the education team.
                  </p>

                  <div class="mt-6 space-y-4">
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                      <span class="text-sm font-semibold text-slate-600">
                        New absence cases
                      </span>
                      <span class="text-lg font-black text-slate-900">
                        {{ dashboardStats.absentToday }}
                      </span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                      <span class="text-sm font-semibold text-slate-600">
                        Cases waiting follow-up
                      </span>
                      <span class="text-lg font-black text-slate-900">
                        {{ dashboardStats.pendingFollowUp }}
                      </span>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                      <span class="text-sm font-semibold text-slate-600">
                        High-risk students
                      </span>
                      <span class="text-lg font-black text-rose-600">
                        {{ dashboardStats.highRisk }}
                      </span>
                    </div>
                  </div>
                </div>

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

          <ReportsTable
            v-else-if="activeNav === 'Reports'"
            :reports="classReports"
            @export="handleExportCSV"
          />

          <div
            v-else-if="activeNav === 'Risk Monitoring'"
            class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
          >
            <h3 class="mb-6 text-lg font-black text-slate-900">Risk Students Monitoring</h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
              <div
                v-for="(student, index) in riskStudents"
                :key="index"
                class="space-y-4 rounded-3xl border border-rose-100 bg-rose-50/70 p-6"
              >
                <div class="flex items-center gap-4">
                  <div class="flex size-12 items-center justify-center rounded-full border-2 border-rose-500 bg-white text-slate-400">
                    <UserMinus :size="24" />
                  </div>
                  <div>
                    <h4 class="font-bold text-slate-900">{{ student.name }}</h4>
                    <p class="text-sm text-slate-500">{{ student.class }}</p>
                  </div>
                </div>

                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold uppercase tracking-wider text-rose-600">
                    High Absence Risk
                  </span>
                  <span class="text-lg font-black text-rose-700">
                    {{ student.absence_count }} Days
                  </span>
                </div>

                <button
                  @click="handleOpenDetail(student.latest_attendance_id)"
                  class="w-full rounded-2xl border border-rose-200 bg-white py-3 text-xs font-bold text-rose-600 transition-all hover:bg-rose-500 hover:text-white"
                >
                  Quick Follow-up
                </button>
              </div>
            </div>
          </div>

          <ProfileView v-else-if="activeNav === 'My Profile'" />
          <AccountSettingsView v-else-if="activeNav === 'Account Settings'" />
        </div>
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
