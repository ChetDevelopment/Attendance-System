<script setup lang="ts">
import { 
  LayoutDashboard, 
  ClipboardCheck, 
  BarChart3, 
  AlertTriangle,
  UserCircle,
  ShieldCheck,
  Network,
  Users,
  Calendar,
  ClipboardList,
  Settings,
  LogOut
} from 'lucide-vue-next';
import { cn } from '../../utils/cn';
import { clearAllAuthData, logout } from '../../services/auth';
import { ref } from 'vue';
import LogoutModal from './LogoutModal.vue';

const props = defineProps<{
  activeNav: string;
}>();

const emit = defineEmits<{
  (e: 'update:activeNav', name: string): void;
}>();

const isLogoutModalOpen = ref(false);

const navItems = [
  { name: 'Dashboard', icon: LayoutDashboard },
  { name: 'Absence Follow-up', icon: ClipboardCheck },
  { name: 'Reports', icon: BarChart3 },
  { name: 'Risk Monitoring', icon: AlertTriangle },
];
</script>

<template>
  <aside class="fixed h-full w-64 flex flex-col bg-white border-r border-slate-200">
    <div class="p-6 border-b border-slate-200 flex items-center gap-3">
      <div class="size-10 rounded-lg border border-slate-200 bg-white flex items-center justify-center p-1.5">
        <img src="/PictureUseInPageLogin.png" alt="Website logo" class="size-7 object-contain" />
      </div>
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900">វត្តមាន-Attendance</h1>
        <p class="text-[10px] text-slate-500 font-bold tracking-wider">វត្តមាន-Attendance</p>
      </div>
    </div>

    <nav class="flex-1 px-4 space-y-1">
      <button
        v-for="item in navItems"
        :key="item.name"
        @click="emit('update:activeNav', item.name)"
        :class="cn(
          'w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 group',
          activeNav === item.name 
            ? 'bg-gradient-to-r from-primary to-primary/80 text-white shadow-lg shadow-primary/20' 
            : 'text-slate-700 hover:bg-primary/10 hover:text-primary hover:shadow-md'
        )"
      >
        <component :is="item.icon" :class="[
          'size-5 transition-all duration-300',
          activeNav === item.name ? 'text-white' : 'text-slate-500 group-hover:text-primary'
        ]" />
        <span class="text-sm font-semibold tracking-wide">{{ item.name }}</span>
      </button>
    </nav>

    <div class="p-4 border-t border-slate-200 space-y-3">
      <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
        <div class="size-8 rounded-full bg-primary/20 flex items-center justify-center">
          <UserCircle class="size-4 text-primary" />
        </div>
        <div>
          <p class="text-xs font-bold text-slate-900">Education Team</p>
          <p class="text-[10px] text-slate-500">Attendance Mgmt</p>
        </div>
      </div>
      
      <button 
        @click="isLogoutModalOpen = true"
        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 transition-all"
      >
        <LogOut :size="16" />
        Logout
      </button>
    </div>

    <LogoutModal 
      :isOpen="isLogoutModalOpen" 
      @close="isLogoutModalOpen = false"
      @confirm="logout"
    />
  </aside>
</template>
