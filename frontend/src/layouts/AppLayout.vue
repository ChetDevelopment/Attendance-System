<<<<<<< HEAD
<<<<<<< HEAD
<template>
  <div class="flex h-screen overflow-hidden bg-background-light">
    <Sidebar :current-module="currentModule" @module-change="setCurrentModule" />

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <Navbar @navigate="setCurrentModule" />

      <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
        <Transition name="module-fade" mode="out-in">
          <component :is="activeModule" :key="currentModule" />
        </Transition>
=======
<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { 
  LayoutDashboard, 
  QrCode, 
  History, 
  Settings, 
  Search, 
  Bell, 
  BadgeCheck,
  LogOut
} from 'lucide-vue-next';
import { studentProfile, logout } from '../services/auth';

const router = useRouter();
const route = useRoute();

const navItems = [
  { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, path: '/dashboard' },
  { id: 'attendance', label: 'Self Attendance', icon: QrCode, path: '/attendance' },
  { id: 'history', label: 'Attendance History', icon: History, path: '/history' },
  { id: 'settings', label: 'Settings', icon: Settings, path: '/settings' },
];

const handleLogout = () => {
  logout();
  router.push('/dashboard');
=======
<script setup lang="ts">
import { useRouter } from 'vue-router';
import { 
  LayoutDashboard as LayoutDashboardIcon, 
  LogOut as LogOutIcon, 
  Users as UsersIcon, 
  Calendar as CalendarIcon 
} from 'lucide-vue-next';

interface UserData {
  email: string;
  role: 'student' | 'admin';
  name: string;
}

defineProps<{
  title: string;
  user: UserData;
}>();

const router = useRouter();

const handleLogout = () => {
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user_data');
  router.push('/login');
>>>>>>> feature/login
};
</script>

<template>
<<<<<<< HEAD
  <div class="flex min-h-screen bg-[#F8FAFC] dark:bg-[#0F172A]">
    <!-- Sidebar -->
    <aside class="w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col fixed inset-y-0 left-0 z-50">
      <div class="p-6 flex items-center gap-3">
        <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary/20">
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
            route.path === item.path 
              ? 'bg-primary text-white shadow-lg shadow-primary/20' 
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
          class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"
        >
          <LogOut :size="20" />
=======
  <div class="min-h-screen bg-slate-950 flex">
    <!-- Sidebar -->
    <aside class="w-64 border-r border-slate-800 bg-slate-900/50 hidden md:flex flex-col">
      <div class="p-6 border-bottom border-slate-800">
        <div class="flex items-center gap-3">
          <div class="bg-white p-1 rounded shadow-sm">
            <img 
              src="https://www.passerellesnumeriques.org/wp-content/uploads/2016/03/pn-logo.png" 
              alt="PNC Logo" 
              class="h-6 w-auto"
              referrerPolicy="no-referrer"
            />
          </div>
          <span class="text-white font-bold text-sm">PNC Portal</span>
        </div>
      </div>
      
      <nav class="flex-1 p-4 space-y-2">
        <router-link 
          to="/admin" 
          v-if="user.role === 'admin'"
          class="w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:bg-slate-800 hover:text-white rounded-xl font-medium transition-colors"
          active-class="bg-pnc-blue/10 text-pnc-blue"
        >
          <LayoutDashboardIcon class="h-5 w-5" />
          Dashboard
        </router-link>
        <router-link 
          to="/student" 
          v-if="user.role === 'student'"
          class="w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:bg-slate-800 hover:text-white rounded-xl font-medium transition-colors"
          active-class="bg-pnc-blue/10 text-pnc-blue"
        >
          <LayoutDashboardIcon class="h-5 w-5" />
          Dashboard
        </router-link>
        <router-link 
          to="/attendance"
          class="w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:bg-slate-800 hover:text-white rounded-xl font-medium transition-colors"
          active-class="bg-pnc-blue/10 text-pnc-blue"
        >
          <CalendarIcon class="h-5 w-5" />
          Attendance
        </router-link>
        <button v-if="user.role === 'admin'" class="w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:bg-slate-800 hover:text-white rounded-xl font-medium transition-colors">
          <UsersIcon class="h-5 w-5" />
          Students
        </button>
      </nav>

      <div class="p-4 border-t border-slate-800">
        <button 
          @click="handleLogout"
          class="w-full flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-red-500/10 rounded-xl font-medium transition-colors"
        >
          <LogOutIcon class="h-5 w-5" />
>>>>>>> feature/login
          Logout
        </button>
      </div>
    </aside>

    <!-- Main Content -->
<<<<<<< HEAD
    <main class="flex-1 ml-64 flex flex-col min-h-screen">
      <!-- Header -->
      <header class="h-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-8 sticky top-0 z-40">
        <div class="flex items-center gap-4 bg-slate-100 dark:bg-slate-800 px-4 py-2 rounded-2xl w-96 border border-slate-200 dark:border-slate-700">
          <Search class="text-slate-400" :size="18" />
          <input 
            type="text" 
            placeholder="Search courses, records..." 
            class="bg-transparent border-none outline-none text-sm w-full dark:text-white"
          />
        </div>

        <div class="flex items-center gap-6">
          <div class="flex items-center gap-2">
            <button class="p-2.5 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all relative">
              <Bell :size="20" />
              <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white dark:border-slate-900"></span>
            </button>
          </div>
          
          <div class="h-8 w-px bg-slate-200 dark:bg-slate-800"></div>
          
          <div class="flex items-center gap-3">
            <div class="text-right">
              <p class="text-sm font-bold dark:text-white">{{ studentProfile.name }}</p>
              <p class="text-[10px] text-slate-500 font-mono">ID: {{ studentProfile.id }}</p>
            </div>
            <img 
              :src="studentProfile.avatar" 
              class="w-10 h-10 rounded-xl object-cover ring-2 ring-primary/10"
            />
=======
    <main class="flex-1 flex flex-col">
      <header class="h-16 border-b border-slate-800 bg-slate-900/30 flex items-center justify-between px-8">
        <h2 class="text-xl font-bold text-white">{{ title }}</h2>
        <div class="flex items-center gap-4">
          <div class="text-right hidden sm:block">
            <p class="text-sm font-bold text-white leading-none">{{ user.name }}</p>
            <p class="text-[10px] text-slate-500 uppercase tracking-widest mt-1">{{ user.role }}</p>
          </div>
          <div class="w-10 h-10 rounded-full bg-pnc-blue/20 border border-pnc-blue/30 flex items-center justify-center text-pnc-blue font-bold">
            {{ user.name.charAt(0) }}
>>>>>>> feature/login
          </div>
        </div>
      </header>

<<<<<<< HEAD
      <!-- Content Area -->
      <div class="flex-1">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
>>>>>>> feature/student-Dashboard
=======
      <div class="flex-1 p-8 overflow-y-auto">
        <slot />
>>>>>>> feature/login
      </div>
    </main>
  </div>
</template>

<<<<<<< HEAD
<script setup lang="ts">
import { computed, ref } from 'vue';
import Sidebar from './Sidebar.vue';
import Navbar from './Navbar.vue';
import Dashboard from '../components/admin/Dashboard.vue';
import UserManagement from '../components/admin/UserManagement.vue';
import AcademicStructure from '../components/admin/AcademicStructure.vue';
import StudentManagement from '../components/admin/StudentManagement.vue';
import AttendanceControl from '../components/admin/AttendanceControl.vue';
import SystemSettings from '../components/admin/SystemSettings.vue';
import Profile from '../components/admin/Profile.vue';

const currentModule = ref('dashboard');

const moduleMap = {
  dashboard: Dashboard,
  users: UserManagement,
  academic: AcademicStructure,
  students: StudentManagement,
  attendance: AttendanceControl,
  settings: SystemSettings,
  profile: Profile,
} as const;

const activeModule = computed(
  () => moduleMap[currentModule.value as keyof typeof moduleMap] ?? Dashboard
);

const setCurrentModule = (module: string) => {
  currentModule.value = module;
};
</script>

<style scoped>
.module-fade-enter-active,
.module-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.module-fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.module-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
=======
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
>>>>>>> feature/student-Dashboard
}
</style>
