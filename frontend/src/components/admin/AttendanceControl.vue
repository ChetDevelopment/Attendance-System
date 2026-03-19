<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Search, Unlock, Pencil } from 'lucide-vue-next'
import Modal from './Modal.vue'
import { adminAttendanceService } from '../../services/adminAttendanceService'

type AttendanceStatus = 'Present' | 'Absent' | 'Late' | 'Excused'

type AttendanceRecord = {
  id: number
  status: AttendanceStatus
  is_locked: boolean
  created_at: string
  location?: string | null
  student: {
    id: number
    name: string
    code: string
    class_name: string
  }
  session: {
    name: string
  }
  submitted_by: {
    name: string
  }
  student_id: number
  session_id: number
}

type Session = {
  id: number
  name: string
  start_time: string
  end_time: string
}

const records = ref<AttendanceRecord[]>([])
const sessions = ref<Session[]>([])

const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')

const searchQuery = ref('')
const statusFilter = ref('all')
const dateFilter = ref(new Date().toISOString().slice(0, 10))

const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

const isEditModalOpen = ref(false)
const selectedRecord = ref<AttendanceRecord | null>(null)
const selectedStatus = ref<AttendanceStatus>('Present')

const correctionForm = ref({
  student_id: '',
  session_id: '',
  status: 'Present' as AttendanceStatus,
})

const filteredRecords = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return records.value.filter((r) => {
    const bySearch =
      !q ||
      r.student.name.toLowerCase().includes(q) ||
      r.student.code.toLowerCase().includes(q) ||
      r.session.name.toLowerCase().includes(q)

    const byStatus = statusFilter.value === 'all' || r.status === statusFilter.value
    return bySearch && byStatus
  })
})

const stats = computed(() => {
  const totalRecords = records.value.length
  const lockedRecords = records.value.filter((r) => r.is_locked).length
  const lateRecords = records.value.filter((r) => r.status === 'Late').length
  return { totalRecords, lockedRecords, lateRecords }
})

const loadData = async (targetPage = 1) => {
  loading.value = true
  errorMessage.value = ''
  try {
    const [recordData, sessionData] = await Promise.all([
      adminAttendanceService.getRecords({
        page: targetPage,
        date: dateFilter.value || undefined,
      }),
      adminAttendanceService.getSessions(),
    ])

    records.value = Array.isArray(recordData?.data) ? recordData.data : []
    page.value = Number(recordData?.current_page || 1)
    lastPage.value = Number(recordData?.last_page || 1)
    total.value = Number(recordData?.total || 0)
    sessions.value = Array.isArray(sessionData)
      ? sessionData
      : Array.isArray((sessionData as any)?.data)
        ? (sessionData as any).data
        : []
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load attendance records.'
  } finally {
    loading.value = false
  }
}

const openEditModal = (record: AttendanceRecord) => {
  selectedRecord.value = record
  selectedStatus.value = record.status
  isEditModalOpen.value = true
}

const updateStatus = async () => {
  if (!selectedRecord.value) return
  saving.value = true
  errorMessage.value = ''
  try {
    await adminAttendanceService.updateRecord(selectedRecord.value.id, {
      status: selectedStatus.value,
    })
    isEditModalOpen.value = false
    await loadData(page.value)
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to update attendance.'
  } finally {
    saving.value = false
  }
}

const unlockRecord = async (record: AttendanceRecord) => {
  try {
    await adminAttendanceService.unlockRecord(record.id)
    await loadData(page.value)
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to unlock attendance.'
  }
}

const applyCorrection = async () => {
  if (saving.value) return
  saving.value = true
  errorMessage.value = ''
  try {
    await adminAttendanceService.manualCorrection({
      student_id: Number(correctionForm.value.student_id),
      session_id: Number(correctionForm.value.session_id),
      status: correctionForm.value.status,
    })
    // Reset form
    correctionForm.value.student_id = ''
    correctionForm.value.session_id = ''
    correctionForm.value.status = 'Present'
    await loadData(page.value)
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to apply correction.'
  } finally {
    saving.value = false
  }
}

