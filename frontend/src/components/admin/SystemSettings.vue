<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Modal from './Modal.vue';
import {
  Clock,
  Bell,
  Database,
  History as HistoryIcon,
  Save,
  Download,
  Search,
  Filter,
  CheckCircle,
  AlertTriangle,
} from 'lucide-vue-next';
import { dashboardService } from '../../services/dashboardService';
import { profileService } from '../../services/profileService';
import { adminAttendanceService } from '../../services/adminAttendanceService';
import { sessionAdminService } from '../../services/sessionAdminService';
import api from '../../services/api';

type LogStatus = 'Success' | 'Failed';

type ActivityLog = {
  time: string;
  user: string;
  action: string;
  status: LogStatus;
};

const isBackupModalOpen = ref(false);
const activeTab = ref<'config' | 'logs'>('config');
const logSearchQuery = ref('');
const serverTime = ref(new Date().toLocaleTimeString());
const loadingLogs = ref(false);
const loadingConfig = ref(false);
const saving = ref(false);
const testingTelegram = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
let timer: number | undefined;

const logs = ref<ActivityLog[]>([]);
const sessions = ref<Array<{ id: number; name: string; start_time: string; end_time: string }>>([]);

const profileSettings = ref({
  theme: 'light',
  notification_email: true,
  notification_push: true,
});

const settingsForm = ref({
  default_session_start: '',
  default_session_end: '',
  late_threshold: 15,
});

const maintenanceInfo = ref({
  last_backup: '-',
  database_size: '-',
  version: String(import.meta.env.VITE_APP_VERSION || 'n/a'),
  environment: String(import.meta.env.MODE || 'production'),
});

const filteredLogs = computed(() =>
  logs.value.filter(
    (l) =>
      l.user.toLowerCase().includes(logSearchQuery.value.toLowerCase()) ||
      l.action.toLowerCase().includes(logSearchQuery.value.toLowerCase())
  )
);

const statusTextClass = (status: string) => [
  'text-[10px] font-black uppercase',
  status === 'Success' ? 'text-green-600' : 'text-red-600',
];

const loadLogs = async () => {
  loadingLogs.value = true;
      try {
    const notificationData = await dashboardService.getNotifications();
    logs.value = Array.isArray(notificationData)
      ? notificationData.map((item: any) => ({
        time: String(item?.created_at || '-'),
        user: String(item?.type || 'System'),
        action: String(item?.title || 'Activity update'),
        status: String(item?.type || '').toLowerCase().includes('failed') ? 'Failed' : 'Success',
      }))
      : [];
  } catch (error: any) {
    errorMessage.value = error?.message || 'Failed to load activity logs.';
  } finally {
    loadingLogs.value = false;
  }
};

const loadConfig = async () => {
  loadingConfig.value = true;
  errorMessage.value = '';
  try {
    const [profile, sessionData] = await Promise.all([
      profileService.getProfile(),
      sessionAdminService.list().catch(() => adminAttendanceService.getSessions()),
    ]);

    sessions.value = Array.isArray(sessionData) ? sessionData : [];
    const defaultSession = sessions.value[0] || null;

    settingsForm.value.default_session_start = String(defaultSession?.start_time || '');
    settingsForm.value.default_session_end = String(defaultSession?.end_time || '');
    settingsForm.value.late_threshold = Number(defaultSession?.late_threshold ?? settingsForm.value.late_threshold);

    profileSettings.value = {
      theme: String(profile?.theme || 'light'),
      notification_email: Boolean(profile?.notification_email ?? true),
      notification_push: Boolean(profile?.notification_push ?? true),
    };

    maintenanceInfo.value.last_backup = logs.value[0]?.time || '-';
    maintenanceInfo.value.database_size = `${sessions.value.length} sessions configured`;
  } catch (error: any) {
    errorMessage.value = error?.message || 'Failed to load system settings.';
  } finally {
    loadingConfig.value = false;
  }
};

const saveSettings = async () => {
  if (saving.value) return;
  saving.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  try {
    await profileService.updateSettings({
      theme: profileSettings.value.theme,
      notification_email: profileSettings.value.notification_email,
      notification_push: profileSettings.value.notification_push,
    });

    // Persist attendance rules to sessions (best-effort).
    const allSessions = sessions.value.length
      ? sessions.value
      : await sessionAdminService.list().catch(() => adminAttendanceService.getSessions());

    if (Array.isArray(allSessions) && allSessions.length) {
      const sessionOne = allSessions.find((s: any) => Number(s?.order || 0) === 1) ?? allSessions[0];

      const updates = [];

      if (sessionOne?.id) {
        updates.push(sessionAdminService.update(sessionOne.id, {
          name: sessionOne.name,
          order: Number(sessionOne.order || 1),
          start_time: settingsForm.value.default_session_start,
          end_time: settingsForm.value.default_session_end,
          late_threshold: Number(settingsForm.value.late_threshold || 0),
          is_active: sessionOne.is_active ?? true,
          description: sessionOne.description ?? null,
          date: sessionOne.date ?? null,
          academic_year_id: sessionOne.academic_year_id ?? null,
        }));
      }

      // Apply late threshold across all sessions for consistency.
      for (const s of allSessions) {
        if (!s?.id || s?.id === sessionOne?.id) continue;
        updates.push(sessionAdminService.update(s.id, {
          name: s.name,
          order: Number(s.order || 1),
          start_time: s.start_time,
          end_time: s.end_time,
          late_threshold: Number(settingsForm.value.late_threshold || 0),
          is_active: s.is_active ?? true,
          description: s.description ?? null,
          date: s.date ?? null,
          academic_year_id: s.academic_year_id ?? null,
        }));
      }

      await Promise.allSettled(updates);
    }

    successMessage.value = 'Settings saved successfully.';
  } catch (error: any) {
    errorMessage.value = error?.message || 'Failed to save settings.';
  } finally {
    saving.value = false;
  }
};

