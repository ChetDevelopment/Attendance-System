<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Plus, Save, Trash2, Power } from 'lucide-vue-next'
import AdminPageHeader from './AdminPageHeader.vue'
import { sessionAdminService } from '../../services/sessionAdminService'

const sessions = ref<any[]>([])
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const form = ref({
  name: '',
  start_time: '',
  end_time: '',
  order: 1,
  late_threshold: 15,
  is_active: true,
  description: '',
})

const activeSessionsCount = computed(() => sessions.value.filter((session) => session.is_active).length)
const averageLateThreshold = computed(() => {
  if (sessions.value.length === 0) return 0

  const totalThreshold = sessions.value.reduce(
    (sum, session) => sum + Number(session.late_threshold ?? session.late_after_minutes ?? 15),
    0,
  )

  return Math.round(totalThreshold / sessions.value.length)
})

const loadSessions = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    sessions.value = await sessionAdminService.list()
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load sessions.'
  } finally {
    loading.value = false
  }
}

const createSession = async () => {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    await sessionAdminService.create(form.value)
    successMessage.value = 'Session created successfully.'
    form.value = { name: '', start_time: '', end_time: '', order: 1, late_threshold: 15, is_active: true, description: '' }
    await loadSessions()
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to create session.'
  } finally {
    saving.value = false
  }
}

const toggleSession = async (id: number) => {
  try {
    await sessionAdminService.toggle(id)
    await loadSessions()
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to update session status.'
  }
}

const deleteSession = async (id: number) => {
  try {
    await sessionAdminService.delete(id)
    await loadSessions()
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to delete session.'
  }
}

onMounted(loadSessions)
</script>

<template>
  <div class="space-y-8">
    <AdminPageHeader
      eyebrow="Session Scheduling"
      title="Session Management"
      description="Create attendance windows, keep active sessions organized, and make timing rules easier for staff to understand."
    >
      <template #meta>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Total Sessions</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ sessions.length }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Active Sessions</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ activeSessionsCount }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Avg. Late Threshold</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ averageLateThreshold }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500">minutes</p>
          </div>
        </div>
      </template>
    </AdminPageHeader>

    <p v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ errorMessage }}</p>
    <p v-if="successMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ successMessage }}</p>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[360px_1fr]">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-6 flex items-center gap-3">
          <Plus class="size-5 text-primary" />
          <h3 class="text-lg font-bold text-slate-900">Create Session</h3>
        </div>
        <div class="space-y-4">
          <div class="space-y-1">
            <label class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">Session Name</label>
            <input v-model="form.name" type="text" placeholder="Session name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">Start Time</label>
              <input v-model="form.start_time" type="time" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none" />
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">End Time</label>
              <input v-model="form.end_time" type="time" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">Display Order</label>
              <input v-model.number="form.order" type="number" min="1" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none" />
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">Late Threshold</label>
              <input v-model.number="form.late_threshold" type="number" min="0" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none" />
            </div>
          </div>
          <div class="space-y-1">
            <label class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-500">Description</label>
            <textarea v-model="form.description" rows="3" placeholder="Description" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none"></textarea>
          </div>
          <label class="flex items-center gap-2 text-sm text-slate-600">
            <input v-model="form.is_active" type="checkbox" />
            Active session
          </label>
          <button @click="createSession" :disabled="saving" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white">
            <Save class="size-4" />
            {{ saving ? 'Saving...' : 'Create Session' }}
          </button>
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-6 text-lg font-bold text-slate-900">Existing Sessions</h3>
        <div v-if="loading" class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-400">
          Loading sessions...
        </div>
        <div v-else class="space-y-4">
          <div v-for="session in sessions" :key="session.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
              <div>
                <h4 class="text-base font-bold text-slate-900">{{ session.name }}</h4>
                <p class="text-sm text-slate-500">Order {{ session.order }} | {{ session.start_time }} - {{ session.end_time }}</p>
                <p class="mt-1 text-xs text-slate-400">Late threshold: {{ session.late_threshold ?? session.late_after_minutes ?? 15 }} minutes</p>
              </div>
              <div class="flex items-center gap-2">
                <button @click="toggleSession(session.id)" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700">
                  <Power class="size-4" />
                  {{ session.is_active ? 'Deactivate' : 'Activate' }}
                </button>
                <button @click="deleteSession(session.id)" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600">
                  <Trash2 class="size-4" />
                  Delete
                </button>
              </div>
            </div>
          </div>
          <div v-if="sessions.length === 0" class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-400">
            No sessions found yet.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
