<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import StatCard from './StatCard.vue';
import ActiveSession from './ActiveSession.vue';
import AbsenceChart from './AbsenceChart.vue';
import RiskTable from './RiskTable.vue';
import Modal from './Modal.vue';
import api from '../../services/api';
import {
  CheckCircle2,
  XCircle,
  Clock,
  Send,
  CloudCheck,
  Search,
  MapPin,
} from 'lucide-vue-next';

const isLateModalOpen = ref(false);
const lateSearchQuery = ref('');
const selectedPeriod = ref<'Today' | 'Weekly' | 'Monthly'>('Today');
let todayStatsInterval: ReturnType<typeof setInterval> | null = null;
const notifications = ref([
  {
    id: 1,
    title: 'Automated Parent Call scheduled for 09:45 AM',
    subtitle: "Target: 34 students from today's absence list",
    type: 'call',
  },
]);

const stats = ref({
  Today: { present: '0', absent: '0', late: '0', rate: '-', offsite: '0' },
  Weekly: { present: '5,820', absent: '156', late: '84', rate: '92.8%', offsite: '15' },
  Monthly: { present: '24,150', absent: '412', late: '320', rate: '93.5%', offsite: '42' },
});

const formatNumber = (value: number): string => new Intl.NumberFormat().format(value);

const fetchTodayStats = async () => {
  try {
    const { data } = await api.get('/dashboard/today-attendance');

    const present = Number(data?.present_today ?? 0);
    const absent = Number(data?.absent_today ?? 0);
    const late = Number(data?.late_today ?? 0);
    const total = present + absent + late;
    const attendanceRate = total > 0 ? `${((present / total) * 100).toFixed(1)}%` : '0.0%';

    stats.value.Today = {
      ...stats.value.Today,
      present: formatNumber(present),
      absent: formatNumber(absent),
      late: formatNumber(late),
      rate: attendanceRate,
    };
  } catch {
    stats.value.Today = {
      ...stats.value.Today,
      present: '0',
      absent: '0',
      late: '0',
      rate: '0.0%',
    };
  }
};

const lateStudents = [
  { name: 'John Doe', class: '10A', time: '08:05 AM', status: 'Late' },
  { name: 'Jane Smith', class: '10B', time: '08:12 AM', status: 'Late' },
  { name: 'Mike Ross', class: '11A', time: '08:15 AM', status: 'Late' },
];

const filteredLateStudents = computed(() =>
  lateStudents.filter(
    (s) =>
      s.name.toLowerCase().includes(lateSearchQuery.value.toLowerCase()) ||
      s.class.toLowerCase().includes(lateSearchQuery.value.toLowerCase())
  )
);

const currentStats = computed(() => stats.value[selectedPeriod.value]);

const dismissNotification = (id: number) => {
  notifications.value = notifications.value.filter((n) => n.id !== id);
};

onMounted(() => {
  fetchTodayStats();
  todayStatsInterval = setInterval(fetchTodayStats, 30000);
});

onBeforeUnmount(() => {
  if (todayStatsInterval) {
    clearInterval(todayStatsInterval);
  }
});
</script>

<template>
  <div class="space-y-8">
    <div class="flex items-end justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">???????-Attendance Dashboard</h2>
        <p class="text-sm text-slate-500 font-medium">Academic Session 2023-2024 - Term 2</p>
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
      />
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
        <ActiveSession />
        <AbsenceChart />
      </div>
      <div>
        <RiskTable />
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
        v-if="notifications.length > 0"
        class="bg-white rounded-xl p-8 border border-slate-200 shadow-sm flex items-center gap-8"
      >
        <div class="flex-1">
          <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Recent Notification</h4>
          <p class="text-sm font-bold text-slate-900">{{ notifications[0].title }}</p>
          <p class="text-[10px] text-slate-400 mt-1 italic">{{ notifications[0].subtitle }}</p>
        </div>
        <div class="flex flex-col gap-2">
          <button class="px-4 py-2 bg-primary text-white text-[10px] font-bold rounded-lg shadow-lg shadow-primary/20">Edit Action</button>
          <button
            @click="dismissNotification(notifications[0].id)"
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
  </div>
</template>
