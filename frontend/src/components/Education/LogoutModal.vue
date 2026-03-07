<script setup lang="ts">
import { LogOut } from 'lucide-vue-next';
import { cn } from '../../utils/cn';

const props = defineProps<{
  isOpen: boolean;
  isLoading?: boolean;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'confirm'): void;
}>();
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden">
      <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-gradient-to-r from-rose-50 to-rose-100">
        <div class="flex items-center gap-3">
          <div class="size-12 rounded-full bg-rose-500/20 flex items-center justify-center">
            <LogOut class="size-6 text-rose-600" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900">Logout Confirmation</h3>
            <p class="text-sm text-slate-600">Are you sure you want to logout?</p>
          </div>
        </div>
        <button @click="emit('close')" class="p-2 hover:bg-slate-200 rounded-full transition-colors">
          <svg class="size-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <div class="p-6 space-y-4">
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
          <p class="text-sm text-slate-600">
            You will be signed out of your account and all session data will be cleared. You'll need to sign in again to access your dashboard.
          </p>
        </div>
        
        <div class="flex items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-xl">
          <div class="size-6 rounded-full bg-amber-500/20 flex items-center justify-center">
            <svg class="size-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <p class="text-sm text-amber-700 font-medium">This action cannot be undone</p>
        </div>
      </div>

      <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex gap-4">
        <button 
          @click="emit('close')"
          class="flex-1 py-3 rounded-2xl text-sm font-bold text-slate-600 hover:bg-slate-200 transition-all"
          :disabled="isLoading"
        >
          Cancel
        </button>
        <button 
          @click="emit('confirm')"
          class="flex-1 py-3 rounded-2xl text-sm font-bold bg-rose-500 text-white hover:bg-rose-600 shadow-lg shadow-rose-200 transition-all flex items-center justify-center gap-2"
          :disabled="isLoading"
        >
          <LogOut :size="18" />
          {{ isLoading ? 'Logging out...' : 'Logout' }}
        </button>
      </div>
    </div>
  </div>
</template>