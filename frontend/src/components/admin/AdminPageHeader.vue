<script setup lang="ts">
withDefaults(
  defineProps<{
    eyebrow?: string;
    title: string;
    description: string;
  }>(),
  {
    eyebrow: 'Admin Workspace',
  },
);
</script>

<template>
  <section class="rounded-3xl border border-slate-200 bg-white px-6 py-6 shadow-sm md:px-8">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
      <div class="space-y-2">
        <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-primary">{{ eyebrow }}</p>
        <h2 class="text-3xl font-black tracking-tight text-slate-900">{{ title }}</h2>
        <p class="max-w-2xl text-sm font-medium text-slate-500">{{ description }}</p>
      </div>

      <div v-if="$slots.actions" class="flex flex-wrap items-center gap-3">
        <slot name="actions" />
      </div>
    </div>

    <div v-if="$slots.meta" class="mt-6">
      <slot name="meta" />
    </div>
  </section>
</template>
