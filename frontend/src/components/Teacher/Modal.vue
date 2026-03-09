<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { cn } from '../lib/utils';

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
  sm: 'max-w-md',
  md: 'max-w-2xl',
  lg: 'max-w-4xl',
  xl: 'max-w-6xl',
};
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
        <!-- Backdrop -->
        <div 
          class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
          @click="emit('close')"
        />
        
        <!-- Modal Content -->
        <Transition
          appear
          enter-active-class="transition duration-300 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-4"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-95 translate-y-4"
        >
          <div
            :class="cn(
              'relative bg-white w-full rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]',
              sizeClasses[size]
            )"
          >
            <div class="p-6 border-b border-slate-100 flex items-center justify-between shrink-0">
              <h3 class="text-xl font-bold text-slate-900">{{ title }}</h3>
              <button
                @click="emit('close')"
                class="p-2 hover:bg-slate-100 rounded-full transition-colors text-slate-400 hover:text-slate-600"
              >
                <X :size="20" />
              </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
              <slot />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