onMounted(() => loadData(1))
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Attendance Control</h2>
        <p class="text-sm text-slate-500 font-medium">Review and manage attendance from backend records</p>
      </div>
      <button class="px-4 py-2 border border-slate-200 rounded-lg text-xs font-bold" :disabled="loading" @click="loadData(page)">
        {{ loading ? 'Refreshing...' : 'Refresh' }}
      </button>
    </div>

    <p v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm">{{ errorMessage }}</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] uppercase font-bold text-slate-500">Loaded Records</p>
        <p class="text-2xl font-black text-slate-900">{{ stats.totalRecords }}</p>
      </div>
      <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] uppercase font-bold text-slate-500">Late Records</p>
        <p class="text-2xl font-black text-slate-900">{{ stats.lateRecords }}</p>
      </div>
      <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] uppercase font-bold text-slate-500">Locked Records</p>
        <p class="text-2xl font-black text-slate-900">{{ stats.lockedRecords }}</p>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex flex-wrap items-center gap-3 justify-between">
        <div class="flex items-center gap-3">
          <div class="relative max-w-xs w-full">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search records..."
              class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none"
            />
          </div>
          <select v-model="statusFilter" class="px-3 py-2 border border-slate-200 rounded-lg text-sm">
            <option value="all">All Status</option>
            <option value="Present">Present</option>
            <option value="Absent">Absent</option>
            <option value="Late">Late</option>
            <option value="Excused">Excused</option>
          </select>
          <input v-model="dateFilter" type="date" class="px-3 py-2 border border-slate-200 rounded-lg text-sm" />
        </div>
        <button class="px-3 py-2 border border-slate-200 rounded-lg text-xs font-bold" @click="loadData(1)">Apply</button>
      </div>

      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold">
          <tr>
            <th class="px-6 py-4">Student</th>
            <th class="px-6 py-4">Session</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4">Submitted By</th>
            <th class="px-6 py-4">Location</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td :colspan="6" class="px-6 py-10 text-center text-slate-400 italic">Loading attendance...</td>
          </tr>
          <tr v-for="record in filteredRecords" :key="record.id" class="hover:bg-slate-50">
            <td class="px-6 py-4">
              <div class="font-bold text-slate-900">{{ record.student.name }}</div>
              <div class="text-[10px] text-slate-400">{{ record.student.code }} | {{ record.student.class_name || '-' }}</div>
            </td>
            <td class="px-6 py-4">
              <div class="font-medium text-slate-700">{{ record.session.name }}</div>
              <div class="text-[10px] text-slate-400">{{ record.created_at }}</div>
            </td>
            <td class="px-6 py-4">
              <span
                :class="[
                  'px-2 py-1 text-[10px] rounded font-bold uppercase',
                  record.status === 'Present' ? 'bg-green-100 text-green-700' : '',
                  record.status === 'Absent' ? 'bg-rose-100 text-rose-700' : '',
                  record.status === 'Late' ? 'bg-amber-100 text-amber-700' : '',
                  record.status === 'Excused' ? 'bg-blue-100 text-blue-700' : '',
                ]"
              >
                {{ record.status }}
              </span>
              <span v-if="record.is_locked" class="ml-2 text-[10px] font-bold text-rose-600 uppercase">Locked</span>
            </td>
            <td class="px-6 py-4 text-slate-600">{{ record.submitted_by.name }}</td>
            <td class="px-6 py-4 text-slate-600">{{ record.location || '-' }}</td>
            <td class="px-6 py-4 text-right">
              <div class="flex justify-end gap-2">
                <button class="p-2 rounded-lg hover:bg-sky-50 text-sky-600" @click="openEditModal(record)">
                  <Pencil class="size-4" />
                </button>
                <button
                  class="p-2 rounded-lg hover:bg-amber-50 text-amber-600"
                  :disabled="!record.is_locked"
                  @click="unlockRecord(record)"
                >
                  <Unlock class="size-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && filteredRecords.length === 0">
            <td :colspan="6" class="px-6 py-10 text-center text-slate-400 italic">No attendance records found.</td>
          </tr>
        </tbody>
      </table>

      <div class="p-4 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <p class="text-xs text-slate-500">Total: {{ total }} records</p>
        <div class="flex items-center gap-2">
          <button
            class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-bold disabled:opacity-50"
            :disabled="page <= 1 || loading"
            @click="loadData(page - 1)"
          >
            Prev
          </button>
          <span class="text-xs font-bold text-slate-600">Page {{ page }} / {{ lastPage }}</span>
          <button
            class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-bold disabled:opacity-50"
            :disabled="page >= lastPage || loading"
            @click="loadData(page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
      <h3 class="text-lg font-bold text-slate-900">Manual Correction</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Student ID</label>
          <input v-model="correctionForm.student_id" type="number" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded" />
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Session</label>
          <select v-model="correctionForm.session_id" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded">
            <option value="">Select session</option>
            <option v-for="session in sessions" :key="session.id" :value="String(session.id)">
              {{ session.name }} ({{ session.start_time }} - {{ session.end_time }})
            </option>
          </select>
        </div>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase">Status</label>
          <select v-model="correctionForm.status" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded">
            <option value="Present">Present</option>
            <option value="Absent">Absent</option>
            <option value="Late">Late</option>
            <option value="Excused">Excused</option>
          </select>
        </div>
      </div>
      <div class="flex justify-end">
        <button class="px-6 py-2 bg-slate-900 text-white rounded-lg text-sm font-bold disabled:opacity-60" :disabled="saving" @click="applyCorrection">
          {{ saving ? 'Applying...' : 'Apply Correction' }}
        </button>
      </div>
    </div>

    <Modal :is-open="isEditModalOpen" title="Update Attendance Status" @close="isEditModalOpen = false">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">
          {{ selectedRecord?.student.name }} - {{ selectedRecord?.session.name }}
        </p>
        <select v-model="selectedStatus" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded">
          <option value="Present">Present</option>
          <option value="Absent">Absent</option>
          <option value="Late">Late</option>
          <option value="Excused">Excused</option>
        </select>
        <div class="flex justify-end gap-2 pt-2">
          <button class="px-4 py-2 rounded bg-slate-100 text-slate-700 text-sm font-bold" @click="isEditModalOpen = false">Cancel</button>
          <button class="px-4 py-2 rounded bg-primary text-white text-sm font-bold" :disabled="saving" @click="updateStatus">
            {{ saving ? 'Saving...' : 'Update' }}
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>
