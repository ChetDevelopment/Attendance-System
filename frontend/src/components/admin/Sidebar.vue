<script setup lang="ts">
import { 
  LayoutDashboard,
  ShieldCheck, 
  Network, 
  Users, 
  Calendar, 
  Settings, 
  Headset 
} from 'lucide-vue-next';
import { cn } from '@/lib/utils';

defineProps<{
  currentModule: string;
}>();

const emit = defineEmits<{
  (e: 'moduleChange', module: string): void;
}>();

const navItems = [
  { id: 'dashboard', icon: LayoutDashboard, label: 'Dashboard' },
  { id: 'users', icon: ShieldCheck, label: 'User & Permission' },
  { id: 'academic', icon: Network, label: 'Academic Structure' },
  { id: 'students', icon: Users, label: 'Student Management' },
  { id: 'attendance', icon: Calendar, label: 'Attendance Control' },
  { id: 'settings', icon: Settings, label: 'System Settings' },
];
</script>

<template>
  <aside class="w-64 flex-shrink-0 bg-white border-r border-slate-200 flex flex-col h-screen">
    <div class="p-6 border-b border-slate-200 flex items-center gap-3">
      <div class="size-10 bg-primary rounded-lg flex items-center justify-center text-white">
        <span class="font-black text-xl">Σ</span>
      </div>
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900">EduAttend</h1>
        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Principal Suite</p>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto p-4 space-y-1">
      <button
        v-for="item in navItems"
        :key="item.id"
        @click="emit('moduleChange', item.id)"
        :class="cn(
          'w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors group',
          currentModule === item.id 
            ? 'bg-primary text-white shadow-sm' 
            : 'text-slate-600 hover:bg-primary/10 hover:text-primary'
        )"
      >
        <component 
          :is="item.icon" 
          :class="cn('size-5', currentModule === item.id ? 'text-white' : 'text-slate-500 group-hover:text-primary')" 
        />
        <span class="text-sm font-semibold">{{ item.label }}</span>
      </button>
    </nav>

    <div class="p-4 border-t border-slate-200">
      <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
        <div class="size-8 rounded-full bg-primary/20 flex items-center justify-center">
          <Headset class="size-4 text-primary" />
        </div>
        <div>
          <p class="text-xs font-bold text-slate-900">Help Desk</p>
          <p class="text-[10px] text-slate-500">Contact Support</p>
        </div>
      </div>
    </div>
  </aside>
</template>
