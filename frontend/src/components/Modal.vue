<script setup lang="ts">
import { cn } from '@/lib/utils';
import { X } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
  isOpen: boolean;
  title: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
}>(), {
  size: 'md'
});

const emit = defineEmits<{
  (e: 'close'): void;
}>();

const sizeClasses = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
};
</script>

<template>
  <teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div :class="cn('bg-white rounded-2xl shadow-2xl w-full overflow-hidden flex flex-col animate-in fade-in zoom-in duration-200', sizeClasses[size])">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-900">{{ title }}</h3>
          <button @click="emit('close')" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
            <X class="size-5 text-slate-500" />
          </button>
        </div>
        
        <div class="p-6 overflow-y-auto max-h-[80vh]">
          <slot></slot>
        </div>

        <div v-if="$slots.footer" class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
          <slot name="footer"></slot>
        </div>
      </div>
    </div>
  </teleport>
</template>