const clearSystemCache = async () => {
  errorMessage.value = '';
  successMessage.value = '';
  try {
    const response = await api.post('/admin/system/clear-cache');
    successMessage.value = response?.data?.message || 'System cache cleared.';
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || error?.message || 'Failed to clear system cache.';
  }
};

const downloadConfigBackup = async () => {
  errorMessage.value = '';
  successMessage.value = '';
  try {
    const response = await api.get('/admin/system/export-config', { responseType: 'blob' });
    const blob = new Blob([response.data], { type: 'application/json;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `attendance-config-${new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-')}.json`;
    link.click();
    URL.revokeObjectURL(url);
    successMessage.value = 'Configuration exported.';
    maintenanceInfo.value.last_backup = new Date().toLocaleString();
    isBackupModalOpen.value = false;
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || error?.message || 'Failed to export configuration.';
  }
};

const testTelegramConnection = async () => {
  if (testingTelegram.value) return;
  testingTelegram.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  try {
    const response = await api.post('/test/telegram');

    if (response?.data?.success || response?.status === 200) {
      successMessage.value = 'Telegram test request sent successfully.';
    } else {
      throw new Error(response?.data?.message || 'Telegram test failed.');
    }
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || error?.message || 'Failed to test Telegram connection.';
  } finally {
    testingTelegram.value = false;
  }
};

const exportLogs = () => {
  const content = filteredLogs.value
    .map((log) => `${log.time},${log.user},${log.action},${log.status}`)
    .join('\n');

  if (!content) return;

  const blob = new Blob([`time,user,action,status\n${content}`], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = 'activity-logs.csv';
  link.click();
  URL.revokeObjectURL(url);
};

onMounted(() => {
  timer = window.setInterval(() => {
    serverTime.value = new Date().toLocaleTimeString();
  }, 1000);
  loadLogs().then(loadConfig);
});

onUnmounted(() => {
  if (timer) window.clearInterval(timer);
});
</script>

<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">System Settings</h2>
        <p class="text-sm text-slate-500 font-medium">Configure system parameters and view activity logs</p>
      </div>
      <div class="flex bg-white p-1 rounded-lg border border-slate-200 shadow-sm">
        <button
          @click="activeTab = 'config'"
          :class="[
            'px-4 py-1.5 rounded-md text-xs font-bold transition-all',
            activeTab === 'config' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50',
          ]"
        >
          Configuration
        </button>
        <button
          @click="activeTab = 'logs'"
          :class="[
            'px-4 py-1.5 rounded-md text-xs font-bold transition-all',
            activeTab === 'logs' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50',
          ]"
        >
          Activity Logs
        </button>
      </div>
    </div>

    <p v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm">{{ errorMessage }}</p>
    <p v-if="successMessage" class="p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm">{{ successMessage }}</p>

    <div v-if="activeTab === 'config'" class="grid grid-cols-1 xl:grid-cols-2 gap-8">
      <div class="space-y-6">
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
          <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <Clock class="size-5 text-primary" />
            <h3 class="font-bold text-slate-900">Attendance Rules</h3>
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Default Session Start</label>
              <input v-model="settingsForm.default_session_start" type="time" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Default Session End</label>
              <input v-model="settingsForm.default_session_end" type="time" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
          </div>

          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Late Threshold (Minutes)</label>
            <div class="flex items-center gap-3">
              <input v-model.number="settingsForm.late_threshold" type="number" class="w-24 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" />
              <span class="text-xs text-slate-400">Minutes after session start</span>
            </div>
          </div>

          <div class="pt-4">
            <button :disabled="saving || loadingConfig" @click="saveSettings" class="flex items-center gap-2 px-6 py-2 bg-primary text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/20 disabled:opacity-60">
              <Save class="size-4" />
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
          <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <Bell class="size-5 text-primary" />
            <h3 class="font-bold text-slate-900">Telegram Integration</h3>
          </div>

          <p class="text-sm text-slate-600">
            Telegram credentials are configured on the server (backend <code>.env</code>) for security. Use the test button to verify connectivity.
          </p>

          <div class="pt-4">
            <button :disabled="testingTelegram" @click="testTelegramConnection" class="px-6 py-2 bg-slate-900 text-white rounded-lg font-bold text-sm shadow-xl disabled:opacity-60">
              {{ testingTelegram ? 'Testing...' : 'Test Connection' }}
            </button>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
          <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <Database class="size-5 text-primary" />
            <h3 class="font-bold text-slate-900">Database Maintenance</h3>
          </div>

          <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-xs font-medium text-slate-600">Last Backup</span>
              <span class="text-[10px] font-bold text-slate-400">{{ maintenanceInfo.last_backup }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-xs font-medium text-slate-600">Database Size</span>
              <span class="text-[10px] font-bold text-slate-400">{{ maintenanceInfo.database_size }}</span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <button
              @click="isBackupModalOpen = true"
              class="flex items-center justify-center gap-2 px-4 py-3 bg-white border border-slate-200 rounded-xl font-bold text-xs text-slate-600 hover:bg-slate-50 transition-all"
            >
              <Download class="size-4" />
              Export Config
            </button>
            <button disabled class="flex items-center justify-center gap-2 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-bold text-xs text-slate-400 cursor-not-allowed">
              <HistoryIcon class="size-4" />
              Restore Point
            </button>
          </div>

          <button @click="clearSystemCache" class="w-full py-3 bg-red-50 text-red-600 border border-red-100 rounded-xl font-bold text-xs hover:bg-red-100 transition-all">
            Clear System Cache
          </button>
        </div>

        <div class="bg-primary/5 p-6 rounded-xl border border-primary/20 space-y-4">
          <h4 class="text-sm font-bold text-primary">System Information</h4>
          <div class="space-y-2">
            <div class="flex justify-between text-[10px] font-bold">
              <span class="text-slate-500 uppercase">Version</span>
              <span class="text-primary">{{ maintenanceInfo.version }}</span>
            </div>
            <div class="flex justify-between text-[10px] font-bold">
              <span class="text-slate-500 uppercase">Environment</span>
              <span class="text-primary">{{ maintenanceInfo.environment }}</span>
            </div>
            <div class="flex justify-between text-[10px] font-bold">
              <span class="text-slate-500 uppercase">Server Time</span>
              <span class="text-primary">{{ serverTime }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between gap-4">
        <div class="relative flex-1 max-w-md">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
          <input
            v-model="logSearchQuery"
            type="text"
            placeholder="Search logs..."
            class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
          />
        </div>
        <div class="flex items-center gap-2">
          <button class="flex items-center gap-2 px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50">
            <Filter class="size-4" />
            Filters
          </button>
          <button @click="exportLogs" class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50">
            <Download class="size-4 text-slate-500" />
          </button>
        </div>
      </div>

      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider">
          <tr>
            <th class="px-6 py-4">Timestamp</th>
            <th class="px-6 py-4">User</th>
            <th class="px-6 py-4">Action</th>
            <th class="px-6 py-4">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loadingLogs">
            <td :colspan="4" class="px-6 py-10 text-center text-slate-400 italic">Loading logs...</td>
          </tr>
          <tr v-for="(log, i) in filteredLogs" :key="i" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4 font-mono text-[11px] text-slate-500">{{ log.time }}</td>
            <td class="px-6 py-4 font-bold text-slate-900">{{ log.user }}</td>
            <td class="px-6 py-4 text-slate-600">{{ log.action }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-1.5">
                <CheckCircle v-if="log.status === 'Success'" class="size-3 text-green-500" />
                <AlertTriangle v-else class="size-3 text-red-500" />
                <span :class="statusTextClass(log.status)">{{ log.status }}</span>
              </div>
            </td>
          </tr>
          <tr v-if="!loadingLogs && filteredLogs.length === 0">
            <td :colspan="4" class="px-6 py-10 text-center text-slate-400 italic">No logs found matching your criteria.</td>
          </tr>
        </tbody>
      </table>

      <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
        <p class="text-[10px] text-slate-400 font-bold uppercase">Showing {{ filteredLogs.length }} logs</p>
        <div class="flex gap-1">
          <button
            v-for="p in [1, 2, 3]"
            :key="p"
            :class="[
              'size-8 rounded-lg text-xs font-bold transition-all',
              p === 1 ? 'bg-primary text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50',
            ]"
          >
            {{ p }}
          </button>
        </div>
      </div>
    </div>

    <Modal :is-open="isBackupModalOpen" title="Export Configuration" @close="isBackupModalOpen = false">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">This exports sessions and system metadata to a JSON file for backup/restore or sharing configuration between environments.</p>
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-2">
          <div class="flex justify-between text-[10px] font-bold">
            <span class="text-slate-500 uppercase">Format</span>
            <span class="text-slate-900">JSON</span>
          </div>
          <div class="flex justify-between text-[10px] font-bold">
            <span class="text-slate-500 uppercase">Estimated Size</span>
            <span class="text-slate-900">&lt; 1 MB</span>
          </div>
        </div>
        <div class="pt-4 flex justify-end gap-3">
          <button @click="isBackupModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button @click="downloadConfigBackup" class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg shadow-lg shadow-primary/20">Download</button>
        </div>
      </div>
    </Modal>
  </div>
</template>
