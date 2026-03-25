<script setup lang="ts">
import { computed } from 'vue'
import { TrendData } from './types'

const props = defineProps<{
  data: TrendData[]
}>()

const maxValue = computed(() => Math.max(...props.data.map((item) => item.value), 1))
</script>

<template>
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-8 flex items-center justify-between">
      <div class="flex flex-col">
        <h3 class="text-lg font-black text-slate-900">Monthly Absence Trends</h3>
        <p class="text-xs text-slate-500 font-medium">System Date: {{ new Date().toISOString().split('T')[0] }}</p>
      </div>
      <select class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 outline-none focus:ring-2 focus:ring-primary/20">
        <option>Current Month</option>
      </select>
    </div>

    <div class="flex h-[240px] items-end gap-3 border-t border-slate-100 pt-6">
      <div
        v-for="(item, index) in data"
        :key="`${item.name}-${index}`"
        class="flex flex-1 flex-col items-center justify-end gap-2"
      >
        <span class="text-[10px] font-semibold text-primary">{{ item.value }}</span>
        <div
          class="w-full rounded-t-md bg-primary transition-all"
          :style="{ height: `${Math.max((item.value / maxValue) * 180, 8)}px` }"
        />
        <span class="text-[11px] font-semibold text-slate-600">{{ item.name }}</span>
      </div>
    </div>
  </div>
</template>
