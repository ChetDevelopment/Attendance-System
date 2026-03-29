<script setup lang="ts">
import { computed } from 'vue'
import { ChevronDown, FileText, UserRound } from 'lucide-vue-next'
import type {
  AcademicYearOption,
  EducationClassOption,
  EducationReportRow,
  ReportPeriod,
} from './types'

const props = defineProps<{
  academicYears: AcademicYearOption[]
  classes: EducationClassOption[]
  rows: EducationReportRow[]
  selectedAcademicYear: number | null
  selectedClass: number | null
  selectedPeriod: ReportPeriod
  isLoading?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:academicYear', value: number | null): void
  (e: 'update:class', value: number | null): void
  (e: 'update:period', value: ReportPeriod): void
  (e: 'export'): void
}>()

const periodOptions: Array<{ label: string; value: ReportPeriod }> = [
  { label: 'Today', value: 'today' },
  { label: 'Weekly', value: 'weekly' },
  { label: 'Monthly', value: 'monthly' },
]

const academicYearValue = computed({
  get: () => (props.selectedAcademicYear ? String(props.selectedAcademicYear) : ''),
  set: (value: string) => emit('update:academicYear', value ? Number(value) : null),
})

const classValue = computed({
  get: () => (props.selectedClass ? String(props.selectedClass) : ''),
  set: (value: string) => emit('update:class', value ? Number(value) : null),
})

</script>

<template>
  <section class="space-y-8 rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm md:p-8">
    <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
      <div class="space-y-2">
        <h2 class="text-3xl font-black tracking-tight text-slate-950">
          Class Attendance Reports
        </h2>
      </div>

      <div class="inline-flex w-full rounded-[22px] border border-slate-200 bg-slate-50 p-1.5 xl:w-auto">
        <button
          v-for="option in periodOptions"
          :key="option.value"
          type="button"
          @click="emit('update:period', option.value)"
          :class="[
            'flex-1 rounded-[16px] px-6 py-3 text-sm font-bold transition-all xl:flex-none',
            selectedPeriod === option.value
              ? 'bg-primary text-white shadow-sm'
              : 'text-slate-600 hover:text-slate-900',
          ]"
        >
          {{ option.label }}
        </button>
      </div>
    </div>

    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
      <div class="grid flex-1 grid-cols-1 gap-4 md:grid-cols-2 xl:max-w-[760px]">
        <label class="space-y-2">
          <span class="text-sm font-bold uppercase tracking-wide text-slate-500">
            Academic Year
          </span>
          <div class="relative">
            <select
              v-model="academicYearValue"
              class="h-12 w-full appearance-none rounded-2xl border border-slate-200 bg-white px-5 pr-12 text-base font-semibold text-slate-900 outline-none transition focus:border-primary/40 focus:ring-4 focus:ring-primary/10"
            >
              <option
                v-for="year in academicYears"
                :key="year.id"
                :value="String(year.id)"
              >
                {{ year.name }}
              </option>
            </select>
            <ChevronDown class="pointer-events-none absolute right-4 top-1/2 size-5 -translate-y-1/2 text-slate-500" />
          </div>
        </label>

        <label class="space-y-2">
          <span class="text-sm font-bold uppercase tracking-wide text-slate-500">
            Class
          </span>
          <div class="relative">
            <select
              v-model="classValue"
              class="h-12 w-full appearance-none rounded-2xl border border-slate-200 bg-white px-5 pr-12 text-base font-semibold text-slate-900 outline-none transition focus:border-primary/40 focus:ring-4 focus:ring-primary/10"
            >
              <option
                v-for="schoolClass in classes"
                :key="schoolClass.id"
                :value="String(schoolClass.id)"
              >
                {{ schoolClass.label }}
              </option>
            </select>
            <ChevronDown class="pointer-events-none absolute right-4 top-1/2 size-5 -translate-y-1/2 text-slate-500" />
          </div>
        </label>
      </div>

      <div class="flex justify-start xl:justify-end">
        <button
          type="button"
          @click="emit('export')"
          class="inline-flex items-center gap-2 rounded-2xl bg-primary px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-primary/90"
        >
          <FileText class="size-4" />
          Export CSV
        </button>
      </div>
    </div>

    <div class="overflow-hidden rounded-[22px] border border-slate-200">
      <div class="overflow-x-auto">
        <table class="min-w-full border-separate border-spacing-0 text-left">
          <thead class="bg-white">
            <tr class="text-sm font-bold uppercase text-slate-500">
              <th class="border-b border-slate-200 px-5 py-4">No</th>
              <th class="border-b border-slate-200 px-5 py-4">Photos</th>
              <th class="border-b border-slate-200 px-5 py-4">Student</th>
              <th class="border-b border-slate-200 px-5 py-4">Code</th>
              <th class="border-b border-slate-200 px-5 py-4">Late</th>
              <th class="border-b border-slate-200 px-5 py-4">Absent</th>
            </tr>
          </thead>

          <tbody class="bg-white">
            <tr v-if="isLoading">
              <td colspan="6" class="px-5 py-10 text-center text-sm font-medium text-slate-500">
                Loading report data...
              </td>
            </tr>

            <tr v-else-if="rows.length === 0">
              <td colspan="6" class="px-5 py-10 text-center text-sm font-medium text-slate-500">
                No students found for the selected filters.
              </td>
            </tr>

            <tr
              v-for="(row, index) in rows"
              :key="row.id"
              class="transition hover:bg-slate-50/60"
            >
              <td class="border-b border-slate-200 px-5 py-4 text-base font-semibold text-slate-700 last:border-b-0">
                {{ index + 1 }}
              </td>
              <td class="border-b border-slate-200 px-5 py-4 last:border-b-0">
                <div
                  v-if="!row.photo"
                  class="flex size-12 items-center justify-center rounded-full bg-slate-200 text-slate-500"
                  :title="row.name"
                >
                  <UserRound class="size-5" />
                </div>
                <img
                  v-else
                  :src="row.photo"
                  :alt="row.name"
                  class="size-12 rounded-full object-cover ring-1 ring-slate-200"
                />
              </td>
              <td class="border-b border-slate-200 px-5 py-4 text-base font-bold text-slate-950 last:border-b-0">
                {{ row.name }}
              </td>
              <td class="border-b border-slate-200 px-5 py-4 text-base font-semibold text-slate-600 last:border-b-0">
                {{ row.student_code }}
              </td>
              <td class="border-b border-slate-200 px-5 py-4 text-base font-semibold text-slate-700 last:border-b-0">
                {{ row.late_count }}
              </td>
              <td class="border-b border-slate-200 px-5 py-4 text-base font-semibold text-slate-700 last:border-b-0">
                {{ row.absent_count }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
