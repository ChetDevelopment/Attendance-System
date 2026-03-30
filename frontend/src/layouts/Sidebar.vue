<script setup lang="ts">
import {
  LayoutDashboard,
  ShieldCheck,
  Network,
  Users,
  Calendar,
  Fingerprint,
  Settings,
  ClipboardList,
  User,
  UserCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { getUser, getUserRole } from '../services/auth';
import { setImageFallback } from '../utils/imageFallback';

const props = defineProps<{
  currentModule: string;
}>();

const emit = defineEmits<{
  moduleChange: [module: string];
}>();

const userRole = computed(() => getUserRole());
const currentUser = computed(() => getUser());

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
      { id: 'sessions', icon: Calendar, label: 'Session Management' },
      { id: 'biometric', icon: Fingerprint, label: 'Biometric Management' },
      { id: 'absences', icon: ClipboardList, label: 'Absence Management' },
      { id: 'settings', icon: Settings, label: 'System Settings' },
    ];
  }

  return [
    { id: 'dashboard', icon: LayoutDashboard, label: 'Dashboard' },
    { id: 'users', icon: ShieldCheck, label: 'User & Permission' },
    { id: 'academic', icon: Network, label: 'Academic Structure' },
    { id: 'students', icon: Users, label: 'Student Management' },
    { id: 'attendance', icon: Calendar, label: 'Attendance Control' },
    { id: 'biometric', icon: Fingerprint, label: 'Biometric Management' },
    { id: 'absences', icon: ClipboardList, label: 'Absence Management' },
    { id: 'settings', icon: Settings, label: 'System Settings' },
    { id: 'profile', icon: User, label: 'My Profile' },
  ];
});

const workspaceLabel = computed(() => {
  if (userRole.value === 'teacher') return 'Teacher Workspace';
  if (userRole.value === 'education') return 'Education Workspace';
  if (userRole.value === 'student') return 'Student Workspace';
  return 'Admin Workspace';
});

const profileLabel = computed(
  () =>
    currentUser.value?.department
    || currentUser.value?.role?.name
    || currentUser.value?.role
    || workspaceLabel.value,
);

const profileImage = computed(
  () => currentUser.value?.avatar_url || currentUser.value?.profile_image || null,
);

const onSelect = (module: string) => emit('moduleChange', module);
</script>

<template>
  <aside class="sticky top-0 flex h-screen w-64 flex-shrink-0 flex-col border-r border-slate-200 bg-white">
    <div class="border-b border-slate-200 p-6">
      <div class="flex items-center gap-3">
        <div class="flex size-8 items-center justify-center rounded-lg bg-primary text-white">
          <LayoutDashboard class="size-4" />
        </div>
        <div>
          <h2 class="text-xl font-bold tracking-tight text-primary">Attendance</h2>
          <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">
            {{ workspaceLabel }}
          </p>
        </div>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-4">
      <div class="space-y-1.5">
        <button
          v-for="item in navItems"
          :key="item.id"
          @click="onSelect(item.id)"
          class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition-colors"
          :class="[
            props.currentModule === item.id
              ? 'bg-primary text-white shadow-lg shadow-primary/20'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
          ]"
        >
          <component
            :is="item.icon"
            class="size-5"
            :class="props.currentModule === item.id ? 'text-white' : 'text-slate-500'"
          />
          <span class="text-sm font-medium">{{ item.label }}</span>
        </button>
      </div>
    </nav>

    <div class="border-t border-slate-200 p-4">
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
        <div class="flex items-center gap-3">
          <div class="flex size-10 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-white text-slate-400">
            <img
              v-if="profileImage"
              :src="profileImage"
              :alt="currentUser?.name || 'User profile'"
              class="size-full object-cover"
              referrerpolicy="no-referrer"
              @error="(event) => setImageFallback(event, '/default-teacher.svg')"
            />
            <UserCircle v-else class="size-5" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-slate-900">
              {{ currentUser?.name || 'Administrator' }}
            </p>
            <p class="truncate text-xs text-slate-500">{{ profileLabel }}</p>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>
