<script setup lang="ts">
import { TrendingUp, TrendingDown } from 'lucide-vue-next';
import { cn } from '../../utils/cn';

defineProps<{
  label: string;
  value: number | string;
  icon: any;
  color: string;
  bgColor: string;
  trend?: string;
  trendUp?: boolean;
  badge?: string;
  subtext?: string;
}>();
</script>

<template>
  <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
    <div class="flex justify-between items-start mb-4">
      <span class="text-slate-500 text-sm font-medium">{{ label }}</span>
      <div :class="cn('p-2 rounded-xl', bgColor, color)">
        <component :is="icon" :size="20" />
      </div>
    </div>
    <div class="flex items-baseline gap-2">
      <span class="text-3xl font-bold text-slate-900">{{ value }}</span>
      <span v-if="trend" :class="cn(
        'text-xs font-bold flex items-center gap-0.5 px-1.5 py-0.5 rounded-full',
        trendUp ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50'
      )">
        <TrendingUp v-if="trendUp" :size="12" />
        <TrendingDown v-else :size="12" />
        {{ trend }}
      </span>
      <span v-if="badge" class="px-2 py-0.5 bg-rose-100 text-rose-600 text-[10px] font-bold rounded uppercase tracking-wider">
        {{ badge }}
      </span>
      <span v-if="subtext" class="text-xs text-slate-400 font-medium">{{ subtext }}</span>
    </div>
  </div>
</template>
