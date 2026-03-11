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
};
</script>

<template>
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
          Logout
        </button>
      </div>
    </aside>

    <!-- Main Content -->
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
          </div>
        </div>
      </header>

      <div class="flex-1 p-8 overflow-y-auto">
        <slot />
      </div>
    </main>
  </div>
</template>
