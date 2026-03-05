<script setup lang="ts">
import { Search, Bell, Settings, LogOut } from 'lucide-vue-next';
import { ViewType } from './Sidebar.vue';

interface User {
  name: string;
  role: 'teacher' | 'admin';
  department?: string;
  photo?: string;
}

defineProps<{
  user: User;
}>();

const emit = defineEmits<{
  (e: 'navigate', view: ViewType): void;
  (e: 'logout'): void;
}>();
</script>

<template>
  <header class="h-16 border-b border-slate-200 bg-white flex items-center justify-between px-8 sticky top-0 z-10">
    <div class="flex items-center gap-4 flex-1">
      <div class="relative w-full max-w-md">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
        <input 
          type="text" 
          placeholder="Search students, classes, or reports..."
          class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
        />
      </div>
    </div>
    
    <div class="flex items-center gap-4">
      <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 rounded-full border border-slate-100">
        <div class="size-2 bg-emerald-500 rounded-full animate-pulse" />
        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ user.role }} mode</span>
      </div>
      <button 
        @click="emit('navigate', 'notifications')"
        class="relative p-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors"
      >
        <Bell :size="20" />
        <span class="absolute top-2 right-2 size-2 bg-red-500 rounded-full border-2 border-white"></span>
      </button>
      <button 
        @click="emit('navigate', 'settings')"
        class="p-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors"
      >
        <Settings :size="20" />
      </button>
      <button
        @click="emit('logout')"
        class="p-2 rounded-lg text-rose-600 hover:bg-rose-50 transition-colors"
        title="Logout"
      >
        <LogOut :size="20" />
      </button>
    </div>
  </header>
</template>
