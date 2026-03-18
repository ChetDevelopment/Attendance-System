<script setup lang="ts">
import {
  LayoutDashboard,
  ShieldCheck,
  Network,
  Users,
  Calendar,
  Settings,
  Headphones,
  ClipboardList,
  User,
  Menu,
  X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { getUserRole } from '../services/auth';

defineProps<{
  currentModule: string;
}>();

const emit = defineEmits<{
  moduleChange: [module: string];
}>();

const isCollapsed = ref(false);
const userRole = computed(() => getUserRole());

const navItems = computed(() => {
  if (userRole.value === 'student') {
    return [
      { id: 'dashboard', icon: LayoutDashboard, label: 'Dashboard' },
      { id: 'attendance', icon: Calendar, label: 'Attendance' },
      { id: 'absences', icon: ClipboardList, label: 'Absences' },
    ];
  } else if (userRole.value === 'teacher') {
    return [
      { id: 'dashboard', icon: LayoutDashboard, label: 'Dashboard' },
      { id: 'attendance', icon: Calendar, label: 'Attendance Control' },
      { id: 'absences', icon: ClipboardList, label: 'Absence Management' },
    ];
  } else if (userRole.value === 'education') {
    return [
      { id: 'dashboard', icon: LayoutDashboard, label: 'Dashboard' },
      { id: 'users', icon: ShieldCheck, label: 'User & Permission' },
      { id: 'academic', icon: Network, label: 'Academic Structure' },
      { id: 'students', icon: Users, label: 'Student Management' },
      { id: 'attendance', icon: Calendar, label: 'Attendance Control' },
      { id: 'absences', icon: ClipboardList, label: 'Absence Management' },
      { id: 'settings', icon: Settings, label: 'System Settings' },
    ];
  } else {
    // Admin role
    return [
      { id: 'dashboard', icon: LayoutDashboard, label: 'Dashboard' },
      { id: 'users', icon: ShieldCheck, label: 'User & Permission' },
      { id: 'academic', icon: Network, label: 'Academic Structure' },
      { id: 'students', icon: Users, label: 'Student Management' },
      { id: 'attendance', icon: Calendar, label: 'Attendance Control' },
      { id: 'absences', icon: ClipboardList, label: 'Absence Management' },
      { id: 'settings', icon: Settings, label: 'System Settings' },
      { id: 'profile', icon: User, label: 'My Profile' },
    ];
  }
});

const onSelect = (module: string) => emit('moduleChange', module);
</script>

<template>
  <aside 
    class="flex-shrink-0 flex flex-col h-screen transition-all duration-300 ease-in-out"
    :class="isCollapsed ? 'w-16' : 'w-64'"
  >
    <!-- Header Section -->
    <div 
      class="flex items-center justify-between p-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white"
      :class="isCollapsed ? 'justify-center' : ''"
    >
      <div 
        v-if="!isCollapsed" 
        class="flex items-center gap-3"
      >
        <div class="size-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center shadow-sm">
          <img src="/PictureUseInPageLogin.png" alt="Logo" class="size-7 object-contain" />
        </div>
        <div>
          <h1 class="text-sm font-bold text-slate-900 tracking-tight">វត្តមាន</h1>
          <p class="text-xs text-slate-500 font-medium">Attendance</p>
        </div>
      </div>
      
      <button
        @click="isCollapsed = !isCollapsed"
        class="p-2 rounded-lg hover:bg-slate-100 transition-colors group"
        :class="isCollapsed ? 'mx-auto' : ''"
      >
        <Menu v-if="!isCollapsed" class="size-5 text-slate-600 group-hover:text-slate-900" />
        <X v-else class="size-5 text-slate-600 group-hover:text-slate-900" />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto p-3">
      <div class="space-y-1">
        <button
          v-for="item in navItems"
          :key="item.id"
          @click="onSelect(item.id)"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group hover:bg-slate-100"
          :class="[
            currentModule === item.id 
              ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/20' 
              : 'text-slate-600 hover:text-slate-900'
          ]"
        >
          <component
            :is="item.icon"
            class="size-5 transition-colors"
            :class="currentModule === item.id ? 'text-white' : 'text-slate-500 group-hover:text-blue-600'"
          />
          <span 
            v-if="!isCollapsed" 
            class="text-sm font-medium whitespace-nowrap"
          >
            {{ item.label }}
          </span>
        </button>
      </div>
    </nav>

    <!-- Help Desk Section -->
    <div class="p-3 border-t border-slate-200 bg-gradient-to-b from-slate-50/50 to-transparent">
      <div class="bg-white rounded-xl border border-slate-200 p-3 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3">
          <div class="size-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg">
            <Headphones class="size-5 text-white" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-slate-900 truncate">Help Desk</p>
            <p class="text-xs text-slate-500 truncate">Contact Support</p>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>

