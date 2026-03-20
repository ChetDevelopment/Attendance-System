<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { 
  TrendingUp, 
  Clock, 
  Calendar, 
  CheckCircle2, 
  X, 
  Info, 
  ChevronRight,
  ArrowRight,
  Activity
} from 'lucide-vue-next';
import { buildStudentDashboardSummary } from '../../services/api';
import { getStudentPortalData } from '../../services/studentPortalService';

const attendanceHistory = ref([]);
const stats = ref<any>(null);
const loading = ref(true);
const loadError = ref('');

const recentRecords = computed(() => {
  return attendanceHistory.value.slice(0, 3);
});

const dashboard = computed(() => buildStudentDashboardSummary(stats.value, attendanceHistory.value));
const attendanceRateWidth = computed(() => `${dashboard.value.progressPercentage}%`);
const progressToTargetWidth = computed(() => `${dashboard.value.progressToTarget}%`);

const trends = computed(() => {
  const values = dashboard.value.recentTrendValues;
  return values.length ? values : [0];
});

onMounted(async () => {
  try {
    const { stats: dashboardStats, history } = await getStudentPortalData();
    stats.value = dashboardStats;
    attendanceHistory.value = history;
  } catch (err) {
    console.error(err);
    loadError.value = 'Unable to load your dashboard right now.';
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="p-8 space-y-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight dark:text-white">Student Dashboard</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          Welcome back{{ dashboard.studentName ? `, ${dashboard.studentName}` : '' }}. Here's your attendance overview.
        </p>
      </div>
      <div class="flex items-center gap-3">
        <div class="bg-white dark:bg-slate-900 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-2">
          <Calendar class="text-primary" :size="18" />
          <span class="text-sm font-medium dark:text-white">
            {{ dashboard.currentSession?.course_name || 'No active session' }}
          </span>
        </div>
      </div>
    </div>

    <div v-if="loadError" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ loadError }}
    </div>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
      Loading student dashboard...
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-14 h-14 bg-blue-50 dark:bg-blue-900/20 rounded-full flex items-center justify-center text-primary">
            <TrendingUp :size="24" />
          </div>
          <div>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Attendance Rate</p>
            <h3 class="text-2xl font-bold dark:text-white">{{ dashboard.monthlyPercentage }}%</h3>
          </div>
        </div>
        <div class="space-y-2">
          <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
            <div class="h-full rounded-full bg-blue-500 transition-all duration-500" :style="{ width: attendanceRateWidth }"></div>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ dashboard.totalSessions }} tracked sessions this month
          </p>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 bg-green-50 dark:bg-green-900/20 rounded-full flex items-center justify-center text-green-500">
            <Clock :size="24" />
          </div>
          <div>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Late Sessions</p>
            <h3 class="text-2xl font-bold dark:text-white">{{ dashboard.lateCount }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 bg-amber-50 dark:bg-amber-900/20 rounded-full flex items-center justify-center text-amber-500">
            <Info :size="24" />
          </div>
          <div>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Excused Absences</p>
            <h3 class="text-2xl font-bold dark:text-white">{{ dashboard.excusedCount }}</h3>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 bg-green-50 dark:bg-green-900/20 rounded-full flex items-center justify-center text-green-500">
            <CheckCircle2 :size="24" />
          </div>
          <div>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Present Sessions</p>
            <h3 class="text-2xl font-bold dark:text-white">{{ dashboard.presentCount }}</h3>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 space-y-8">
        <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
          <div class="flex items-center justify-between gap-4 mb-6">
            <div>
              <h3 class="text-lg font-bold dark:text-white">Progress</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400">
                You are {{ dashboard.monthlyPercentage }}% attended this month. Target is {{ dashboard.targetPercentage }}%.
              </p>
            </div>
            <span class="text-sm font-semibold text-primary">
              {{ dashboard.progressToTarget }}% of target
            </span>
          </div>
          <div class="space-y-4">
            <div>
              <div class="mb-2 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                <span>Attendance rate</span>
                <span>{{ dashboard.monthlyPercentage }}%</span>
              </div>
              <div class="h-3 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                <div class="h-full rounded-full bg-blue-500 transition-all duration-500" :style="{ width: attendanceRateWidth }"></div>
              </div>
            </div>
            <div>
              <div class="mb-2 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                <span>Progress to target</span>
                <span>{{ dashboard.targetPercentage }}% goal</span>
              </div>
              <div class="h-3 rounded-full bg-slate-100 dark:bg-slate-700 overflow-hidden">
                <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" :style="{ width: progressToTargetWidth }"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
          <div class="flex items-center justify-between mb-8">
            <h3 class="text-lg font-bold dark:text-white">Attendance Trends</h3>
            <div class="flex items-center gap-4">
              <div class="flex items-center gap-1.5 text-xs text-slate-500">
                <span class="w-2 h-2 rounded-full bg-primary"></span> Actual
              </div>
              <div class="flex items-center gap-1.5 text-xs text-slate-500">
                <span class="w-2 h-2 rounded-full bg-slate-300"></span> Target ({{ dashboard.targetPercentage }}%)
              </div>
            </div>
          </div>
          <div class="w-full h-56 relative flex items-end justify-between px-2">
            <div class="absolute inset-0 flex flex-col justify-between py-1 pointer-events-none">
              <div v-for="val in [100, 75, 50, 25]" :key="val" class="border-t border-slate-100 dark:border-slate-700/50 w-full flex items-center justify-between">
                <span class="text-[10px] text-slate-400 pr-2">{{ val }}%</span>
              </div>
            </div>
            <div v-for="(val, i) in trends" :key="i" class="w-[8%] bg-primary/20 rounded-t relative group cursor-pointer" :style="{ height: `${val}%` }">
              <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity">{{ val }}%</div>
              <div class="absolute bottom-0 w-full bg-primary rounded-t transition-all" style="height: 100%"></div>
            </div>
          </div>
          <div class="flex justify-between px-2 pt-4 text-[10px] text-slate-500 uppercase tracking-widest font-bold">
            <span>Week 1</span>
            <span>Week 2</span>
            <span>Week 3</span>
            <span>Week 4</span>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
          <div class="flex items-center justify-between mb-8">
            <h3 class="text-lg font-bold dark:text-white">Recent Activity</h3>
            <router-link to="/student/history" class="text-primary text-xs font-bold hover:underline flex items-center gap-1">
              View All History <ArrowRight :size="14" />
            </router-link>
          </div>
          <div class="space-y-4">
            <div v-if="recentRecords.length === 0" class="rounded-xl border border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
              No attendance records found yet.
            </div>
            <div v-for="(activity, index) in dashboard.recentActivities" :key="index" class="flex items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800">
              <div class="flex items-center gap-4">
                <div :class="[
                  'w-10 h-10 rounded-xl flex items-center justify-center',
                  activity.status === 'PRESENT' ? 'bg-green-100 text-green-600' : 
                  activity.status === 'LATE' ? 'bg-amber-100 text-amber-600' : 'bg-red-100 text-red-600'
                ]">
                  <CheckCircle2 v-if="activity.status === 'PRESENT'" :size="20" />
                  <Clock v-else-if="activity.status === 'LATE'" :size="20" />
                  <Activity v-else-if="activity.status === 'PENDING'" :size="20" />
                  <X v-else :size="20" />
                </div>
                <div>
                  <p class="text-sm font-bold dark:text-white">{{ activity.title }}</p>
                  <p class="text-[10px] text-slate-500">{{ activity.subtitle }}</p>
                  <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ activity.description }}</p>
                </div>
              </div>
              <span :class="[
                'px-2 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider',
                activity.status === 'PRESENT' ? 'bg-green-500/10 text-green-600' : 
                activity.status === 'LATE' ? 'bg-amber-500/10 text-amber-600' : 
                activity.status === 'PENDING' ? 'bg-blue-500/10 text-blue-600' : 'bg-red-500/10 text-red-600'
              ]">
                {{ activity.status }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-8">
        <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm flex flex-col">
          <h3 class="text-lg font-bold mb-6 dark:text-white">Today’s Attendance</h3>
          <div class="space-y-4 flex-1">
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/50">
              <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Status</p>
              <p class="mt-2 text-lg font-bold capitalize dark:text-white">
                {{ stats?.todayAttendance?.status || 'not recorded' }}
              </p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/50">
              <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Session</p>
              <p class="mt-2 text-sm font-semibold dark:text-white">
                {{ dashboard.todayAttendanceLabel }}
              </p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/50">
              <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Tracked Sessions</p>
              <p class="mt-2 text-lg font-bold dark:text-white">{{ dashboard.totalSessions }}</p>
            </div>
          </div>
          <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
            <div class="flex items-center gap-3 text-sm text-slate-500 italic">
              <Info class="text-primary" :size="16" />
              Keep your attendance above {{ dashboard.targetPercentage }}% to stay on track.
            </div>
          </div>
        </div>

        <div class="bg-primary/10 dark:bg-primary/5 p-8 rounded-3xl border border-primary/20 relative overflow-hidden group">
          <div class="absolute -right-4 -bottom-4 w-32 h-32 bg-primary/10 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
          <h3 class="text-lg font-bold text-primary mb-2">Need Help?</h3>
          <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">Check our student guide for attendance policies and technical support.</p>
          <button class="w-full bg-primary text-white py-3 rounded-xl font-bold text-sm flex items-center justify-center gap-2 hover:bg-blue-600 transition-all shadow-lg shadow-primary/20">
            View Study Planner
            <ChevronRight :size="18" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
