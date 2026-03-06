<script setup lang="ts">
import { ref, computed } from 'vue';
import { 
  LayoutDashboard, 
  CalendarDays, 
  UserCheck, 
  Users, 
  History, 
  MessageSquare, 
  CheckCircle2,
  ChevronDown,
  LogOut
} from 'lucide-vue-next';
import { t } from '../composables/useSettings';

export type ViewType = 'dashboard' | 'schedule' | 'attendance' | 'history' | 'students' | 'messages' | 'management' | 'settings' | 'notifications';

export interface User {
  name: string;
  role: 'teacher' | 'admin';
  department?: string;
  photo?: string;
}

const props = defineProps<{
  currentView: ViewType;
  user: User;
  mockUsers: User[];
}>();

const emit = defineEmits<{
  (e: 'viewChange', view: ViewType): void;
  (e: 'userChange', user: User): void;
  (e: 'logout'): void;
}>();

const navItems = computed(() => [
  { icon: LayoutDashboard, label: t('dashboard'), id: 'dashboard' as ViewType },
  { icon: CalendarDays, label: t('schedule'), id: 'schedule' as ViewType },
  { icon: UserCheck, label: t('attendance'), id: 'attendance' as ViewType },
  { icon: History, label: t('history'), id: 'history' as ViewType },
  { icon: Users, label: t('management'), id: 'management' as ViewType },
  { icon: MessageSquare, label: t('messages'), id: 'messages' as ViewType },
]);
</script>

<template>
  <aside class="w-64 border-r border-slate-200 bg-white flex flex-col h-screen sticky top-0">
    <div class="p-6 flex items-center gap-3">
      <div class="size-8 bg-primary rounded-lg flex items-center justify-center text-white">
        <CheckCircle2 :size="20" />
      </div>
      <h2 class="text-xl font-bold tracking-tight text-primary">AttendancePro</h2>
    </div>

    <nav class="flex-1 px-4 space-y-1">
      <button
        v-for="item in navItems"
        :key="item.label"
        @click="$emit('viewChange', item.id)"
        :class="`w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors ${
          currentView === item.id 
            ? 'bg-primary text-white' 
            : 'text-slate-600 hover:bg-slate-100'
        }`"
      >
        <component :is="item.icon" :size="20" />
        <span class="font-medium">{{ item.label }}</span>
      </button>
    </nav>

    <div class="p-4 border-t border-slate-200">
      <div class="relative group">
        <button class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 transition-all">
          <img 
            class="size-10 rounded-full object-cover border border-slate-200" 
            :src="user.photo" 
            :alt="user.name"
            referrerPolicy="no-referrer"
          />
          <div class="flex flex-col text-left flex-1 min-w-0">
            <p class="text-sm font-bold truncate">{{ user.name }}</p>
            <p class="text-xs text-slate-500 truncate">{{ user.department }}</p>
          </div>
          <ChevronDown :size="14" class="text-slate-400" />
        </button>
        
        <!-- User Switcher Dropdown (for demo) -->
        <div class="absolute bottom-full left-0 w-full mb-2 bg-white border border-slate-200 rounded-xl shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50 p-2">
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-3 py-2">Switch Account</p>
          <button
            v-for="u in mockUsers"
            :key="u.name"
            @click="$emit('userChange', u)"
            :class="`w-full flex items-center gap-3 p-2 rounded-lg text-left transition-colors ${
              user.name === u.name ? 'bg-primary/5 text-primary' : 'hover:bg-slate-50'
            }`"
          >
            <img :src="u.photo" class="size-8 rounded-full object-cover" alt="" referrerPolicy="no-referrer" />
            <span class="text-xs font-bold">{{ u.name }}</span>
          </button>
          <div class="border-t border-slate-200 my-2"></div>
          <button
            @click="$emit('logout')"
            class="w-full flex items-center gap-3 p-2 rounded-lg text-left text-rose-600 hover:bg-rose-50 transition-colors"
          >
            <LogOut :size="16" />
            <span class="text-xs font-bold">Logout</span>
          </button>
        </div>
      </div>
    </div>
  </aside>
</template>
