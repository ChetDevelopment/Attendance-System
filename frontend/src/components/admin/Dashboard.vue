<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
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
const activeAcademicYear = ref<{ id: number; name: string; current_term: number } | null>(null);

const filteredLateStudents = computed(() =>
  lateStudents.value.filter(
    (s) =>
      s.name.toLowerCase().includes(lateSearchQuery.value.toLowerCase()) ||
      s.class.toLowerCase().includes(lateSearchQuery.value.toLowerCase())
  )
);
const filteredOffsiteStudents = computed(() =>
  offsiteStudents.value.filter(
    (s) =>
      s.name.toLowerCase().includes(offsiteSearchQuery.value.toLowerCase()) ||
      s.class.toLowerCase().includes(offsiteSearchQuery.value.toLowerCase())
  )
);
const visibleNotifications = computed(() =>
  notifications.value.filter((item) => !dismissedNotificationIds.value.includes(item.id))
);

const currentStats = computed(() => stats.value[selectedPeriod.value]);

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

const loadNotifications = async () => {
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

const loadDashboard = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const data = await dashboardService.getOverview();
    
    const summaryRes = data.summary || {};
    const lateRes = data.late_students || [];
    const offsiteRes = data.offsite_students || [];
    const sessionRes = data.active_session || null;
    
    // Get trends and risk data from overview response (no extra API calls needed)
    const trendsRes = data.trends || [];
    const riskRes = data.risk_students || [];

    // Set active academic year from summary response
    if (summaryRes?.active_academic_year) {
      activeAcademicYear.value = summaryRes.active_academic_year;
    }

    const present = Number(summaryRes?.total_present_today || 0);
    const absent = Number(summaryRes?.total_absent_today || 0);
    const late = Number(summaryRes?.total_late_today || 0);
    const total = present + absent + late;

    stats.value.Today = {
      present: formatCount(present),
      absent: formatCount(absent),
      late: formatCount(late),
      rate: percent(present, total),
      offsite: formatCount(Number(summaryRes?.total_offsite_today || 0)),
    };

    stats.value.Weekly = {
        present: formatCount(Number(summaryRes?.total_present_weekly || 0)),
        absent: formatCount(Number(summaryRes?.total_absent_weekly || 0)),
        late: formatCount(Number(summaryRes?.total_late_weekly || 0)),
        rate: percent(Number(summaryRes?.total_present_weekly || 0), Number(summaryRes?.total_present_weekly || 0) + Number(summaryRes?.total_absent_weekly || 0)),
        offsite: formatCount(Number(summaryRes?.total_offsite_weekly || 0)),
    };

    stats.value.Monthly = {
        present: formatCount(Number(summaryRes?.total_present_monthly || 0)),
        absent: formatCount(Number(summaryRes?.total_absent_monthly || 0)),
        late: formatCount(Number(summaryRes?.total_late_monthly || 0)),
        rate: percent(Number(summaryRes?.total_present_monthly || 0), Number(summaryRes?.total_present_monthly || 0) + Number(summaryRes?.total_absent_monthly || 0)),
        offsite: formatCount(Number(summaryRes?.total_offsite_monthly || 0)),
    };

    lateStudents.value = Array.isArray(lateRes)
      ? lateRes.map((s: any) => ({
          name: String(s?.name || 'Unknown'),
          class: String(s?.class || 'Unknown'),
          time: String(s?.time || '--:--'),
          status: String(s?.status || 'Late'),
        }))
      : [];

    offsiteStudents.value = Array.isArray(offsiteRes)
      ? offsiteRes.map((s: any) => ({
          name: String(s?.name || 'Unknown'),
          class: String(s?.class || 'Unknown'),
          time: String(s?.check_in_time || '--:--'),
          status: String(s?.status || 'Present'),
          distance_km: Number(s?.distance_km || 0),
          location: String(s?.location || ''),
        }))
      : [];

    activeSession.value = sessionRes || null;
    trendData.value = trendsRes;
    riskStudents.value = Array.isArray(riskRes)
      ? riskRes.map((s: any) => ({
          name: String(s?.name || 'Unknown'),
          class: String(s?.class || 'Unknown'),
          absence_count: Number(s?.absence_count || 0),
        }))
      : [];
  } catch (error: any) {
    errorMessage.value = error?.message || 'Failed to load dashboard data from backend.';
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  try {
    const stored = localStorage.getItem('admin_dashboard_dismissed_notifications');
    dismissedNotificationIds.value = stored ? JSON.parse(stored) : [];
  } catch {
    dismissedNotificationIds.value = [];
  }

  await Promise.all([loadDashboard(), loadNotifications()]);
});
</script>

