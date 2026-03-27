<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { Search, Bell, LogOut, User, Settings, UserCircle } from 'lucide-vue-next';
import api from '../services/api';
import { notificationService } from '../services/notificationService';
import { clearStudentSession, clearToken, clearUser, clearUserRole, getToken, getUser } from '../services/auth';

const emit = defineEmits<{
  navigate: [module: string];
}>();

const isProfileOpen = ref(false);
const isNotificationsOpen = ref(false);
const isLoggingOut = ref(false);
const router = useRouter();

interface Notification {
  id: number;
  title: string;
  subtitle: string;
  type: string;
  read: boolean;
  created_at: string;
}

const notifications = ref<Notification[]>([]);
const notificationLoading = ref(false);
const notificationError = ref('');

const currentUser = computed(() => getUser());
const userName = computed(() => currentUser.value?.name || 'User');
const userRole = computed(() => {
  const role = currentUser.value?.role;

  if (role?.name === 'admin') return 'Administrator';
  if (role?.name === 'teacher') return 'Teacher';
  if (role?.name === 'education') return 'Education Staff';
  if (role?.name === 'student') return 'Student';
  if (role === 'admin') return 'Administrator';
  if (role === 'teacher') return 'Teacher';
  if (role === 'education') return 'Education Staff';
  if (role === 'student') return 'Student';

  return 'User';
});
const userAvatar = computed(() => currentUser.value?.avatar_url || currentUser.value?.profile_image || null);

const unreadNotifications = computed(() => notifications.value.filter((notification) => !notification.read));
const hasUnread = computed(() => unreadNotifications.value.length > 0);

const navigateTo = (module: string) => {
  emit('navigate', module);
  isProfileOpen.value = false;
};

const loadNotifications = async () => {
  if (!getToken()) return;

  notificationLoading.value = true;
  notificationError.value = '';

  try {
    const data = await notificationService.getNotifications();
    notifications.value = Array.isArray(data) ? data : [];
  } catch (error) {
    console.error('Failed to load notifications:', error);
    notificationError.value = 'Failed to load notifications';
    notifications.value = [];
  } finally {
    notificationLoading.value = false;
  }
};

const markAllAsRead = async () => {
  if (!getToken() || notifications.value.length === 0) return;

  try {
    await notificationService.markAllAsRead();
    notifications.value = notifications.value.map((notification) => ({ ...notification, read: true }));
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error);
  }
};

const handleLogout = async () => {
  if (isLoggingOut.value) return;

  isLoggingOut.value = true;

  try {
    if (getToken()) {
      await api.post('/auth/logout');
    }
  } catch {
    // Always clear local token and continue to login page even if API fails.
  } finally {
    clearToken();
    clearStudentSession();
    clearUser();
    clearUserRole();
    isProfileOpen.value = false;
    isNotificationsOpen.value = false;
    isLoggingOut.value = false;
    router.push({ name: 'login' });
  }
};

onMounted(() => {
  loadNotifications();
});
</script>

<template>
  <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-8">
    <div class="flex max-w-md flex-1 items-center">
      <div class="relative w-full">
        <Search class="absolute left-3 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
        <input
          type="text"
          placeholder="Search users, students, or reports..."
          class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 text-sm outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20"
        />
      </div>
    </div>

    <div class="flex items-center gap-4">
      <div class="flex items-center gap-2 rounded-full border border-slate-100 bg-slate-50 px-3 py-1.5">
        <div class="size-2 animate-pulse rounded-full bg-emerald-500"></div>
        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">admin mode</span>
      </div>

      <div class="relative">
        <button
          @click="isNotificationsOpen = !isNotificationsOpen"
          class="relative rounded-lg p-2 text-slate-600 transition-colors hover:bg-slate-100"
        >
          <Bell class="size-5" />
          <span v-if="hasUnread" class="absolute right-2 top-2 size-2 rounded-full border-2 border-white bg-red-500"></span>
        </button>

        <div
          v-if="isNotificationsOpen"
          class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
        >
          <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-4 py-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-900">Notifications</span>
            <button
              @click="markAllAsRead"
              class="text-[10px] font-bold text-primary transition-colors hover:text-primary/80"
            >
              Mark all read
            </button>
          </div>
          <div class="max-h-80 overflow-y-auto">
            <div v-if="notificationLoading" class="px-4 py-4 text-center text-[10px] text-slate-400">
              Loading notifications...
            </div>
            <div v-else-if="notificationError" class="px-4 py-4 text-center text-[10px] text-red-500">
              {{ notificationError }}
            </div>
            <div v-else-if="notifications.length === 0" class="px-4 py-4 text-center text-[10px] italic text-slate-400">
              No notifications at this time
            </div>
            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="cursor-pointer border-b border-slate-50 px-4 py-3 transition-colors hover:bg-slate-50 last:border-0"
            >
              <div class="flex items-start gap-3">
                <div
                  :class="[
                    'mt-1 size-2 flex-shrink-0 rounded-full',
                    notification.read ? 'bg-slate-300' : 'bg-primary',
                  ]"
                ></div>
                <div class="flex-1">
                  <p class="text-xs font-medium text-slate-800">{{ notification.title }}</p>
                  <p class="mt-1 text-[10px] text-slate-400">{{ notification.subtitle }}</p>
                  <p class="mt-1 text-[10px] text-slate-300">{{ new Date(notification.created_at).toLocaleString() }}</p>
                </div>
              </div>
            </div>
          </div>
          <div class="border-t border-slate-100 px-4 py-3">
            <button
              @click="markAllAsRead"
              class="w-full text-[10px] font-bold text-slate-500 transition-colors hover:text-primary"
            >
              Mark all read
            </button>
          </div>
        </div>
      </div>

      <div class="h-6 w-px bg-slate-200"></div>

      <div class="relative">
        <button
          @click="isProfileOpen = !isProfileOpen"
          class="flex items-center gap-3 rounded-lg p-1 transition-colors hover:bg-slate-50"
        >
          <div class="hidden text-right sm:block">
            <p class="text-sm font-bold leading-tight text-slate-900">{{ userName }}</p>
            <p class="text-[10px] font-medium text-slate-500">{{ userRole }}</p>
          </div>
          <div class="flex size-10 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-100 text-slate-500">
            <img
              v-if="userAvatar"
              :src="userAvatar"
              alt="User profile"
              class="h-full w-full object-cover"
              referrerpolicy="no-referrer"
            />
            <UserCircle v-else class="size-5" />
          </div>
        </button>

        <div
          v-if="isProfileOpen"
          class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white py-2 shadow-2xl"
        >
          <button
            @click="navigateTo('profile')"
            class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
          >
            <User class="size-4" />
            My Profile
          </button>
          <button
            @click="navigateTo('settings')"
            class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
          >
            <Settings class="size-4" />
            Settings
          </button>
          <div class="my-1 h-px bg-slate-100"></div>
          <button
            @click="handleLogout"
            :disabled="isLoggingOut"
            class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
          >
            <LogOut class="size-4" />
            Logout
          </button>
        </div>
      </div>

      <button
        @click="handleLogout"
        :disabled="isLoggingOut"
        class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-red-50 hover:text-red-500 sm:hidden disabled:cursor-not-allowed disabled:opacity-60"
      >
        <LogOut class="size-5" />
      </button>
    </div>
  </header>
</template>
