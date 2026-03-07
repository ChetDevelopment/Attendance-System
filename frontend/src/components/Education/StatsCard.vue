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
  <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1 hover:border-slate-300">
    <div class="flex justify-between items-start mb-4">
      <span class="text-slate-600 text-sm font-semibold tracking-wide">{{ label }}</span>
      <div :class="cn('p-3 rounded-xl shadow-sm', bgColor, color, 'hover:shadow-md transition-all duration-300')">
        <component :is="icon" :size="24" />
      </div>
    </div>
    <div class="flex items-baseline gap-3">
      <span class="text-4xl font-extrabold text-slate-900 tracking-tight">{{ value }}</span>
      <span v-if="trend" :class="cn(
        'text-xs font-bold flex items-center gap-1 px-2 py-1 rounded-full border',
        trendUp ? 'text-emerald-700 bg-emerald-50 border-emerald-100' : 'text-rose-700 bg-rose-50 border-rose-100'
      )">
        <TrendingUp v-if="trendUp" :size="14" />
        <TrendingDown v-else :size="14" />
        {{ trend }}
      </span>
      <span v-if="badge" class="px-3 py-1 bg-gradient-to-r from-rose-500 to-rose-600 text-white text-[10px] font-bold rounded-full uppercase tracking-wider shadow-sm">
        {{ badge }}
      </span>
      <span v-if="subtext" class="text-xs text-slate-500 font-medium bg-slate-50 px-2 py-1 rounded-full">
        {{ subtext }}
      </span>
    </div>
  </div>
</template>
