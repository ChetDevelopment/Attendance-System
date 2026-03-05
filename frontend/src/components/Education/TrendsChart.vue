<script setup lang="ts">
import { computed } from 'vue'
import { TrendData } from './types'

const props = defineProps<{
  data: TrendData[]
}>()

const maxValue = computed(() => Math.max(...props.data.map((item) => item.value), 1))
</script>

<template>
  <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
    <div class="flex justify-between items-center mb-8">
      <div class="flex flex-col">
        <h3 class="font-bold text-lg text-slate-900">Monthly Absence Trends</h3>
        <p class="text-xs text-slate-500 font-medium">System Date: {{ new Date().toISOString().split('T')[0] }}</p>
      </div>
      <select class="text-xs font-semibold text-slate-600 border border-slate-200 bg-white rounded-lg px-3 py-1.5 outline-none focus:ring-2 focus:ring-[#135bec]/20">
        <option>Current Month</option>
      </select>
    </div>

    <div class="h-[240px] flex items-end gap-3 border-t border-slate-100 pt-6">
      <div
        v-for="(item, index) in data"
        :key="`${item.name}-${index}`"
        class="flex-1 flex flex-col items-center justify-end gap-2"
      >
        <span class="text-[10px] font-semibold text-[#135bec]">{{ item.value }}</span>
        <div
          class="w-full rounded-t-md bg-[#135bec] transition-all"
          :style="{ height: `${Math.max((item.value / maxValue) * 180, 8)}px` }"
        />
        <span class="text-[11px] font-semibold text-slate-600">{{ item.name }}</span>
      </div>
    </div>
  </div>
</template>
