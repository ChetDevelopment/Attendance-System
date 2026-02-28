<script setup lang="ts">
import { ref, computed } from 'vue';
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
  AlertTriangle
} from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const isBackupModalOpen = ref(false);
const activeTab = ref<'config' | 'logs'>('config');
const logSearchQuery = ref('');

const logs = [
  { time: '2024-02-24 09:15:22', user: 'Dr. Albus Percival', action: 'Modified Late Threshold', status: 'Success' },
  { time: '2024-02-24 08:45:10', user: 'System', action: 'Daily Backup Created', status: 'Success' },
  { time: '2024-02-24 08:32:05', user: 'Dr. Smith', action: 'Attendance Submission', status: 'Success' },
];

const filteredLogs = computed(() => {
  return logs.filter(l => 
    l.user.toLowerCase().includes(logSearchQuery.value.toLowerCase()) || 
    l.action.toLowerCase().includes(logSearchQuery.value.toLowerCase())
  );
});

const currentTime = ref(new Date().toLocaleTimeString());
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
          :class="cn(
            'px-4 py-1.5 rounded-md text-xs font-bold transition-all',
            activeTab === 'config' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'
          )"
        >
          Configuration
        </button>
        <button 
          @click="activeTab = 'logs'"
          :class="cn(
            'px-4 py-1.5 rounded-md text-xs font-bold transition-all',
            activeTab === 'logs' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'
          )"
        >
          Activity Logs
        </button>
      </div>
    </div>

    <div v-if="activeTab === 'config'" class="grid grid-cols-1 xl:grid-cols-2 gap-8">
      <!-- General Configuration -->
      <div class="space-y-6">
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
          <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <Clock class="size-5 text-primary" />
            <h3 class="font-bold text-slate-900">Attendance Rules</h3>
          </div>
          
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Session Start</label>
              <input type="time" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" value="08:00" />
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Session End</label>
              <input type="time" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" value="15:30" />
            </div>
          </div>

          <div class="pt-4">
            <button class="flex items-center gap-2 px-6 py-2 bg-primary text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/20">
              <Save class="size-4" />
              Save Changes
            </button>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
          <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <Bell class="size-5 text-primary" />
            <h3 class="font-bold text-slate-900">Telegram Integration</h3>
          </div>
          
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Telegram Group ID</label>
            <input type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" value="-100123456789" />
          </div>

          <div class="pt-4">
            <button class="px-6 py-2 bg-slate-900 text-white rounded-lg font-bold text-sm shadow-xl">Test Connection</button>
          </div>
        </div>
      </div>

      <!-- Maintenance & Backup -->
      <div class="space-y-6">
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
          <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
            <Database class="size-5 text-primary" />
            <h3 class="font-bold text-slate-900">Database Maintenance</h3>
          </div>
          
          <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-xs font-medium text-slate-600">Last Backup</span>
              <span class="text-[10px] font-bold text-slate-400">Today, 04:00 AM</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-xs font-medium text-slate-600">Database Size</span>
              <span class="text-[10px] font-bold text-slate-400">124.5 MB</span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <button 
              @click="isBackupModalOpen = true"
              class="flex items-center justify-center gap-2 px-4 py-3 bg-white border border-slate-200 rounded-xl font-bold text-xs text-slate-600 hover:bg-slate-50 transition-all"
            >
              <Download class="size-4" />
              Download Backup
            </button>
          </div>
        </div>

        <div class="bg-primary/5 p-6 rounded-xl border border-primary/20 space-y-4">
          <h4 class="text-sm font-bold text-primary">System Information</h4>
          <div class="space-y-2">
            <div class="flex justify-between text-[10px] font-bold">
              <span class="text-slate-500 uppercase">Version</span>
              <span class="text-primary">v2.4.0-stable</span>
            </div>
            <div class="flex justify-between text-[10px] font-bold">
              <span class="text-slate-500 uppercase">Environment</span>
              <span class="text-primary">Production</span>
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
            type="text" 
            placeholder="Search logs..." 
            v-model="logSearchQuery"
            class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" 
          />
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
          <tr v-for="(log, i) in filteredLogs" :key="i" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4 font-mono text-[11px] text-slate-500">{{ log.time }}</td>
            <td class="px-6 py-4 font-bold text-slate-900">{{ log.user }}</td>
            <td class="px-6 py-4 text-slate-600">{{ log.action }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-1.5">
                <CheckCircle v-if="log.status === 'Success'" class="size-3 text-green-500" />
                <AlertTriangle v-else class="size-3 text-red-500" />
                <span :class="cn(
                  'text-[10px] font-black uppercase',
                  log.status === 'Success' ? 'text-green-600' : 'text-red-600'
                )">
                  {{ log.status }}
                </span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Modal :is-open="isBackupModalOpen" @close="isBackupModalOpen = false" title="Confirm Database Backup">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">This will generate a full SQL dump of the current database state.</p>
        <div class="pt-4 flex justify-end gap-3">
          <button @click="isBackupModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg shadow-lg shadow-primary/20">Start Backup</button>
        </div>
      </div>
    </Modal>
  </div>
</template>
