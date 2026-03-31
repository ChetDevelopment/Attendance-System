<script setup lang="ts">
import {
  LayoutDashboard,
  ClipboardCheck,
  BarChart3,
  AlertTriangle,
  UserCircle,
  LogOut,
} from 'lucide-vue-next'
import { ref } from 'vue'
import { cn } from '../../utils/cn'
import { logout } from '../../services/auth'
import { setImageFallback } from '../../utils/imageFallback'
import LogoutModal from './LogoutModal.vue'

defineProps<{
  activeNav: string
  user?: any
}>()

const emit = defineEmits<{
  (e: 'update:activeNav', name: string): void
}>()

const isLogoutModalOpen = ref(false)

const navItems = [
  { name: 'Dashboard', icon: LayoutDashboard },
  { name: 'Absence Follow-up', icon: ClipboardCheck },
  { name: 'Reports', icon: BarChart3 },
  { name: 'Risk Monitoring', icon: AlertTriangle },
]
</script>

<template>
  <aside class="sticky top-0 flex h-screen w-64 flex-col border-r border-slate-200 bg-white">
    <div class="flex items-center gap-3 p-6">
      <div class="flex size-8 items-center justify-center rounded-lg bg-primary text-white">
        <LayoutDashboard :size="18" />
      </div>
      <div>
        <h2 class="text-xl font-bold tracking-tight text-primary">AttendancePro</h2>
        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">
          Education Portal
        </p>
      </div>
    </div>

    <nav class="flex-1 space-y-1 px-4">
      <button
        v-for="item in navItems"
        :key="item.name"
        @click="emit('update:activeNav', item.name)"
        :class="cn(
          'w-full flex items-center gap-3 rounded-lg px-3 py-2 transition-colors',
          activeNav === item.name ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100',
        )"
      >
        <component
          :is="item.icon"
          :class="['size-5', activeNav === item.name ? 'text-white' : 'text-slate-500']"
        />
        <span class="font-medium">{{ item.name }}</span>
      </button>
    </nav>

    <div class="border-t border-slate-200 p-4">
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
        <div class="flex items-center gap-3">
          <div class="flex size-10 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-white text-slate-400">
            <img
              v-if="user?.avatar_url || user?.profile_image"
              :src="user?.avatar_url || user?.profile_image"
              :alt="user?.name || 'Education user'"
              class="size-full object-cover"
              referrerPolicy="no-referrer"
              @error="(event) => setImageFallback(event, '/default-student.svg')"
            />
            <UserCircle v-else :size="20" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-slate-900">
              {{ user?.name || 'Education Team' }}
            </p>
            <p class="truncate text-xs text-slate-500">
              {{ user?.department || user?.role?.name || user?.role || 'Attendance Management' }}
            </p>
          </div>
        </div>
      </div>

      <button
        @click="isLogoutModalOpen = true"
        class="mt-3 flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-rose-600 transition-colors hover:bg-rose-50"
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
