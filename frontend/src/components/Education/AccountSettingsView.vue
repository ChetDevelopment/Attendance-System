<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Settings, Bell, Shield, Moon, Sun, Save, Loader2 } from 'lucide-vue-next';
import { cn } from '../../utils/cn';
import api from '../../services/api';

const settings = ref<any>(null);
const isSaving = ref(false);

const fetchSettings = async () => {
  const { data } = await api.get('/user/profile');
  settings.value = data;
};

const handleSave = async () => {
  isSaving.value = true;
  try {
    const { status } = await api.post('/user/settings', settings.value);
    if (status >= 200 && status < 300) {
      alert('Settings saved successfully!');
    }
  } catch (err) {
    console.error(err);
  } finally {
    isSaving.value = false;
  }
};

const handleSendTestAlert = async () => {
  try {
    const { data } = await api.post('/attendance/alert', { isTest: true });
    if (data.success) {
      alert('Test alert triggered! Check server logs for output.');
    }
  } catch (err) {
    console.error(err);
    alert('Failed to send test alert.');
  }
};

onMounted(fetchSettings);
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-8">
    <div v-if="!settings" class="flex items-center justify-center h-64">
      <Loader2 class="animate-spin text-[#135bec]" />
    </div>

    <div v-else>
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Account Settings</h2>
        <button 
          @click="handleSave"
          class="px-6 py-2.5 bg-[#135bec] text-white text-sm font-bold rounded-xl hover:bg-[#135bec]/90 transition-all flex items-center gap-2"
          :disabled="isSaving"
        >
          <Loader2 v-if="isSaving" :size="18" class="animate-spin" />
          <Save v-else :size="18" />
          Save Settings
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="md:col-span-1 space-y-4">
          <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm space-y-1">
            <button class="w-full flex items-center gap-3 px-3 py-2 rounded-lg bg-[#135bec]/10 text-[#135bec] text-sm font-bold">
              <Bell :size="18" />
              Notifications
            </button>
            <button class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 text-sm font-bold transition-all">
              <Shield :size="18" />
              Security
            </button>
            <button class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 text-sm font-bold transition-all">
              <Settings :size="18" />
              General
            </button>
          </div>
        </div>

        <div class="md:col-span-2 space-y-8">
          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-6">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <Bell :size="20" class="text-[#135bec]" />
              Notification Preferences
            </h3>
            
            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <div>
                  <p class="text-sm font-bold text-slate-900">Email Notifications</p>
                  <p class="text-xs text-slate-500">Receive daily attendance summaries via email.</p>
                </div>
                <button 
                  @click="settings.notification_email = !settings.notification_email"
                  :class="cn(
                    'w-12 h-6 rounded-full transition-all relative',
                    settings.notification_email ? 'bg-[#135bec]' : 'bg-slate-300'
                  )"
                >
                  <div :class="cn(
                    'absolute top-1 size-4 bg-white rounded-full transition-all',
                    settings.notification_email ? 'left-7' : 'left-1'
                  )"></div>
                </button>
              </div>

              <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <div>
                  <p class="text-sm font-bold text-slate-900">Push Notifications</p>
                  <p class="text-xs text-slate-500">Get real-time alerts for high-risk absences.</p>
                </div>
                <button 
                  @click="settings.notification_push = !settings.notification_push"
                  :class="cn(
                    'w-12 h-6 rounded-full transition-all relative',
                    settings.notification_push ? 'bg-[#135bec]' : 'bg-slate-300'
                  )"
                >
                  <div :class="cn(
                    'absolute top-1 size-4 bg-white rounded-full transition-all',
                    settings.notification_push ? 'left-7' : 'left-1'
                  )"></div>
                </button>
              </div>

              <div class="pt-4 border-t border-slate-100">
                <button 
                  @click="handleSendTestAlert"
                  class="w-full py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-[#135bec] hover:bg-[#135bec] hover:text-white transition-all flex items-center justify-center gap-2"
                >
                  <Bell :size="16" />
                  Send Test Alert
                </button>
              </div>
            </div>
          </div>

          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-6">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <Sun :size="20" class="text-[#135bec]" />
              Appearance
            </h3>
            
            <div class="grid grid-cols-2 gap-4">
              <button 
                @click="settings.theme = 'light'"
                :class="cn(
                  'flex flex-col items-center gap-3 p-4 rounded-2xl border-2 transition-all',
                  settings.theme === 'light' ? 'border-[#135bec] bg-[#135bec]/5' : 'border-slate-100 hover:border-slate-200'
                )"
              >
                <Sun :size="24" :class="settings.theme === 'light' ? 'text-[#135bec]' : 'text-slate-400'" />
                <span class="text-sm font-bold">Light Mode</span>
              </button>
              <button 
                @click="settings.theme = 'dark'"
                :class="cn(
                  'flex flex-col items-center gap-3 p-4 rounded-2xl border-2 transition-all',
                  settings.theme === 'dark' ? 'border-[#135bec] bg-[#135bec]/5' : 'border-slate-100 hover:border-slate-200'
                )"
              >
                <Moon :size="24" :class="settings.theme === 'dark' ? 'text-[#135bec]' : 'text-slate-400'" />
                <span class="text-sm font-bold">Dark Mode</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
