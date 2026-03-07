<script setup lang="ts">
import { TrendingUp } from 'lucide-vue-next';

type IconComponent = any;

defineProps<{
  title: string;
  value: string | number;
  icon: IconComponent;
  iconColor: string;
  borderColor: string;
  subtitle?: string;
  trend?: string;
  footerText?: string;
}>();
</script>

<template>
  <div :class="['bg-white p-6 rounded-xl border-l-4 shadow-sm', borderColor]">
    <div class="flex items-center justify-between mb-4">
      <span class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">{{ title }}</span>
      <component :is="icon" :class="['size-5', iconColor]" />
    </div>

    <div class="flex items-baseline gap-3">
      <p :class="['text-3xl font-black', title === 'Absent Students' ? 'text-red-500' : 'text-slate-900']">
        {{ value }}
      </p>
      <slot name="action" />
    </div>

    <p v-if="trend" class="text-[10px] text-green-600 font-bold mt-2 flex items-center gap-1">
      <TrendingUp class="size-3" /> {{ trend }}
    </p>

    <p v-if="subtitle" class="text-[10px] text-slate-500 font-medium mt-2 italic">{{ subtitle }}</p>

    <div v-if="footerText" class="mt-4 flex items-center justify-between">
      <p class="text-[9px] text-slate-400 font-mono">{{ footerText }}</p>
      <button class="text-[10px] px-3 py-1 bg-slate-100 hover:bg-slate-200 rounded font-bold transition-colors">Retry</button>
    </div>
  </div>
</template>
