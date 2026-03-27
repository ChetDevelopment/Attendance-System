<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import StatCard from './StatCard.vue';
import ActiveSession from './ActiveSession.vue';
import AbsenceChart from './AbsenceChart.vue';
import RiskTable from './RiskTable.vue';
import Modal from './Modal.vue';
import {
  CheckCircle2,
  XCircle,
  Clock,
  Send,
  CloudCheck,
  Search,
  MapPin,
} from 'lucide-vue-next';
import { dashboardService } from '../../services/dashboardService';
import adminDashboardService from '../../services/adminDashboardService';
import { getUserRole } from '../../services/auth';

type Period = 'Today' | 'Weekly' | 'Monthly';
type DashboardStats = { present: string; absent: string; late: string; rate: string; offsite: string };

const isLateModalOpen = ref(false);
const isOffsiteModalOpen = ref(false);
const lateSearchQuery = ref('');
const offsiteSearchQuery = ref('');
const selectedPeriod = ref<Period>('Today');
const loading = ref(false);
const errorMessage = ref('');
const notificationLoading = ref(false);
const notificationError = ref('');
const dismissedNotificationIds = ref<number[]>([]);

const notifications = ref<Array<{ id: number; title: string; subtitle: string; type: string }>>([]);

const emptyStats: DashboardStats = { present: '0', absent: '0', late: '0', rate: '0.0%', offsite: '0' };
const stats = ref<Record<Period, DashboardStats>>({
  Today: { ...emptyStats },
  Weekly: { ...emptyStats },
  Monthly: { ...emptyStats },
});

const lateStudents = ref<Array<{ name: string; class: string; time: string; status: string }>>([]);
const offsiteStudents = ref<Array<{ name: string; class: string; time: string; status: string; distance_km: number; location: string }>>([]);

const activeSession = ref<any>(null);
const trendData = ref<Array<{ name: string; value: number }>>([]);
const riskStudents = ref<Array<{ name: string; class: string; absence_count: number }>>([]);
const activeAcademicYear = ref<{ id: number; name: string; current_term: string | number } | null>(null);
const userRole = computed(() => getUserRole());
const isAdmin = computed(() => userRole.value === 'admin');

const systemStats = ref<any>(null);
const studentAnalytics = ref<any>(null);

const filteredLateStudents = computed(() =>
  lateStudents.value.filter(
    (student) =>
      student.name.toLowerCase().includes(lateSearchQuery.value.toLowerCase())
      || student.class.toLowerCase().includes(lateSearchQuery.value.toLowerCase()),
  ),
);
const filteredOffsiteStudents = computed(() =>
  offsiteStudents.value.filter(
    (student) =>
      student.name.toLowerCase().includes(offsiteSearchQuery.value.toLowerCase())
      || student.class.toLowerCase().includes(offsiteSearchQuery.value.toLowerCase()),
  ),
);
const visibleNotifications = computed(() =>
  notifications.value.filter((item) => !dismissedNotificationIds.value.includes(item.id)),
);
const highlightedNotification = computed(() => visibleNotifications.value[0] ?? null);
const currentStats = computed(() => stats.value[selectedPeriod.value]);
const biometricEnrollment = computed(() => studentAnalytics.value?.biometric_enrollment ?? null);
const periodOptions: Period[] = ['Today', 'Weekly', 'Monthly'];

const setPeriod = (period: string) => {
  selectedPeriod.value = period as Period;
};

const formatCount = (value: number) => new Intl.NumberFormat().format(Number(value || 0));
const percent = (numerator: number, denominator: number) =>
  denominator > 0 ? `${((numerator / denominator) * 100).toFixed(1)}%` : '0.0%';
const sumTrend = (slice: Array<{ value: number }>) =>
  slice.reduce((total, item) => total + Number(item?.value || 0), 0);

const dismissNotification = (id: number) => {
  if (!dismissedNotificationIds.value.includes(id)) {
    dismissedNotificationIds.value = [...dismissedNotificationIds.value, id];
    localStorage.setItem('admin_dashboard_dismissed_notifications', JSON.stringify(dismissedNotificationIds.value));
  }
};

