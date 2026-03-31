<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import {
  Search,
  Bell,
  Settings,
  UserCircle,
  AlertTriangle,
  CheckCircle2,
  TrendingUp,
  LogOut,
} from 'lucide-vue-next';
import { cn } from '../../utils/cn';
import api from '../../services/api';
import { logout } from '../../services/auth';
import { dashboardService } from '../../services/dashboardService';
import LogoutModal from './LogoutModal.vue';

const props = defineProps<{
  user?: any;
  isLoading: boolean;
  isNotificationOpen: boolean;
  isSettingsOpen: boolean;
  isProfileOpen: boolean;
  searchQuery?: string;
}>();

const emit = defineEmits<{
  (e: 'refresh'): void;
  (e: 'update:isNotificationOpen', val: boolean): void;
  (e: 'update:isSettingsOpen', val: boolean): void;
  (e: 'update:isProfileOpen', val: boolean): void;
  (e: 'update:searchQuery', val: string): void;
  (e: 'setActiveNav', val: string): void;
}>();

const router = useRouter();
const isLogoutModalOpen = ref(false);
const isLoggingOut = ref(false);
const user = ref<any>(props.user ?? null);
const notifications = ref<any[]>([]);
const unreadCount = ref(0);
const notificationLoading = ref(false);
const notificationError = ref('');

const searchValue = computed({
  get: () => props.searchQuery || '',
  set: (value: string) => emit('update:searchQuery', value),
});

const loadNotifications = async () => {
  notificationLoading.value = true;
  notificationError.value = '';

  try {
    const data = await dashboardService.getNotifications();
    notifications.value = Array.isArray(data?.notifications) ? data.notifications : [];
    unreadCount.value = Number(data?.unread_count || 0);
  } catch (error: any) {
    notificationError.value = error?.message || 'Failed to load notifications.';
    notifications.value = [];
    unreadCount.value = 0;
  } finally {
    notificationLoading.value = false;
  }
};

const handleResetDb = async () => {
  if (confirm('Are you sure you want to reset the database? This will clear all data and re-seed with default values.')) {
    const { data } = await api.post('/debug/reset-db');
    alert(data.message);
    emit('update:isSettingsOpen', false);
    emit('refresh');
  }
};

onMounted(() => {
  api.get('/user/profile')
    .then((res) => res.data)
    .then((data) => {
      user.value = data;
    });

  loadNotifications();
});

watch(
  () => props.isNotificationOpen,
  (open) => {
    if (open && notifications.value.length === 0 && !notificationLoading.value) {
      loadNotifications();
    }
  },
);

const handleLogout = async () => {
  if (isLoggingOut.value) return;

  isLoggingOut.value = true;
  try {
    await logout();
    isLogoutModalOpen.value = false;
    await router.replace({ name: 'login' });
  } finally {
    isLoggingOut.value = false;
  }
};
</script>

