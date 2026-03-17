<script setup lang="ts">
import { X, AlertTriangle } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    isOpen: boolean;
    title?: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    type?: 'danger' | 'warning' | 'info';
  }>(),
  {
    title: 'Confirm Action',
    message: 'Are you sure you want to proceed?',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    type: 'danger',
  }
);

const emit = defineEmits<{
  confirm: [];
  cancel: [];
}>();

const typeClasses = {
  danger: 'bg-red-600 hover:bg-red-700 text-white',
  warning: 'bg-amber-500 hover:bg-amber-600 text-white',
  info: 'bg-primary hover:bg-primary/90 text-white',
};

const iconColors = {
  danger: 'text-red-600',
  warning: 'text-amber-500',
  info: 'text-primary',
};
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
  >
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
      <div class="p-6 text-center">
        <div class="mx-auto mb-4 w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center">
          <AlertTriangle :class="['w-6 h-6', iconColors[type]]" />
        </div>
        <h3 class="text-lg font-bold text-slate-900 mb-2">{{ title }}</h3>
        <p class="text-sm text-slate-600">{{ message }}</p>
      </div>

      <div class="px-6 py-4 bg-slate-50 flex justify-end gap-3">
        <button
          @click="emit('cancel')"
          class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
        >
          {{ cancelText }}
        </button>
        <button
          @click="emit('confirm')"
          :class="[
            'px-4 py-2 text-sm font-bold rounded-lg transition-colors',
            typeClasses[type]
          ]"
        >
          {{ confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>