const normalizeNotifications = (payload: any) =>
  Array.isArray(payload)
    ? payload.map((item: any) => ({
        id: Number(item?.id || 0),
        title: String(item?.title || 'System notification'),
        subtitle: String(item?.subtitle || 'Attendance activity update'),
        type: String(item?.type || 'activity'),
      }))
    : [];

const loadAdminAnalytics = async () => {
  if (!isAdmin.value) {
    systemStats.value = null;
    studentAnalytics.value = null;
    return;
  }

  try {
    studentAnalytics.value = await adminDashboardService.getStudentAnalytics();
  } catch (error) {
    console.error('Failed to load admin analytics', error);
  }
};

const loadSystemStats = async () => {
  try {
    systemStats.value = await adminDashboardService.getSystemStats();
  } catch (error) {
    console.error('Failed to load system stats', error);
  }
};

const refreshNotifications = async () => {
  notificationLoading.value = true;
  notificationError.value = '';

  try {
    const notificationRes = await dashboardService.getNotifications();
    notifications.value = normalizeNotifications(notificationRes);
  } catch (error: any) {
    notificationError.value = error?.message || 'Failed to load notifications.';
    notifications.value = [];
  } finally {
    notificationLoading.value = false;
  }
};

const applyDashboardPayload = (data: any) => {
  const summaryRes = data.summary || {};
  const lateRes = data.late_students || data.late_students_today || [];
  const offsiteRes = data.offsite_students || [];
  const sessionRes = data.active_session || null;
  const trendsRes = data.trends || [];
  const riskRes = data.risk_students || [];
  const recentActivity = data.recent_activities || [];

  if (summaryRes?.active_academic_year) {
    activeAcademicYear.value = summaryRes.active_academic_year;
  } else if (summaryRes?.academic_year) {
    activeAcademicYear.value = {
      id: Number(summaryRes.academic_year.id || 0),
      name: String(summaryRes.academic_year.name || ''),
      current_term: summaryRes.academic_year.term || '',
    };
  } else {
    activeAcademicYear.value = null;
  }

  const present = Number(summaryRes?.total_present_today ?? summaryRes?.attendance?.today?.present ?? 0);
  const absent = Number(summaryRes?.total_absent_today ?? summaryRes?.attendance?.today?.absent ?? 0);
  const late = Number(summaryRes?.total_late_today ?? summaryRes?.attendance?.today?.late ?? 0);
  const total = present + absent + late;

  stats.value.Today = {
    present: formatCount(present),
    absent: formatCount(absent),
    late: formatCount(late),
    rate: percent(present, total),
    offsite: formatCount(Number(summaryRes?.total_offsite_today || 0)),
  };

  const weeklyPresent = Number(summaryRes?.total_present_weekly ?? summaryRes?.attendance?.week?.present ?? 0);
  const weeklyAbsent = Number(summaryRes?.total_absent_weekly ?? summaryRes?.attendance?.week?.absent ?? 0);
  const weeklyLate = Number(summaryRes?.total_late_weekly ?? summaryRes?.attendance?.week?.late ?? 0);
  stats.value.Weekly = {
    present: formatCount(weeklyPresent),
    absent: formatCount(weeklyAbsent),
    late: formatCount(weeklyLate),
    rate: percent(weeklyPresent, weeklyPresent + weeklyAbsent + weeklyLate),
    offsite: formatCount(Number(summaryRes?.total_offsite_weekly || 0)),
  };

  const monthlyPresent = Number(summaryRes?.total_present_monthly ?? summaryRes?.attendance?.month?.present ?? 0);
  const monthlyAbsent = Number(summaryRes?.total_absent_monthly ?? summaryRes?.attendance?.month?.absent ?? 0);
  const monthlyLate = Number(summaryRes?.total_late_monthly ?? summaryRes?.attendance?.month?.late ?? 0);
  stats.value.Monthly = {
    present: formatCount(monthlyPresent),
    absent: formatCount(monthlyAbsent),
    late: formatCount(monthlyLate),
    rate: percent(monthlyPresent, monthlyPresent + monthlyAbsent + monthlyLate),
    offsite: formatCount(Number(summaryRes?.total_offsite_monthly || 0)),
  };

  lateStudents.value = Array.isArray(lateRes)
    ? lateRes.map((student: any) => ({
        name: String(student?.name || 'Unknown'),
        class: String(student?.class || 'Unknown'),
        time: String(student?.time || '--:--'),
        status: String(student?.status || 'Late'),
      }))
    : [];

  offsiteStudents.value = Array.isArray(offsiteRes)
    ? offsiteRes.map((student: any) => ({
        name: String(student?.name || 'Unknown'),
        class: String(student?.class || 'Unknown'),
        time: String(student?.check_in_time || '--:--'),
        status: String(student?.status || 'Present'),
        distance_km: Number(student?.distance_km || 0),
        location: String(student?.location || ''),
      }))
    : [];

  activeSession.value = sessionRes || null;
  trendData.value = trendsRes;
  riskStudents.value = Array.isArray(riskRes)
    ? riskRes.map((student: any) => ({
        name: String(student?.name || 'Unknown'),
        class: String(student?.class || 'Unknown'),
        absence_count: Number(student?.absence_count || 0),
      }))
    : [];

  notifications.value = normalizeNotifications(
    recentActivity.map((item: any) => ({
      id: Number(item?.id || 0),
      title: String(item?.action || 'System notification'),
      subtitle: item?.student_name ? `Student: ${item.student_name}` : 'Attendance activity update',
      type: 'activity',
    })),
  );
  notificationError.value = '';
};