<template>
  <div class="space-y-8">
    <div v-if="errorMessage" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
      <p class="font-bold">Error</p>
      <p>{{ errorMessage }}</p>
    </div>
    <div class="flex items-end justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">វត្តមាន-Attendance Dashboard</h2>
        <p v-if="activeAcademicYear" class="text-sm text-slate-500 font-medium">
          {{ activeAcademicYear.name }} - Term {{ activeAcademicYear.current_term }}
        </p>
        <p v-else class="text-sm text-slate-500 font-medium">No active academic year</p>
      </div>
      <div class="flex items-center gap-3 bg-white p-1.5 rounded-lg border border-slate-200 shadow-sm">
        <button
          v-for="period in ['Today', 'Weekly', 'Monthly']"
          :key="period"
          @click="selectedPeriod = period as 'Today' | 'Weekly' | 'Monthly'"
          :class="[
            'px-4 py-1.5 rounded-md text-xs font-bold transition-all',
            selectedPeriod === period ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50',
          ]"
        >
          {{ period }}
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-6">
      <StatCard
        :title="`Present ${selectedPeriod}`"
        :value="currentStats.present"
        :icon="CheckCircle2"
        icon-color="text-green-500"
        border-color="border-green-500"
        :trend="`${currentStats.rate} Attendance rate`"
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
        subtitle="Peak at 08:05 AM"
      >
        <template #action>
          <button @click="isLateModalOpen = true" class="text-[10px] text-primary font-bold hover:underline">View Details</button>
        </template>
      </StatCard>
      <StatCard
        :title="`Off-site ${selectedPeriod}`"
        :value="currentStats.offsite"
        :icon="MapPin"
        icon-color="text-red-500"
        border-color="border-red-500"
        subtitle="Outside school perimeter"
        footer-text="Requires verification"
      >
        <template #action>
          <button @click="isOffsiteModalOpen = true" class="text-[10px] text-primary font-bold hover:underline">View Details</button>
        </template>
      </StatCard>
      <StatCard
        title="Telegram Alerts"
        value="Sent Status"
        :icon="Send"
        icon-color="text-primary"
        border-color="border-primary"
        footer-text="ID: TG-99238 - 08:32 AM"
      >
        <template #action>
          <div class="size-2 bg-green-500 rounded-full animate-pulse self-center ml-2"></div>
        </template>
      </StatCard>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
      <div class="xl:col-span-2 space-y-8">
        <ActiveSession :session="activeSession" :loading="loading" />
        <AbsenceChart :data="trendData" />
      </div>
      <div>
        <RiskTable :students="riskStudents" />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pb-12">
      <div class="bg-slate-900 text-white rounded-xl p-8 flex items-center justify-between shadow-xl">
        <div>
          <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Weekly System Uptime</h4>
          <p class="text-4xl font-black">99.98%</p>
          <p class="text-[10px] text-slate-500 mt-2">Biometric and RFID sensors online across all blocks</p>
        </div>
        <div class="size-20 bg-primary/20 rounded-full flex items-center justify-center border-4 border-primary/40">
          <CloudCheck class="size-10 text-primary" />
        </div>
      </div>

      <div
        v-if="notificationLoading"
        class="bg-white rounded-xl p-8 border border-slate-200 shadow-sm flex items-center justify-center text-slate-400 text-xs font-bold uppercase tracking-widest"
      >
        Loading notifications...
      </div>
      <div
        v-else-if="notificationError"
        class="bg-rose-50 rounded-xl p-8 border border-rose-200 shadow-sm flex items-center justify-center text-rose-600 text-xs font-bold uppercase tracking-widest"
      >
        {{ notificationError }}
      </div>
      <div
        v-else-if="visibleNotifications.length > 0"
        class="bg-white rounded-xl p-8 border border-slate-200 shadow-sm flex items-center gap-8"
      >
        <div class="flex-1">
          <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Recent Notification</h4>
          <p class="text-sm font-bold text-slate-900">{{ visibleNotifications[0].title }}</p>
          <p class="text-[10px] text-slate-400 mt-1 italic">{{ visibleNotifications[0].subtitle }}</p>
        </div>
        <div class="flex flex-col gap-2">
          <button @click="loadNotifications" class="px-4 py-2 bg-primary text-white text-[10px] font-bold rounded-lg shadow-lg shadow-primary/20">Refresh</button>
          <button
            @click="dismissNotification(visibleNotifications[0].id)"
            class="px-4 py-2 bg-slate-100 text-slate-600 text-[10px] font-bold rounded-lg hover:bg-slate-200 transition-colors"
          >
            Dismiss
          </button>
        </div>
      </div>
      <div
        v-else
        class="bg-slate-50 rounded-xl p-8 border border-dashed border-slate-200 flex items-center justify-center text-slate-400 text-xs font-bold uppercase tracking-widest"
      >
        No recent notifications
      </div>
    </div>

    <Modal :is-open="isLateModalOpen" title="Late Students Details" size="lg" @close="isLateModalOpen = false">
      <div class="space-y-4">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
          <input
            v-model="lateSearchQuery"
            type="text"
            placeholder="Filter by name or class..."
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
          />
        </div>
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold">
            <tr>
              <th class="px-4 py-2">Student</th>
              <th class="px-4 py-2">Class</th>
              <th class="px-4 py-2">Arrival Time</th>
              <th class="px-4 py-2">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(s, i) in filteredLateStudents" :key="i">
              <td class="px-4 py-3 font-bold">{{ s.name }}</td>
              <td class="px-4 py-3">{{ s.class }}</td>
              <td class="px-4 py-3 font-mono">{{ s.time }}</td>
              <td class="px-4 py-3">
                <span class="px-2 py-0.5 bg-amber-100 text-amber-600 text-[10px] font-bold rounded">LATE</span>
              </td>
            </tr>
            <tr v-if="filteredLateStudents.length === 0">
              <td :colspan="4" class="px-4 py-10 text-center text-slate-400 italic">No late students found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </Modal>

    <Modal :is-open="isOffsiteModalOpen" title="Off-site Today (Outside PNC Geofence)" size="lg" @close="isOffsiteModalOpen = false">
      <div class="space-y-4">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
          <input
            v-model="offsiteSearchQuery"
            type="text"
            placeholder="Filter by name or class..."
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
          />
        </div>
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold">
            <tr>
              <th class="px-4 py-2">Student</th>
              <th class="px-4 py-2">Class</th>
              <th class="px-4 py-2">Time</th>
              <th class="px-4 py-2">Distance</th>
              <th class="px-4 py-2">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(s, i) in filteredOffsiteStudents" :key="`offsite-${i}`">
              <td class="px-4 py-3 font-bold">{{ s.name }}</td>
              <td class="px-4 py-3">{{ s.class }}</td>
              <td class="px-4 py-3 font-mono">{{ s.time }}</td>
              <td class="px-4 py-3 font-mono">{{ s.distance_km.toFixed(3) }} km</td>
              <td class="px-4 py-3">
                <span class="px-2 py-0.5 bg-rose-100 text-rose-600 text-[10px] font-bold rounded uppercase">{{ s.status }}</span>
              </td>
            </tr>
            <tr v-if="filteredOffsiteStudents.length === 0">
              <td :colspan="5" class="px-4 py-10 text-center text-slate-400 italic">No off-site students found for today.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </Modal>
  </div>
</template>
