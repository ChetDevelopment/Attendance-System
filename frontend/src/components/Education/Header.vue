<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Search, Bell, Settings, UserCircle, AlertTriangle, CheckCircle2, TrendingUp, LogOut } from 'lucide-vue-next';
import { cn } from '../../utils/cn';
import api from '../../services/api';
import { clearAllAuthData, logout } from '../../services/auth';
import LogoutModal from './LogoutModal.vue';

const props = defineProps<{
  isLoading: boolean;
  isNotificationOpen: boolean;
  isSettingsOpen: boolean;
  isProfileOpen: boolean;
}>();

const emit = defineEmits<{
  (e: 'refresh'): void;
  (e: 'update:isNotificationOpen', val: boolean): void;
  (e: 'update:isSettingsOpen', val: boolean): void;
  (e: 'update:isProfileOpen', val: boolean): void;
  (e: 'setActiveNav', val: string): void;
}>();

const isLogoutModalOpen = ref(false);

const user = ref<any>(null);

onMounted(() => {
  api.get('/user/profile')
    .then(res => res.data)
    .then(data => user.value = data);
});

const handleResetDb = async () => {
  if(confirm("Are you sure you want to reset the database? This will clear all data and re-seed with default values.")) {
    const { data } = await api.post('/debug/reset-db');
    alert(data.message);
    emit('update:isSettingsOpen', false);
    emit('refresh');
  }
};
</script>

