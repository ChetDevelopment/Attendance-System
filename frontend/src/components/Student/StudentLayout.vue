<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { 
  LayoutDashboard, 
  QrCode, 
  History, 
  Settings, 
  Search, 
  Bell, 
  BadgeCheck,
  Fingerprint,
  LogOut
} from 'lucide-vue-next';
import { getUser, logout } from '../../services/auth';
import { prefetchStudentPortalData } from '../../services/studentPortalService';

const router = useRouter();
const route = useRoute();
const user = getUser();

const navItems = [
  { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, path: '/student/dashboard' },
  { id: 'attendance', label: 'Self Attendance', icon: QrCode, path: '/student/attendance' },
  { id: 'biometric-scan', label: 'Biometric Scan', icon: Fingerprint, path: '/student/biometric-scan' },
  { id: 'history', label: 'Attendance History', icon: History, path: '/student/history' },
  { id: 'settings', label: 'Settings', icon: Settings, path: '/student/settings' },
];

const handleLogout = () => {
  logout();
  router.push('/login');
};

const onImageError = (e: Event) => {
  const target = e.target as HTMLImageElement;
  target.src = 'https://api.dicebear.com/7.x/avataaars/svg?seed=student';
};

onMounted(() => {
  prefetchStudentPortalData();
});
</script>

<template>
  <div class="flex min-h-screen bg-[#F8FAFC] dark:bg-[#0F172A]">
    <!-- Sidebar -->
    <aside class="w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col fixed inset-y-0 left-0 z-50">
      <div class="p-6 flex items-center gap-3">
        <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-500/20">
          <BadgeCheck :size="24" />
        </div>
        <span class="text-xl font-bold tracking-tight dark:text-white">Attendance Students</span>
      </div>
      
      <nav class="flex-1 px-4 space-y-2 mt-4">
        <router-link
          v-for="item in navItems"
          :key="item.id"
          :to="item.path"
          :class="[
            'w-full flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all',
            route.path === item.path || (route.path.startsWith(item.path) && item.path !== '/student/dashboard')
              ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20' 
              : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800'
          ]"
        >
          <component :is="item.icon" :size="20" />
          {{ item.label }}
        </router-link>
      </nav>

      <div class="p-4 border-t border-slate-100 dark:border-slate-800">
        <button 
          @click="handleLogout"
          class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all"
        >
          <LogOut :size="20" />
          Logout
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-64 flex flex-col min-h-screen">
      <!-- Header -->
      <header class="h-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-8 sticky top-0 z-40">
        <div class="flex items-center gap-4 bg-white px-4 py-2 rounded-2xl w-96 border border-slate-200">
          <Search class="text-slate-400" :size="18" />
          <input 
            type="text" 
            placeholder="Search courses, records..." 
            class="bg-transparent border-none outline-none text-sm w-full"
          />
        </div>

        <div class="flex items-center gap-6">
          <div class="flex items-center gap-2">
            <button class="p-2.5 text-slate-500 hover:bg-slate-100 rounded-xl transition-all relative">
              <Bell :size="20" />
              <span class="absolute top-2 right-2 w-2 h-2 bg-blue-500 rounded-full border-2 border-white"></span>
            </button>
          </div>
          
          <div class="h-8 w-px bg-slate-200"></div>
          
        <div class="flex items-center gap-3">
          <div class="text-right">
            <p class="text-sm font-bold">{{ user?.fullname || user?.username || 'Student' }}</p>
            <p class="text-[10px] text-slate-500 font-mono">ID: {{ user?.id || 'N/A' }}</p>
          </div>
          <img 
            :src="user?.avatar || 'https://api.dicebear.com/7.x/avataaars/svg?seed=student'" 
            class="w-10 h-10 rounded-xl object-cover ring-2 ring-blue-500/10"
            @error="onImageError"
          />
        </div>
        </div>
      </header>

      <!-- Content Area -->
      <div class="flex-1">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </main>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateX(10px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateX(-10px);
}
</style>