<template>
  <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-8 shadow-sm">
    <div class="flex flex-1 items-center gap-4">
      <div class="relative w-full max-w-md">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
        <input
          v-model="searchValue"
          type="text"
          placeholder="Search students, classes, or reports..."
          class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-sm outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20"
        />
      </div>
    </div>

    <div class="flex items-center gap-4">
      <div class="flex items-center gap-2 rounded-full border border-slate-100 bg-slate-50 px-3 py-1.5">
        <div class="size-2 animate-pulse rounded-full bg-emerald-500" />
        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">education mode</span>
      </div>

      <button
        @click="emit('refresh')"
        class="rounded-lg p-2 text-slate-600 transition-colors hover:bg-slate-100"
        title="Refresh Data"
      >
        <TrendingUp :size="20" :class="cn(isLoading && 'animate-spin')" />
      </button>

      <div class="relative">
        <button
          @click="() => {
            emit('update:isNotificationOpen', !isNotificationOpen)
            emit('update:isSettingsOpen', false)
            emit('update:isProfileOpen', false)
          }"
          class="relative rounded-lg p-2 text-slate-600 transition-colors hover:bg-slate-100"
        >
          <Bell :size="20" />
          <span
            v-if="unreadCount > 0"
            class="absolute right-2 top-2 min-w-2 rounded-full border-2 border-white bg-rose-500 px-1 text-[9px] leading-none text-white"
          >
            {{ unreadCount > 9 ? '9+' : unreadCount }}
          </span>
        </button>

        <div
          v-if="isNotificationOpen"
          class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
          <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 p-4">
            <h4 class="text-sm font-bold text-slate-900">Notifications</h4>
            <span class="text-[10px] font-bold uppercase tracking-wider text-primary">
              {{ unreadCount }} New
            </span>
          </div>
          <div class="max-h-[300px] overflow-y-auto">
            <div v-if="notificationLoading" class="p-4 text-sm text-slate-500">
              Loading notifications...
            </div>
            <div v-else-if="notificationError" class="p-4 text-sm text-rose-600">
              {{ notificationError }}
            </div>
            <div v-else-if="notifications.length === 0" class="p-4 text-sm text-slate-500">
              No notifications available.
            </div>
            <div
              v-for="notification in notifications"
              v-else
              :key="notification.id"
              class="cursor-pointer border-b border-slate-50 p-4 transition-colors hover:bg-slate-50"
            >
              <div class="flex gap-3">
                <div
                  :class="cn(
                    'size-8 rounded-full flex items-center justify-center shrink-0',
                    notification.type === 'warning' || notification.type === 'absence' ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600',
                  )"
                >
                  <AlertTriangle v-if="notification.type === 'warning' || notification.type === 'absence'" :size="16" />
                  <CheckCircle2 v-else :size="16" />
                </div>
                <div>
                  <p class="text-xs font-bold text-slate-900">{{ notification.title }}</p>
                  <p class="mt-0.5 text-[10px] text-slate-500">{{ notification.message || notification.subtitle }}</p>
                  <p class="mt-1 text-[9px] text-slate-400">
                    {{ notification.created_at || notification.date || 'Just now' }}
                  </p>
                </div>
              </div>
            </div>
          </div>
          <button class="w-full bg-slate-50/50 p-3 text-center text-[10px] font-bold text-slate-500 transition-colors hover:text-primary">
            View All Notifications
          </button>
        </div>
      </div>

      <div class="relative">
        <button
          @click="() => {
            emit('update:isSettingsOpen', !isSettingsOpen)
            emit('update:isNotificationOpen', false)
            emit('update:isProfileOpen', false)
          }"
          class="rounded-lg p-2 text-slate-600 transition-colors hover:bg-slate-100"
          title="Settings"
        >
          <Settings :size="20" />
        </button>

        <div
          v-if="isSettingsOpen"
          class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
          <div class="border-b border-slate-100 bg-slate-50/50 p-4">
            <h4 class="text-sm font-bold text-slate-900">System Settings</h4>
          </div>
          <div class="p-2">
            <button
              @click="handleResetDb"
              class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold text-rose-600 transition-all hover:bg-rose-50"
            >
              <AlertTriangle :size="16" />
              Reset Database
            </button>
            <button
              @click="alert('Notification configuration is currently in read-only mode for your account.')"
              class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-600 transition-all hover:bg-slate-50"
            >
              <Bell :size="16" />
              Notification Config
            </button>
            <button
              @click="alert('API Configuration requires administrator privileges.')"
              class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-600 transition-all hover:bg-slate-50"
            >
              <Settings :size="16" />
              API Configuration
            </button>
          </div>
        </div>
      </div>

      <div class="h-6 w-px bg-slate-200"></div>

      <div class="relative">
        <button
          @click="() => {
            emit('update:isProfileOpen', !isProfileOpen)
            emit('update:isNotificationOpen', false)
            emit('update:isSettingsOpen', false)
          }"
          :class="cn(
            'flex size-9 items-center justify-center overflow-hidden rounded-xl transition-all',
            isProfileOpen ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-primary/10 text-primary hover:bg-primary/20',
          )"
        >
          <img
            v-if="user?.avatar_url || user?.profile_image"
            :src="user?.avatar_url || user?.profile_image"
            alt="Profile"
            class="size-full object-cover"
            referrerPolicy="no-referrer"
          />
          <UserCircle v-else :size="22" />
        </button>

        <div
          v-if="isProfileOpen"
          class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
          <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 p-4">
            <div class="flex size-10 items-center justify-center overflow-hidden rounded-full bg-primary/10 text-primary">
              <img
                v-if="user?.avatar_url || user?.profile_image"
                :src="user?.avatar_url || user?.profile_image"
                alt="Profile"
                class="size-full object-cover"
                referrerPolicy="no-referrer"
              />
              <UserCircle v-else :size="24" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-900">{{ user?.name || 'Loading...' }}</p>
              <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">
                {{ user?.role?.name || user?.role || 'Education Team' }}
              </p>
            </div>
          </div>
          <div class="p-2">
            <button
              @click="() => {
                emit('setActiveNav', 'My Profile')
                emit('update:isProfileOpen', false)
              }"
              class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-600 transition-all hover:bg-slate-50"
            >
              <UserCircle :size="16" />
              My Profile
            </button>
            <button
              @click="() => {
                emit('setActiveNav', 'Account Settings')
                emit('update:isProfileOpen', false)
              }"
              class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-600 transition-all hover:bg-slate-50"
            >
              <Settings :size="16" />
              Account Settings
            </button>
            <div class="my-2 border-t border-slate-100"></div>
            <button
              @click="isLogoutModalOpen = true"
              class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold text-rose-600 transition-all hover:bg-rose-50"
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
      :isLoading="isLoggingOut"
      @close="isLogoutModalOpen = false"
      @confirm="handleLogout"
    />
  </header>
</template>