<template>
  <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8 sticky top-0 z-50 shadow-sm">
    <div class="relative w-full max-w-md">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
      <input 
        type="text" 
        placeholder="Search students, classes or reports..." 
        class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-primary/20 transition-all outline-none"
      />
    </div>

    <div class="flex items-center gap-2 relative">
      <button 
        @click="emit('refresh')"
        class="p-2 text-slate-500 hover:bg-slate-50 rounded-full transition-colors"
        title="Refresh Data"
      >
        <TrendingUp :size="20" :class="cn(isLoading && 'animate-spin')" />
      </button>
      
      <div class="relative">
        <button 
          @click="() => {
            emit('update:isNotificationOpen', !isNotificationOpen);
            emit('update:isSettingsOpen', false);
            emit('update:isProfileOpen', false);
          }"
          :class="cn(
            'relative p-2 rounded-full transition-colors',
            isNotificationOpen ? 'bg-primary/10 text-primary' : 'text-slate-500 hover:bg-slate-50'
          )"
        >
          <Bell :size="20" />
          <span class="absolute top-2 right-2 size-2 bg-rose-500 rounded-full border-2 border-white"></span>
        </button>
        
        <div v-if="isNotificationOpen" class="absolute right-0 mt-2 w-80 bg-white rounded-2xl border border-slate-200 shadow-2xl z-50 overflow-hidden">
          <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h4 class="font-bold text-sm text-slate-900">Notifications</h4>
            <span class="text-[10px] font-bold text-primary uppercase tracking-wider">2 New</span>
          </div>
          <div class="max-h-[300px] overflow-y-auto">
            <div class="p-4 border-b border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer">
              <div class="flex gap-3">
                <div class="size-8 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 shrink-0">
                  <AlertTriangle :size="16" />
                </div>
                <div>
                  <p class="text-xs font-bold text-slate-900">High Absence Alert</p>
                  <p class="text-[10px] text-slate-500 mt-0.5">Benjamin Thompson has missed 3 consecutive days.</p>
                  <p class="text-[9px] text-slate-400 mt-1">2 minutes ago</p>
                </div>
              </div>
            </div>
            <div class="p-4 border-b border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer">
              <div class="flex gap-3">
                <div class="size-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                  <CheckCircle2 :size="16" />
                </div>
                <div>
                  <p class="text-xs font-bold text-slate-900">Follow-up Resolved</p>
                  <p class="text-[10px] text-slate-500 mt-0.5">James Miller's absence case has been marked as resolved.</p>
                  <p class="text-[9px] text-slate-400 mt-1">1 hour ago</p>
                </div>
              </div>
            </div>
          </div>
          <button class="w-full p-3 text-center text-[10px] font-bold text-slate-500 hover:text-primary transition-colors bg-slate-50/50">
            View All Notifications
          </button>
        </div>
      </div>

      <div class="relative">
        <button 
          @click="() => {
            emit('update:isSettingsOpen', !isSettingsOpen);
            emit('update:isNotificationOpen', false);
            emit('update:isProfileOpen', false);
          }"
          :class="cn(
            'p-2 rounded-full transition-colors',
            isSettingsOpen ? 'bg-primary/10 text-primary' : 'text-slate-500 hover:bg-slate-50'
          )"
          title="Settings"
        >
          <Settings :size="20" />
        </button>

        <div v-if="isSettingsOpen" class="absolute right-0 mt-2 w-64 bg-white rounded-2xl border border-slate-200 shadow-2xl z-50 overflow-hidden">
          <div class="p-4 border-b border-slate-100 bg-slate-50/50">
            <h4 class="font-bold text-sm text-slate-900">System Settings</h4>
          </div>
          <div class="p-2">
            <button 
              @click="handleResetDb"
              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 transition-all"
            >
              <AlertTriangle :size="16" />
              Reset Database
            </button>
            <button 
              @click="alert('Notification configuration is currently in read-only mode for your account.')"
              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all"
            >
              <Bell :size="16" />
              Notification Config
            </button>
            <button 
              @click="alert('API Configuration requires administrator privileges.')"
              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all"
            >
              <Settings :size="16" />
              API Configuration
            </button>
          </div>
        </div>
      </div>

      <div class="h-6 w-px bg-slate-200 mx-2"></div>
      
      <div class="relative">
        <button 
          @click="() => {
            emit('update:isProfileOpen', !isProfileOpen);
            emit('update:isNotificationOpen', false);
            emit('update:isSettingsOpen', false);
          }"
          :class="cn(
            'size-9 rounded-xl flex items-center justify-center transition-all overflow-hidden',
            isProfileOpen ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-primary/10 text-primary hover:bg-primary/20'
          )"
        >
          <img v-if="user?.avatar_url" :src="user.avatar_url" alt="Profile" class="size-full object-cover" referrerPolicy="no-referrer" />
          <UserCircle v-else :size="22" />
        </button>

        <div v-if="isProfileOpen" class="absolute right-0 mt-2 w-64 bg-white rounded-2xl border border-slate-200 shadow-2xl z-50 overflow-hidden">
          <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-3">
            <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center text-primary overflow-hidden">
              <img v-if="user?.avatar_url" :src="user.avatar_url" alt="Profile" class="size-full object-cover" referrerPolicy="no-referrer" />
              <UserCircle v-else :size="24" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-900">{{ user?.name || 'Loading...' }}</p>
              <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">{{ user?.role || 'User' }}</p>
            </div>
          </div>
          <div class="p-2">
            <button 
              @click="() => {
                emit('setActiveNav', 'My Profile');
                emit('update:isProfileOpen', false);
              }"
              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all"
            >
              <UserCircle :size="16" />
              My Profile
            </button>
            <button 
              @click="() => {
                emit('setActiveNav', 'Account Settings');
                emit('update:isProfileOpen', false);
              }"
              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all"
            >
              <Settings :size="16" />
              Account Settings
            </button>
            <div class="border-t border-slate-100 my-2"></div>
            <button 
              @click="isLogoutModalOpen = true"
              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 transition-all"
            >
              <LogOut :size="16" />
              Logout
            </button>
          </div>
        </div>
      </div>
    </div>

    <LogoutModal 
      :isOpen="isLogoutModalOpen" 
      @close="isLogoutModalOpen = false"
      @confirm="logout"
    />
  </header>
</template>