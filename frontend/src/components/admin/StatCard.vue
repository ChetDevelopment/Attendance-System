<script setup lang="ts">
import { computed } from 'vue';

type IconComponent = any;

const props = defineProps<{
  title: string;
  value: string | number;
  icon: IconComponent;
  iconColor: string;
  borderColor: string;
  subtitle?: string;
  trend?: string;
  footerText?: string;
}>();

const iconBackgroundClass = computed(() => {
  if (props.iconColor.includes('green')) return 'bg-emerald-50';
  if (props.iconColor.includes('red')) return 'bg-rose-50';
  if (props.iconColor.includes('amber')) return 'bg-amber-50';
  if (props.iconColor.includes('blue')) return 'bg-blue-50';
  if (props.iconColor.includes('primary')) return 'bg-primary/10';
  return 'bg-slate-100';
});
</script>

<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-start justify-between gap-4">
      <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">{{ title }}</span>
      <div :class="[iconBackgroundClass, 'rounded-xl p-3']">
        <component :is="icon" :class="['size-5', iconColor]" />
      </div>
    </div>

    <div class="flex flex-wrap items-end gap-3">
      <p class="text-3xl font-black tracking-tight text-slate-900">{{ value }}</p>
      <slot name="action" />
    </div>

    <p v-if="subtitle" class="mt-3 text-sm font-medium text-slate-500">{{ subtitle }}</p>

    <div v-if="trend || footerText" class="mt-4 flex flex-wrap items-center gap-2">
      <span
        v-if="trend"
        class="rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700"
      >
        {{ trend }}
      </span>
      <span
        v-if="footerText"
        class="rounded-full bg-slate-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-500"
      >
        {{ footerText }}
      </span>
    </div>
  </div>
</template>