const loadDashboard = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const data = await adminDashboardService.getDashboardData();
    applyDashboardPayload(data);
  } catch (error: any) {
    try {
      const fallbackData = await dashboardService.getOverview();
      applyDashboardPayload(fallbackData);
    } catch (fallbackError: any) {
      errorMessage.value = fallbackError?.message || error?.message || 'Failed to load dashboard data from backend.';
    }
  } finally {
    loading.value = false;
  }
};

const autoRefreshInterval = ref<number | null>(null);

const refreshDashboard = async () => {
  await loadDashboard();
};

onMounted(async () => {
  try {
    const stored = localStorage.getItem('admin_dashboard_dismissed_notifications');
    dismissedNotificationIds.value = stored ? JSON.parse(stored) : [];
  } catch {
    dismissedNotificationIds.value = [];
  }

  notificationLoading.value = true;
  await loadDashboard();
  notificationLoading.value = false;
  void loadAdminAnalytics();
  window.setTimeout(() => {
    void loadSystemStats();
  }, 0);

  autoRefreshInterval.value = window.setInterval(refreshDashboard, 30000);
});

onUnmounted(() => {
  if (autoRefreshInterval.value) {
    window.clearInterval(autoRefreshInterval.value);
  }
});
</script>

<template>
  <div class="space-y-8">
    <div
      v-if="errorMessage"
      class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white px-6 py-6 shadow-sm md:px-8">
      <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="space-y-2">
          <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-primary">Admin Dashboard</p>
          <h2 class="text-3xl font-black tracking-tight text-slate-900">Attendance Operations Center</h2>
        </div>

        <div class="flex flex-col gap-3 xl:items-end">
          <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-1.5">
            <button
              v-for="period in periodOptions"
              :key="period"
              @click="setPeriod(period)"
              :class="[
                'rounded-xl px-4 py-2 text-xs font-bold transition-all',
                selectedPeriod === period
                  ? 'bg-primary text-white shadow-sm'
                  : 'text-slate-600 hover:bg-white',
              ]"
            >
              {{ period }}
            </button>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <button
              @click="refreshDashboard"
              :disabled="loading"
              class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white transition-all hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {{ loading ? 'Refreshing...' : 'Refresh Live Data' }}
            </button>
            <span class="text-xs font-semibold text-slate-500">
              {{ loading ? 'Updating dashboard metrics...' : 'Auto-refresh every 30 seconds' }}
            </span>
          </div>
        </div>
      </div>

      <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Academic Year</p>
          <p class="mt-2 text-sm font-bold text-slate-900">
            {{ activeAcademicYear?.name || 'No active academic year' }}
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Current Term</p>
          <p class="mt-2 text-sm font-bold text-slate-900">
            {{ activeAcademicYear?.current_term || 'Unavailable' }}
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Open Alerts</p>
          <p class="mt-2 text-sm font-bold text-slate-900">
            {{ visibleNotifications.length }} active updates
          </p>
        </div>
      </div>
    </section>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-5">
      <StatCard
        :title="`Present ${selectedPeriod}`"
        :value="currentStats.present"
        :icon="CheckCircle2"
        icon-color="text-green-500"
        border-color="border-green-500"
        :trend="`${currentStats.rate} attendance rate`"
      />
      <StatCard
        :title="`Absent ${selectedPeriod}`"
        :value="currentStats.absent"
        :icon="XCircle"
        icon-color="text-red-500"
        border-color="border-red-500"
        subtitle="Requires verification"
      />
      <StatCard
        :title="`Late ${selectedPeriod}`"
        :value="currentStats.late"
        :icon="Clock"
        icon-color="text-amber-500"
        border-color="border-amber-500"
        subtitle="Review late arrivals"
      >
        <template #action>
          <button
            @click="isLateModalOpen = true"
            class="rounded-full border border-amber-100 bg-amber-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-700"
          >
            View Details
          </button>
        </template>
      </StatCard>
      <StatCard
        :title="`Off-site ${selectedPeriod}`"
        :value="currentStats.offsite"
        :icon="MapPin"
        icon-color="text-blue-500"
        border-color="border-blue-500"
        subtitle="Outside school perimeter"
      >
        <template #action>
          <button
            @click="isOffsiteModalOpen = true"
            class="rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-blue-700"
          >
            View Details
          </button>
        </template>
      </StatCard>
      <StatCard
        v-if="biometricEnrollment"
        title="Biometric Enrollment"
        :value="`${biometricEnrollment.percentage}%`"
        :icon="Send"
        icon-color="text-blue-500"
        border-color="border-blue-500"
        :subtitle="`${biometricEnrollment.enrolled} / ${biometricEnrollment.total} students`"
      />
      <StatCard
        v-else
        title="Telegram Alerts"
        value="Online"
        :icon="Send"
        icon-color="text-primary"
        border-color="border-primary"
        subtitle="Alert channel ready"
        footer-text="Monitor delivery logs"
      />
    </div>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-3">
      <div class="space-y-8 xl:col-span-2">
        <ActiveSession :session="activeSession" :loading="loading" />
        <AbsenceChart :data="trendData" />
      </div>

      <div class="space-y-6">
        <RiskTable :students="riskStudents" />

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h3 class="text-lg font-black text-slate-900">Operations Snapshot</h3>
          <p class="mt-2 text-sm text-slate-500">
            Quick context for the current attendance workload and review queue.
          </p>

          <div class="mt-6 space-y-4">
            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
              <span class="text-sm font-semibold text-slate-600">Late students</span>
              <span class="text-lg font-black text-slate-900">{{ lateStudents.length }}</span>
            </div>
            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
              <span class="text-sm font-semibold text-slate-600">Off-site check-ins</span>
              <span class="text-lg font-black text-slate-900">{{ offsiteStudents.length }}</span>
            </div>
            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
              <span class="text-sm font-semibold text-slate-600">Trend total</span>
              <span class="text-lg font-black text-slate-900">{{ sumTrend(trendData) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-3">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-primary">System Overview</p>
            <h3 class="mt-2 text-lg font-black text-slate-900">Platform Health and Capacity</h3>
            <p class="mt-1 text-sm text-slate-500">
              Backend health, activity flow, and biometric adoption for administrators.
            </p>
          </div>

          <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
              <CloudCheck class="size-5" />
            </div>
            <div>
              <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Status</p>
              <p class="text-sm font-bold text-slate-900">Monitoring Active</p>
            </div>
          </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Database Size</p>
            <p class="mt-2 text-2xl font-black text-slate-900">
              {{ systemStats?.database?.size_mb ?? 'N/A' }}
              <span v-if="systemStats?.database?.size_mb" class="text-sm font-bold text-slate-500">MB</span>
            </p>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Activity (24h)</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ systemStats?.activity?.last_24h ?? 'N/A' }}</p>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Enrollment</p>
            <p class="mt-2 text-2xl font-black text-slate-900">
              {{ biometricEnrollment?.percentage ?? 'N/A' }}
              <span v-if="biometricEnrollment?.percentage !== undefined" class="text-sm font-bold text-slate-500">%</span>
            </p>
          </div>
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-primary">Notifications</p>
            <h3 class="mt-2 text-lg font-black text-slate-900">Recent Activity</h3>
          </div>
          <button
            @click="refreshNotifications"
            class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-100"
          >
            Refresh
          </button>
        </div>

        <div v-if="notificationLoading" class="mt-6 rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-400">
          Loading notifications...
        </div>

        <div v-else-if="notificationError" class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-6 text-sm text-rose-700">
          {{ notificationError }}
        </div>

        <div v-else-if="highlightedNotification" class="mt-6 space-y-3">
          <div
            v-for="item in visibleNotifications.slice(0, 3)"
            :key="item.id"
            class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
          >
            <p class="text-sm font-bold text-slate-900">{{ item.title }}</p>
            <p class="mt-1 text-xs leading-5 text-slate-500">{{ item.subtitle }}</p>
            <button
              @click="dismissNotification(item.id)"
              class="mt-3 text-[10px] font-bold uppercase tracking-wider text-primary transition-colors hover:text-primary/80"
            >
              Dismiss
            </button>
          </div>
        </div>

        <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-400">
          No recent notifications
        </div>
      </div>
    </div>

    <Modal :is-open="isLateModalOpen" title="Late Students Details" size="lg" @close="isLateModalOpen = false">
      <div class="space-y-4">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="lateSearchQuery"
            type="text"
            placeholder="Filter by name or class..."
            class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-sm outline-none focus:ring-2 focus:ring-primary/20"
          />
        </div>
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-500">
            <tr>
              <th class="px-4 py-2">Student</th>
              <th class="px-4 py-2">Class</th>
              <th class="px-4 py-2">Arrival Time</th>
              <th class="px-4 py-2">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(student, index) in filteredLateStudents" :key="index">
              <td class="px-4 py-3 font-bold">{{ student.name }}</td>
              <td class="px-4 py-3">{{ student.class }}</td>
              <td class="px-4 py-3 font-mono">{{ student.time }}</td>
              <td class="px-4 py-3">
                <span class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-600">LATE</span>
              </td>
            </tr>
            <tr v-if="filteredLateStudents.length === 0">
              <td :colspan="4" class="px-4 py-10 text-center italic text-slate-400">No late students found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </Modal>

    <Modal :is-open="isOffsiteModalOpen" title="Off-site Today (Outside PNC Geofence)" size="lg" @close="isOffsiteModalOpen = false">
      <div class="space-y-4">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="offsiteSearchQuery"
            type="text"
            placeholder="Filter by name or class..."
            class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-sm outline-none focus:ring-2 focus:ring-primary/20"
          />
        </div>
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-[10px] font-bold uppercase text-slate-500">
            <tr>
              <th class="px-4 py-2">Student</th>
              <th class="px-4 py-2">Class</th>
              <th class="px-4 py-2">Time</th>
              <th class="px-4 py-2">Distance</th>
              <th class="px-4 py-2">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(student, index) in filteredOffsiteStudents" :key="`offsite-${index}`">
              <td class="px-4 py-3 font-bold">{{ student.name }}</td>
              <td class="px-4 py-3">{{ student.class }}</td>
              <td class="px-4 py-3 font-mono">{{ student.time }}</td>
              <td class="px-4 py-3 font-mono">{{ student.distance_km.toFixed(3) }} km</td>
              <td class="px-4 py-3">
                <span class="rounded bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-600">{{ student.status }}</span>
              </td>
            </tr>
            <tr v-if="filteredOffsiteStudents.length === 0">
              <td :colspan="5" class="px-4 py-10 text-center italic text-slate-400">No off-site students found for today.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </Modal>
  </div>
</template>
