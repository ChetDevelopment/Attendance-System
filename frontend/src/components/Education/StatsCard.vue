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
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-start justify-between">
      <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">{{ label }}</span>
      <div :class="cn('rounded-xl p-3', bgColor, color)">
        <component :is="icon" :size="24" />
      </div>
    </div>
    <div class="flex flex-wrap items-center gap-3">
      <span class="text-3xl font-black tracking-tight text-slate-900">{{ value }}</span>
      <span v-if="trend" :class="cn(
        'flex items-center gap-1 rounded-full border px-2 py-1 text-[10px] font-bold uppercase tracking-wider',
        trendUp ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-amber-100 bg-amber-50 text-amber-700'
      )">
        <TrendingUp v-if="trendUp" :size="14" />
        <TrendingDown v-else :size="14" />
        {{ trend }}
      </span>
      <span v-if="badge" class="rounded-full bg-primary px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white">
        {{ badge }}
      </span>
      <span v-if="subtext" class="rounded-full bg-slate-50 px-2 py-1 text-xs font-medium text-slate-500">
        {{ subtext }}
      </span>
    </div>
  </div>
</template>
