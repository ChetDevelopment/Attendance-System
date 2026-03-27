<script setup lang="ts">
import { Sigma } from 'lucide-vue-next';

defineProps<{
  session: {
    is_active: boolean;
    name: string | null;
    start_time: string | null;
    end_time: string | null;
  } | null;
  loading: boolean;
}>();
</script>

<template>
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
      <div class="flex items-start gap-4">
        <div class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
          <Sigma class="size-7" />
        </div>

        <div class="space-y-2">
          <div class="flex flex-wrap items-center gap-2">
            <h3 class="text-2xl font-black tracking-tight text-slate-900">
              {{ loading ? 'Loading session...' : (session?.name || 'No Active Session') }}
            </h3>
            <span
              :class="[
                'rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wider',
                session?.is_active
                  ? 'bg-emerald-50 text-emerald-700'
                  : 'bg-slate-100 text-slate-600',
              ]"
            >
              {{ session?.is_active ? 'Running' : 'Idle' }}
            </span>
          </div>

        </div>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:min-w-[320px]">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Time Window</p>
          <p class="mt-2 text-sm font-bold text-slate-900">
            {{ session?.start_time && session?.end_time ? `${session.start_time} - ${session.end_time}` : '--:-- - --:--' }}
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Status</p>
          <p class="mt-2 text-sm font-bold text-slate-900">
            {{ session?.is_active ? 'Attendance collection is live' : 'Waiting for the next session' }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
