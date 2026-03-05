<script setup lang="ts">
import { 
  LayoutDashboard, 
  ClipboardCheck, 
  BarChart3, 
  AlertTriangle,
  UserCircle
} from 'lucide-vue-next';
import { cn } from '../../utils/cn';

defineProps<{
  activeNav: string;
}>();

const emit = defineEmits<{
  (e: 'update:activeNav', name: string): void;
}>();

const navItems = [
  { name: 'Dashboard', icon: LayoutDashboard },
  { name: 'Absence Follow-up', icon: ClipboardCheck },
  { name: 'Reports', icon: BarChart3 },
  { name: 'Risk Monitoring', icon: AlertTriangle },
];
</script>

<template>
  <aside class="fixed h-full w-64 flex flex-col bg-white border-r border-slate-200">
    <div class="p-6 flex items-center gap-3">
      <div class="bg-[#135bec] rounded-lg p-2 text-white">
        <LayoutDashboard :size="24" />
      </div>
      <div>
        <h1 class="text-sm font-bold leading-tight">Education Team</h1>
        <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">Attendance Mgmt</p>
      </div>
    </div>

    <nav class="flex-1 px-4 space-y-1">
      <button
        v-for="item in navItems"
        :key="item.name"
        @click="emit('update:activeNav', item.name)"
        :class="cn(
          'w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200',
          activeNav === item.name 
            ? 'bg-[#135bec]/10 text-[#135bec] font-semibold' 
            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
        )"
      >
        <component :is="item.icon" :size="20" />
        <span class="text-sm">{{ item.name }}</span>
      </button>
    </nav>

    <div class="p-4 border-t border-slate-100">
      <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 transition-colors cursor-pointer group">
        <div class="size-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
          <UserCircle :size="20" />
        </div>
        <div class="flex-1 overflow-hidden">
          <p class="text-xs font-bold truncate">Sarah Jenkins</p>
          <p class="text-[10px] text-slate-500 truncate">Lead Coordinator</p>
        </div>
      </div>
    </div>
  </aside>
</template>
