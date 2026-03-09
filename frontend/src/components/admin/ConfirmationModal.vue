<script setup lang="ts">
import { computed } from 'vue';
import { AlertTriangle, X } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    isOpen: boolean;
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'danger' | 'warning' | 'info';
  }>(),
  {
    confirmText: 'Delete',
    cancelText: 'Cancel',
    variant: 'danger',
  }
);

const emit = defineEmits<{
  confirm: [];
  cancel: [];
}>();

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'warning':
      return {
        icon: 'text-amber-500',
        title: 'text-amber-900',
        bg: 'bg-amber-50',
        border: 'border-amber-200',
        confirm: 'bg-amber-500 hover:bg-amber-600 text-white',
      };
    case 'info':
      return {
        icon: 'text-blue-500',
        title: 'text-blue-900',
        bg: 'bg-blue-50',
        border: 'border-blue-200',
        confirm: 'bg-blue-500 hover:bg-blue-600 text-white',
      };
    default:
      return {
        icon: 'text-rose-500',
        title: 'text-rose-900',
        bg: 'bg-rose-50',
        border: 'border-rose-200',
        confirm: 'bg-rose-500 hover:bg-rose-600 text-white',
      };
  }
});

const confirm = () => emit('confirm');
const cancel = () => emit('cancel');
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col animate-in fade-in zoom-in duration-200"
    >
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div :class="['p-2 rounded-full', variantClasses.bg, variantClasses.border]">
            <AlertTriangle :class="['size-5', variantClasses.icon]" />
          </div>
          <h3 :class="['text-lg font-bold', variantClasses.title]">{{ title }}</h3>
        </div>
        <button @click="cancel" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
          <X class="size-5 text-slate-500" />
        </button>
      </div>

      <div class="p-6">
        <p class="text-slate-600 leading-relaxed">{{ message }}</p>
      </div>

      <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
        <button
          @click="cancel"
          class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 font-bold hover:bg-slate-200 transition-colors"
        >
          {{ cancelText }}
        </button>
        <button
          @click="confirm"
          :class="['px-4 py-2 rounded-lg font-bold transition-colors', variantClasses.confirm]"
        >
          {{ confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>