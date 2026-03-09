<script setup lang="ts">
import { ref } from 'vue';
import { 
  User, 
  Bell, 
  Shield, 
  Smartphone, 
  Globe, 
  Moon,
  Save
} from 'lucide-vue-next';
import { cn } from '../lib/utils';
import { useSettings } from '../composables/useSettings';

const { darkMode, language } = useSettings();

const currentTab = ref<'profile' | 'notifications'>('profile');

const profile = ref({
  name: 'Dr. Smith',
  email: 'smith.math@school.edu',
  department: 'Mathematics & Physics'
});

const notifications = ref({
  email: true,
  push: true,
  telegram: true,
  absenceAlerts: true,
  weeklySummary: false
});

const security = ref({
  twoFactor: false,
  sessionTimeout: '30 minutes'
});

const showSavedMessage = ref(false);

const saveChanges = () => {
  showSavedMessage.value = true;
  setTimeout(() => {
    showSavedMessage.value = false;
  }, 3000);
};
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-8">
    <div class="flex justify-between items-start">
      <div>
        <h2 class="text-3xl font-black tracking-tight text-slate-900">Settings</h2>
        <p class="text-slate-500 font-medium">Manage your account preferences and system configuration</p>
      </div>
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-[-10px]"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-[-10px]"
      >
        <div v-if="showSavedMessage" class="bg-emerald-500 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg flex items-center gap-2">
          <Save :size="14" />
          Changes saved successfully!
        </div>
      </Transition>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <!-- Sidebar Navigation for Settings -->
      <div class="space-y-1">
        <button 
          v-for="tab in [
            { id: 'profile', label: 'Profile Settings', icon: User },
            { id: 'notifications', label: 'Notifications', icon: Bell }
          ]"
          :key="tab.id"
          @click="currentTab = tab.id as any"
          :class="cn(
            'w-full flex items-center gap-3 px-4 py-3 rounded-xl font-bold text-sm transition-all',
            currentTab === tab.id 
              ? 'bg-primary text-white shadow-lg shadow-primary/20' 
              : 'text-slate-600 hover:bg-white'
          )"
        >
          <component :is="tab.icon" :size="18" />
          {{ tab.label }}
        </button>
      </div>

      <!-- Settings Content -->
      <div class="md:col-span-2 space-y-6">
        <!-- Profile Section -->
        <div v-if="currentTab === 'profile'" class="space-y-6">
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
              <h3 class="font-bold text-slate-900">Personal Information</h3>
            </div>
            <div class="p-6 space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Full Name</label>
                  <input v-model="profile.name" type="text" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/10" />
                </div>
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Email Address</label>
                  <input v-model="profile.email" type="email" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/10" />
                </div>
              </div>
              <div class="space-y-1.5">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Department</label>
                <input v-model="profile.department" type="text" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/10" />
              </div>
            </div>
          </div>

          <!-- Preferences Section -->
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100">
              <h3 class="font-bold text-slate-900">System Preferences</h3>
            </div>
            <div class="p-6 space-y-6">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                    <Moon :size="18" />
                  </div>
                  <div>
                    <p class="text-sm font-bold text-slate-900">Dark Mode</p>
                    <p class="text-xs text-slate-500">Adjust the interface for low-light environments</p>
                  </div>
                </div>
                <button 
                  @click="darkMode = !darkMode"
                  :class="cn(
                    'w-12 h-6 rounded-full transition-all relative',
                    darkMode ? 'bg-primary' : 'bg-slate-200'
                  )"
                >
                  <div :class="cn(
                    'absolute top-1 size-4 bg-white rounded-full transition-all',
                    darkMode ? 'left-7' : 'left-1'
                  )" />
                </button>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                    <Globe :size="18" />
                  </div>
                  <div>
                    <p class="text-sm font-bold text-slate-900">Language</p>
                    <p class="text-xs text-slate-500">Select your preferred display language</p>
                  </div>
                </div>
                <select v-model="language" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-bold outline-none">
                  <option>English</option>
                  <option>French</option>
                  <option>Khmer</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Notifications Tab -->
        <div v-else-if="currentTab === 'notifications'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="p-6 border-b border-slate-100">
            <h3 class="font-bold text-slate-900">Notification Channels</h3>
          </div>
          <div class="p-6 space-y-6">
            <div v-for="(val, key) in notifications" :key="key" class="flex items-center justify-between">
              <div>
                <p class="text-sm font-bold text-slate-900 capitalize">{{ key.replace(/([A-Z])/g, ' $1') }}</p>
                <p class="text-xs text-slate-500">Receive updates via {{ key }}</p>
              </div>
              <button 
                @click="notifications[key as keyof typeof notifications] = !notifications[key as keyof typeof notifications]"
                :class="cn(
                  'w-12 h-6 rounded-full transition-all relative',
                  notifications[key as keyof typeof notifications] ? 'bg-primary' : 'bg-slate-200'
                )"
              >
                <div :class="cn(
                  'absolute top-1 size-4 bg-white rounded-full transition-all',
                  notifications[key as keyof typeof notifications] ? 'left-7' : 'left-1'
                )" />
              </button>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <button class="px-6 py-2.5 bg-white border border-slate-200 text-slate-600 rounded-xl font-bold text-sm hover:bg-slate-50 transition-all">
            Cancel
          </button>
          <button 
            @click="saveChanges"
            class="px-6 py-2.5 bg-primary text-white rounded-xl font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all flex items-center gap-2"
          >
            <Save :size="16" />
            Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
