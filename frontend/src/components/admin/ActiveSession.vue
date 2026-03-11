<script setup lang="ts">
import { Sigma } from 'lucide-vue-next'

defineProps<{
  session: {
    is_active: boolean
    name: string | null
    start_time: string | null
    end_time: string | null
  } | null
  loading: boolean
}>()
</script>

<template>
  <div class="bg-primary/5 rounded-xl p-1 border border-primary/20 shadow-inner overflow-hidden">
    <div class="bg-white rounded-lg p-6 flex flex-col md:flex-row items-center gap-6 shadow-sm border border-primary/10">
      <div class="flex-shrink-0 relative">
        <div class="size-16 rounded-2xl bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/20">
          <Sigma class="size-10" />
        </div>
        <span class="absolute -top-2 -right-2 px-2 py-0.5 bg-green-500 text-[10px] font-black text-white rounded-full uppercase tracking-tighter">
          {{ session?.is_active ? 'Running' : 'Idle' }}
        </span>
      </div>

      <div class="flex-1 text-center md:text-left">
        <div class="flex items-center justify-center md:justify-start gap-2 mb-1">
          <span class="text-[10px] font-bold text-primary uppercase tracking-widest">Current Active Session</span>
          <span class="size-1.5 bg-primary rounded-full animate-ping"></span>
        </div>
        <h3 class="text-xl font-black text-slate-900">
          {{ loading ? 'Loading session...' : (session?.name || 'No Active Session') }}
        </h3>
      </div>

      <div class="flex flex-col items-center md:items-end border-t md:border-t-0 md:border-l border-slate-200 pt-4 md:pt-0 md:pl-6">
        <p class="text-[10px] font-bold text-slate-500 uppercase">Time Window</p>
        <p class="text-sm font-mono font-black text-primary">
          {{ session?.start_time && session?.end_time ? `${session.start_time} - ${session.end_time}` : '--:-- - --:--' }}
        </p>
      </div>
    </div>
  </div>
</template>
