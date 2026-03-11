<script setup lang="ts">
import { computed } from 'vue';
import { X } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    isOpen: boolean;
    title: string;
    size?: 'sm' | 'md' | 'lg' | 'xl';
  }>(),
  {
    size: 'md',
  }
);

const emit = defineEmits<{
  close: [];
}>();

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'max-w-sm';
    case 'lg':
      return 'max-w-2xl';
    case 'xl':
      return 'max-w-4xl';
    default:
      return 'max-w-md';
  }
});

const close = () => emit('close');
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
  >
    <div
      :class="[
        'bg-white rounded-2xl shadow-2xl w-full overflow-hidden flex flex-col animate-in fade-in zoom-in duration-200',
        sizeClass,
      ]"
    >
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-lg font-bold text-slate-900">{{ title }}</h3>
        <button @click="close" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
          <X class="size-5 text-slate-500" />
        </button>
      </div>

      <div class="p-6 overflow-y-auto max-h-[80vh]">
        <slot />
      </div>

      <div
        v-if="$slots.footer"
        class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3"
      >
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>
