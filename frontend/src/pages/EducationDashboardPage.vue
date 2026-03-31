<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { UserMinus, Clock, FileText, AlertTriangle } from 'lucide-vue-next'
import api from '../services/api'
import { dashboardService } from '../services/dashboardService'
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
const searchQuery = ref('')
const isExportingReports = ref(false)

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

const pageTitle = computed(() => {
  if (activeNav.value === 'Dashboard') return `Welcome, ${welcomeName.value}`
  return activeNav.value
})

const pageDescription = computed(() => {
  if (activeNav.value === 'Dashboard') {
    return 'Monitor attendance exceptions, follow-up workload, and student risk in one place.'
  }

  if (activeNav.value === 'Absence Follow-up') {
    return 'Review absences, open a case, and keep follow-up notes up to date.'
  }

  if (activeNav.value === 'Reports') {
    return 'Class-level attendance summary and export tools.'
  }

  if (activeNav.value === 'Risk Monitoring') {
    return 'Identify high-risk students and start follow-ups quickly.'
  }

  if (activeNav.value === 'My Profile') {
    return 'Manage your profile information.'
  }

  if (activeNav.value === 'Account Settings') {
    return 'Update account preferences and security.'
  }

  return ''
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

const normalizeApiPath = (url: string) => {
  if (/^https?:\/\//i.test(url)) return url
  if (url.startsWith('/api')) return url.slice(4) || '/'
  return url.startsWith('/') ? url : `/${url}`
}

const fetchJson = async (url: string) => {
  const { data } = await api.get(normalizeApiPath(url))
  return data
}

const isAccessError = (error: any) =>
  error?.response?.status === 401 ||
  error?.response?.status === 403 ||
  /unauthorized|forbidden|permission/i.test(String(error?.message || ''))

const toSearchableText = (value: any) => String(value ?? '').toLowerCase()

const matchesSearch = (row: any, fields: any[]) => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return true
  return fields.some((field) => toSearchableText(field).includes(query))
}

const filteredAbsentToday = computed(() =>
  absentToday.value.filter((row) =>
    matchesSearch(row, [row.name, row.class, row.reason, row.date, row.attendance_id]),
  ),
)

const filteredAllAbsent = computed(() =>
  allAbsent.value.filter((row) =>
    matchesSearch(row, [row.name, row.class, row.reason, row.date, row.attendance_id]),
  ),
)

const filteredRiskStudents = computed(() =>
  riskStudents.value.filter((row) =>
    matchesSearch(row, [row.name, row.class, row.absence_count, row.latest_attendance_id]),
  ),
)

const filteredClassReports = computed(() =>
  classReports.value.filter((row) =>
    matchesSearch(row, [row.class, row.present_count, row.absent_count, row.late_count]),
  ),
)

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

  const failed = results.some((r) => r.status === 'rejected')
  if (failed) {
    const accessDenied = results.find((r) => r.status === 'rejected' && isAccessError((r as PromiseRejectedResult).reason))
    errorMessage.value = accessDenied
      ? 'You do not have permission to view one or more sections of this dashboard.'
      : 'Some data could not be loaded from server. Showing available data only.'
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
  } catch (error: any) {
    errorMessage.value = isAccessError(error)
      ? 'You do not have permission to view this attendance detail.'
      : 'Failed to load attendance detail from server.'
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
  } catch (error: any) {
    alert(isAccessError(error) ? 'You do not have permission to update follow-ups.' : 'Failed to save follow-up.')
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
  } catch (error: any) {
    alert(isAccessError(error) ? 'You do not have permission to send alerts.' : 'Error sending alert.')
  }
}

const downloadBlob = (blob: Blob, filename: string) => {
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = filename
  link.click()
  URL.revokeObjectURL(link.href)
}

const handleExportReports = async () => {
  errorMessage.value = ''
  isExportingReports.value = true

  try {
    const blob = await dashboardService.exportClassSummary()
    downloadBlob(blob, `education_class_summary_${new Date().toISOString().slice(0, 10)}.csv`)
  } catch (error: any) {
    errorMessage.value = isAccessError(error)
      ? 'You do not have permission to export this report.'
      : error?.message || 'Failed to export report.'
  } finally {
    isExportingReports.value = false
  }
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
  <div class="flex min-h-screen bg-background-light">
    <Sidebar v-model:activeNav="activeNav" :user="currentUser" />

    <main class="flex min-w-0 flex-1 flex-col overflow-hidden">
      <Header
        :user="currentUser"
        :isLoading="isLoading"
        @refresh="fetchData"
        v-model:isNotificationOpen="isNotificationOpen"
        v-model:isSettingsOpen="isSettingsOpen"
        v-model:isProfileOpen="isProfileOpen"
        v-model:searchQuery="searchQuery"
        @setActiveNav="(val) => (activeNav = val)"
      />

      <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-6">
          <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-primary/7 via-transparent to-emerald-500/10" />
            <div class="relative flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
              <div class="space-y-2">
                <p class="inline-flex w-fit items-center rounded-full bg-primary/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.28em] text-primary">
                  Education Workspace
                </p>
                <h2 class="text-3xl font-black tracking-tight text-slate-900">
                  {{ pageTitle }}
                </h2>
                <p class="max-w-2xl text-sm font-medium text-slate-500">
                  {{ pageDescription }}
                </p>
                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">
                  Signed in as {{ welcomeRole }}
                </p>
              </div>

              <div v-if="activeNav === 'Dashboard'" class="flex flex-wrap items-center gap-3">
                <button
                  @click="fetchData"
                  class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-slate-50"
                >
                  Refresh dashboard
                </button>
                <button
                  @click="activeNav = 'Absence Follow-up'"
                  class="rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white shadow-lg shadow-primary/15 transition-all hover:-translate-y-0.5 hover:bg-primary/95"
                >
                  Open follow-ups
                </button>
              </div>
            </div>
          </section>

          <div
            v-if="errorMessage"
            class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
          >
            {{ errorMessage }}
          </div>

          <div v-if="activeNav === 'Dashboard'" class="space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2 xl:grid-cols-4">
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

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
              <div class="space-y-6 xl:col-span-2">
                <TrendsChart :data="trendData" />
                <AttendanceTable
                  title="Today's Absent Students"
                  :data="filteredAbsentToday"
                  :isLoading="isLoading"
                  @openDetail="handleOpenDetail"
                  @viewAll="activeNav = 'Absence Follow-up'"
                />
              </div>

              <div class="space-y-6">
                <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                  <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-slate-50 via-white to-slate-50" />
                  <div class="relative">
                    <h3 class="text-lg font-black text-slate-900">Operations Snapshot</h3>
                    <p class="mt-2 text-sm font-medium text-slate-500">
                      Quick numbers for today’s team workload.
                    </p>

                    <div class="mt-6 space-y-3">
                      <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <span class="text-sm font-semibold text-slate-600">New absence cases</span>
                        <span class="text-lg font-black text-slate-900">{{ dashboardStats.absentToday }}</span>
                      </div>

                      <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <span class="text-sm font-semibold text-slate-600">Waiting follow-up</span>
                        <span class="text-lg font-black text-slate-900">{{ dashboardStats.pendingFollowUp }}</span>
                      </div>

                      <div class="flex items-center justify-between rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3">
                        <span class="text-sm font-semibold text-rose-700">High-risk students</span>
                        <span class="text-lg font-black text-rose-700">{{ dashboardStats.highRisk }}</span>
                      </div>
                    </div>
                  </div>
                </section>

                <RiskStudents
                  :students="filteredRiskStudents"
                  @viewAll="activeNav = 'Risk Monitoring'"
                  @quickFollowUp="handleOpenDetail"
                />
              </div>
            </div>
          </div>

          <AttendanceTable
            v-else-if="activeNav === 'Absence Follow-up'"
            title="Absence Follow-up Module"
            :data="filteredAllAbsent"
            :isLoading="isLoading"
            @openDetail="handleOpenDetail"
            :showDate="true"
          />

          <ReportsTable
            v-else-if="activeNav === 'Reports'"
            :reports="filteredClassReports"
            :isExporting="isExportingReports"
            @export="handleExportReports"
          />

          <div
            v-else-if="activeNav === 'Risk Monitoring'"
            class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
          >
            <h3 class="mb-6 text-lg font-black text-slate-900">Risk Students Monitoring</h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
              <div
                v-for="(student, index) in filteredRiskStudents"
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
