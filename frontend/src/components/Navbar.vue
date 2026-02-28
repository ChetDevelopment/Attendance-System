<script setup lang="ts">
import { ref } from 'vue';
import { Search, Bell, LogOut, User, Settings } from 'lucide-vue-next';

const emit = defineEmits<{
  (e: 'navigate', module: string): void;
}>();

const isProfileOpen = ref(false);
const isNotificationsOpen = ref(false);

const notifications = [
  { id: 1, text: 'New student registration: Alice Wonder', time: '2m ago' },
  { id: 2, text: 'Attendance report for 10A is ready', time: '15m ago' },
  { id: 3, text: 'System backup completed successfully', time: '1h ago' },
];

const handleNavigate = (module: string) => {
  emit('navigate', module);
  isProfileOpen.value = false;
};
</script>

<template>
  <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8 z-20">
    <div class="flex items-center flex-1 max-w-md">
      <div class="relative w-full">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-5" />
        <input
          type="text"
          placeholder="Search student records..."
          class="w-full bg-slate-100 border-none rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-2 focus:ring-primary/50 transition-all outline-none"
        />
      </div>
    </div>

    <div class="flex items-center gap-4">
      <div class="relative">
        <button 
          @click="isNotificationsOpen = !isNotificationsOpen"
          class="relative p-2 text-slate-500 hover:bg-slate-100 rounded-lg transition-colors"
        >
          <Bell class="size-5" />
          <span class="absolute top-2 right-2 size-2 bg-red-500 rounded-full border-2 border-white"></span>
        </button>

        <div v-if="isNotificationsOpen" class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-xl shadow-xl py-2 z-30">
          <div class="px-4 py-2 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">Notifications</span>
            <span class="text-[10px] text-primary font-bold cursor-pointer hover:underline">Mark all read</span>
          </div>
          <div class="max-h-64 overflow-y-auto">
            <div v-for="n in notifications" :key="n.id" class="px-4 py-3 hover:bg-slate-50 border-b border-slate-50 last:border-0 cursor-pointer">
              <p class="text-xs text-slate-800 font-medium">{{ n.text }}</p>
              <p class="text-[10px] text-slate-400 mt-1">{{ n.time }}</p>
            </div>
          </div>
          <div class="px-4 py-2 text-center">
            <button class="text-[10px] text-slate-500 font-bold hover:text-primary transition-colors">View All Notifications</button>
          </div>
        </div>
      </div>
      
      <div class="h-6 w-px bg-slate-200"></div>
      
      <div class="relative">
        <button 
          @click="isProfileOpen = !isProfileOpen"
          class="flex items-center gap-3 hover:bg-slate-50 p-1 rounded-lg transition-colors"
        >
          <div class="text-right hidden sm:block">
            <p class="text-sm font-bold leading-tight text-slate-900">Dr. Albus Percival</p>
            <p class="text-[10px] text-slate-500 font-medium">Head Principal</p>
          </div>
          <div class="size-10 rounded-full bg-slate-200 overflow-hidden border border-slate-200">
            <img
              src="https://lh3.googleusercontent.com/aida-public/AB6AXuAbXECj7WizmOmdvL9LkjbK4vxAB5Dn8aN-rjhQMLwjxTw5YSwShgy-crK4IfjXlEiZykHVu-PzU827D4DQM6yEyA5Q81pNdPTKQzfPOyoWMDhJ4YzZWA2EeP7IT9941CBBJSRln9A70TLDDMksFnqtl8Q8FOnmFqBxFP7XXX6Ayh-N1aHKyNrwF3VFUGsTY0EK4m03TelucYGY4c1IV3-Vl2gMQkZssmoQwS4yel-OCT1Z6sJbi-yckWfY6zoRYGmm8K0jBZCSlFY"
              alt="Principal profile"
              class="w-full h-full object-cover"
              referrerPolicy="no-referrer"
            />
          </div>
        </button>

        <div v-if="isProfileOpen" class="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-xl py-2 z-30">
          <button 
            @click="handleNavigate('profile')"
            class="w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2"
          >
            <User class="size-4" />
            My Profile
          </button>
          <button 
            @click="handleNavigate('settings')"
            class="w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2"
          >
            <Settings class="size-4" />
            Settings
          </button>
          <div class="h-px bg-slate-100 my-1"></div>
          <button class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
            <LogOut class="size-4" />
            Logout
          </button>
        </div>
      </div>

      <button class="p-2 text-slate-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors sm:hidden">
        <LogOut class="size-5" />
      </button>
    </div>
  </header>
</template>
