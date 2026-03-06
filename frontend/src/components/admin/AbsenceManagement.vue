<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import {
  Search,
  Filter,
  Download,
  RefreshCw,
  Clock,
  CheckCircle2,
  XCircle,
  AlertCircle,
  MessageSquare,
  FileText,
  ChevronLeft,
  ChevronRight,
  Eye,
  Edit3,
  User,
} from 'lucide-vue-next'
import { absenceManagementService } from '../../services/absenceManagementService'
import Modal from './Modal.vue'

// Types
interface Absence {
  id: number
  student_id: number
  student_name: string
  student_code: string
  class_name: string
  class_id: number | null
  absence_date: string
  session_name: string | null
  session_time: string | null
  absence_reason: string | null
  absence_status: string
  comment: string | null
  follow_up_notes: string | null
  reason_submitted_at: string | null
  status_updated_at: string | null
  attendance_status: string | null
}

interface AbsenceDetail extends Absence {
  student: {
    id: number
    name: string
    code: string
    class: string
    class_id: number | null
    parent_number: string | null
  }
  session: {
    id: number
    name: string
    start_time: string
    end_time: string
  } | null
  reason_submitted_by: string | null
  reason_submitted_at: string | null
  status_updated_by: string | null
  status_updated_at: string | null
  notification_status: string
  created_at: string
  attendance: {
    id: number
    status: string
    date: string
    location: string
    justification: string | null
    justified_at: string | null
    marked_by: string
  } | null
  follow_ups: Array<{
    id: number
    updated_by: string
    reason: string | null
    comment: string | null
    note: string | null
    follow_up_status: string
    resolved: boolean
    is_excused: boolean
    created_at: string
  }>
}

interface Stats {
  summary: {
    total: number
    pending: number
    excused: number
    unexcused: number
  }
  by_class: Array<{
    class_name: string
    total: number
    pending: number
    excused: number
    unexcused: number
  }>
  by_date: Array<{
    date: string
    total: number
    pending: number
    excused: number
    unexcused: number
  }>
}

// Filters
const filters = ref({
  status: '',
  start_date: '',
  end_date: '',
  class_id: '',
  student_id: '',
  search: '',
})

const statuses = [
  { value: '', label: 'All Status' },
  { value: 'PENDING', label: 'Pending' },
  { value: 'EXCUSED', label: 'Excused' },
  { value: 'UNEXCUSED', label: 'Unexcused' },
]

// State
const absences = ref<Absence[]>([])
const loading = ref(false)
const error = ref('')
const stats = ref<Stats | null>(null)
const selectedAbsences = ref<number[]>([])
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(20)
const totalItems = ref(0)

// Modal states
const detailModalOpen = ref(false)
const selectedAbsence = ref<AbsenceDetail | null>(null)
const reasonModalOpen = ref(false)
const commentModalOpen = ref(false)
const followUpModalOpen = ref(false)
const statusModalOpen = ref(false)

// Form data
const reasonForm = ref({ reason: '' })
const commentForm = ref({ comment: '' })
const followUpForm = ref({ follow_up_notes: '' })
const statusForm = ref({ status: 'PENDING' })

const statusOptions = [
  { value: 'PENDING', label: 'Pending', color: 'bg-yellow-100 text-yellow-700' },
  { value: 'EXCUSED', label: 'Excused', color: 'bg-green-100 text-green-700' },
  { value: 'UNEXCUSED', label: 'Unexcused', color: 'bg-red-100 text-red-700' },
]

// Computed
const getStatusClass = (status: string) => {
  switch (status) {
    case 'PENDING':
      return 'bg-yellow-100 text-yellow-700'
    case 'EXCUSED':
      return 'bg-green-100 text-green-700'
    case 'UNEXCUSED':
      return 'bg-red-100 text-red-700'
    default:
      return 'bg-slate-100 text-slate-700'
  }
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'PENDING':
      return Clock
    case 'EXCUSED':
      return CheckCircle2
    case 'UNEXCUSED':
      return XCircle
    default:
      return AlertCircle
  }
}

const allSelected = computed(() => {
  return absences.value.length > 0 && selectedAbsences.value.length === absences.value.length
})

// Methods
const loadAbsences = async (page = 1) => {
  loading.value = true
  error.value = ''
  try {
    const params = {
      page,
      per_page: perPage.value,
      ...filters.value,
    }
    
    // Remove empty values
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null) {
        delete params[key]
      }
    })
    
    const response = await absenceManagementService.getAbsences(params)
    absences.value = response.data || []
    currentPage.value = response.current_page || 1
    totalPages.value = response.last_page || 1
    totalItems.value = response.total || 0
  } catch (err: any) {
    error.value = err.message || 'Failed to load absences'
    absences.value = []
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const response = await absenceManagementService.getStats({
      start_date: filters.value.start_date || undefined,
      end_date: filters.value.end_date || undefined,
    })
    stats.value = response
  } catch (err) {
    console.error('Failed to load stats:', err)
  }
}

const openDetailModal = async (absence: Absence) => {
  try {
    const response = await absenceManagementService.getAbsenceDetail(absence.id)
    selectedAbsence.value = response
    detailModalOpen.value = true
  } catch (err: any) {
    error.value = err.message || 'Failed to load absence details'
  }
}

const updateReason = async () => {
  if (!selectedAbsence.value || !reasonForm.value.reason.trim()) return
  
  try {
    await absenceManagementService.updateReason(selectedAbsence.value.id, {
      reason: reasonForm.value.reason,
    })
    reasonModalOpen.value = false
    reasonForm.value.reason = ''
    await loadAbsences(currentPage.value)
    await openDetailModal({ id: selectedAbsence.value.id } as Absence)
  } catch (err: any) {
    error.value = err.message || 'Failed to update reason'
  }
}

const addComment = async () => {
  if (!selectedAbsence.value || !commentForm.value.comment.trim()) return
  
  try {
    await absenceManagementService.addComment(selectedAbsence.value.id, {
      comment: commentForm.value.comment,
    })
    commentModalOpen.value = false
    commentForm.value.comment = ''
    await openDetailModal({ id: selectedAbsence.value.id } as Absence)
  } catch (err: any) {
    error.value = err.message || 'Failed to add comment'
  }
}

const addFollowUp = async () => {
  if (!selectedAbsence.value || !followUpForm.value.follow_up_notes.trim()) return
  
  try {
    await absenceManagementService.addFollowUp(selectedAbsence.value.id, {
      follow_up_notes: followUpForm.value.follow_up_notes,
    })
    followUpModalOpen.value = false
    followUpForm.value.follow_up_notes = ''
    await openDetailModal({ id: selectedAbsence.value.id } as Absence)
  } catch (err: any) {
    error.value = err.message || 'Failed to add follow-up'
  }
}

const updateStatus = async () => {
  if (!selectedAbsence.value) return
  
  try {
    await absenceManagementService.updateStatus(selectedAbsence.value.id, {
      status: statusForm.value.status,
    })
    statusModalOpen.value = false
    await loadAbsences(currentPage.value)
    await openDetailModal({ id: selectedAbsence.value.id } as Absence)
  } catch (err: any) {
    error.value = err.message || 'Failed to update status'
  }
}

const bulkUpdateStatus = async (status: string) => {
  if (selectedAbsences.value.length === 0) return
  
  try {
    await absenceManagementService.bulkUpdateStatus({
      absence_ids: selectedAbsences.value,
      status,
    })
    selectedAbsences.value = []
    await loadAbsences(currentPage.value)
  } catch (err: any) {
    error.value = err.message || 'Failed to update status'
  }
}

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedAbsences.value = []
  } else {
    selectedAbsences.value = absences.value.map(a => a.id)
  }
}

const toggleSelect = (id: number) => {
  const index = selectedAbsences.value.indexOf(id)
  if (index > -1) {
    selectedAbsences.value.splice(index, 1)
  } else {
    selectedAbsences.value.push(id)
  }
}

const applyFilters = () => {
  currentPage.value = 1
  loadAbsences()
  loadStats()
}

const resetFilters = () => {
  filters.value = {
    status: '',
    start_date: '',
    end_date: '',
    class_id: '',
    student_id: '',
    search: '',
  }
  currentPage.value = 1
  loadAbsences()
  loadStats()
}

const formatDate = (date: string | null) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const formatDateTime = (date: string | null) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Watch for filter changes
watch(filters, () => {
  // Debounce is handled by applyFilters button
}, { deep: true })

// Lifecycle
onMounted(() => {
  loadAbsences()
  loadStats()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Absence Management</h2>
        <p class="text-sm text-slate-500 font-medium">Manage absence reasons, comments, and status tracking</p>
      </div>
      <div class="flex items-center gap-2">
        <button
          @click="loadAbsences(currentPage)"
          class="p-2 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
          title="Refresh"
        >
          <RefreshCw class="size-4 text-slate-600" />
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div v-if="stats" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-slate-500 uppercase">Total Absences</p>
            <p class="text-2xl font-black text-slate-900">{{ stats.summary.total }}</p>
          </div>
          <div class="size-10 bg-slate-100 rounded-full flex items-center justify-center">
            <AlertCircle class="size-5 text-slate-500" />
          </div>
        </div>
      </div>
      <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-yellow-600 uppercase">Pending</p>
            <p class="text-2xl font-black text-slate-900">{{ stats.summary.pending }}</p>
          </div>
          <div class="size-10 bg-yellow-100 rounded-full flex items-center justify-center">
            <Clock class="size-5 text-yellow-600" />
          </div>
        </div>
      </div>
      <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-green-600 uppercase">Excused</p>
            <p class="text-2xl font-black text-slate-900">{{ stats.summary.excused }}</p>
          </div>
          <div class="size-10 bg-green-100 rounded-full flex items-center justify-center">
            <CheckCircle2 class="size-5 text-green-600" />
          </div>
        </div>
      </div>
      <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-red-600 uppercase">Unexcused</p>
            <p class="text-2xl font-black text-slate-900">{{ stats.summary.unexcused }}</p>
          </div>
          <div class="size-10 bg-red-100 rounded-full flex items-center justify-center">
            <XCircle class="size-5 text-red-600" />
          </div>
        </div>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
      <p class="font-bold">Error</p>
      <p>{{ error }}</p>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
        <div class="relative lg:col-span-2">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search by student name or code..."
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
            @keyup.enter="applyFilters"
          />
        </div>
        <select
          v-model="filters.status"
          class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
        >
          <option v-for="status in statuses" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>
        <input
          v-model="filters.start_date"
          type="date"
          class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
        />
        <input
          v-model="filters.end_date"
          type="date"
          class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
        />
        <div class="flex gap-2">
          <button
            @click="applyFilters"
            class="flex-1 px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/90 transition-colors"
          >
            Filter
          </button>
          <button
            @click="resetFilters"
            class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-lg hover:bg-slate-200 transition-colors"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedAbsences.length > 0" class="flex items-center gap-4 bg-primary/10 p-4 rounded-lg">
      <span class="text-sm font-bold text-primary">{{ selectedAbsences.length }} selected</span>
      <div class="flex gap-2">
        <button
          @click="bulkUpdateStatus('EXCUSED')"
          class="px-3 py-1.5 bg-green-500 text-white text-xs font-bold rounded-lg hover:bg-green-600 transition-colors"
        >
          Mark as Excused
        </button>
        <button
          @click="bulkUpdateStatus('UNEXCUSED')"
          class="px-3 py-1.5 bg-red-500 text-white text-xs font-bold rounded-lg hover:bg-red-600 transition-colors"
        >
          Mark as Unexcused
        </button>
        <button
          @click="bulkUpdateStatus('PENDING')"
          class="px-3 py-1.5 bg-yellow-500 text-white text-xs font-bold rounded-lg hover:bg-yellow-600 transition-colors"
        >
          Mark as Pending
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-slate-50 text-slate-500 uppercase text-xs font-bold">
          <tr>
            <th class="px-4 py-3 w-10">
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleSelectAll"
                class="rounded border-slate-300"
              />
            </th>
            <th class="px-4 py-3">Date</th>
            <th class="px-4 py-3">Student</th>
            <th class="px-4 py-3">Class</th>
            <th class="px-4 py-3">Session</th>
            <th class="px-4 py-3">Reason</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading" class="hover:bg-slate-50">
            <td :colspan="8" class="px-4 py-10 text-center text-slate-400">
              <RefreshCw class="size-6 animate-spin mx-auto" />
              <p class="mt-2">Loading absences...</p>
            </td>
          </tr>
          <tr v-else-if="absences.length === 0" class="hover:bg-slate-50">
            <td :colspan="8" class="px-4 py-10 text-center text-slate-400 italic">
              No absences found.
            </td>
          </tr>
          <tr v-for="absence in absences" :key="absence.id" class="hover:bg-slate-50 transition-colors">
            <td class="px-4 py-3">
              <input
                type="checkbox"
                :checked="selectedAbsences.includes(absence.id)"
                @change="toggleSelect(absence.id)"
                class="rounded border-slate-300"
              />
            </td>
            <td class="px-4 py-3 text-sm font-mono">
              {{ formatDate(absence.absence_date) }}
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <div class="size-8 bg-primary/10 rounded-full flex items-center justify-center">
                  <User class="size-4 text-primary" />
                </div>
                <div>
                  <p class="text-sm font-bold text-slate-900">{{ absence.student_name }}</p>
                  <p class="text-xs text-slate-500">{{ absence.student_code }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-sm">
              {{ absence.class_name }}
            </td>
            <td class="px-4 py-3 text-sm">
              <span v-if="absence.session_name">
                {{ absence.session_name }}
                <span v-if="absence.session_time" class="text-slate-400 text-xs">({{ absence.session_time }})</span>
              </span>
              <span v-else class="text-slate-400">-</span>
            </td>
            <td class="px-4 py-3 text-sm max-w-xs truncate">
              <span v-if="absence.absence_reason" :title="absence.absence_reason">
                {{ absence.absence_reason }}
              </span>
              <span v-else class="text-slate-400 italic">No reason</span>
            </td>
            <td class="px-4 py-3">
              <span
                :class="[
                  'inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold',
                  getStatusClass(absence.absence_status)
                ]"
              >
                <component :is="getStatusIcon(absence.absence_status)" class="size-3" />
                {{ absence.absence_status }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button
                @click="openDetailModal(absence)"
                class="p-2 text-slate-600 hover:text-primary hover:bg-primary/10 rounded-lg transition-colors"
                title="View Details"
              >
                <Eye class="size-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex items-center justify-between">
      <p class="text-sm text-slate-500">
        Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} results
      </p>
      <div class="flex items-center gap-2">
        <button
          @click="loadAbsences(currentPage - 1)"
          :disabled="currentPage === 1"
          class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <ChevronLeft class="size-4" />
        </button>
        <span class="text-sm font-medium">Page {{ currentPage }} of {{ totalPages }}</span>
        <button
          @click="loadAbsences(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <ChevronRight class="size-4" />
        </button>
      </div>
    </div>

    <!-- Detail Modal -->
    <Modal :is-open="detailModalOpen" title="Absence Details" size="xl" @close="detailModalOpen = false">
      <div v-if="selectedAbsence" class="space-y-6">
        <!-- Student Info -->
        <div class="bg-slate-50 p-4 rounded-lg">
          <h4 class="text-sm font-bold text-slate-500 uppercase mb-3">Student Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-slate-400">Name</p>
              <p class="font-bold">{{ selectedAbsence.student.name }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400">Student Code</p>
              <p class="font-bold">{{ selectedAbsence.student.code }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400">Class</p>
              <p class="font-bold">{{ selectedAbsence.student.class }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400">Parent Contact</p>
              <p class="font-bold">{{ selectedAbsence.student.parent_number || '-' }}</p>
            </div>
          </div>
        </div>

        <!-- Absence Info -->
        <div class="bg-slate-50 p-4 rounded-lg">
          <h4 class="text-sm font-bold text-slate-500 uppercase mb-3">Absence Information</h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-slate-400">Date</p>
              <p class="font-bold">{{ formatDate(selectedAbsence.absence_date) }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400">Session</p>
              <p class="font-bold">{{ selectedAbsence.session?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400">Reason</p>
              <p class="font-bold">{{ selectedAbsence.absence_reason || 'No reason provided' }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400">Status</p>
              <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold', getStatusClass(selectedAbsence.absence_status)]">
                {{ selectedAbsence.absence_status }}
              </span>
            </div>
          </div>
        </div>

        <!-- Comments -->
        <div v-if="selectedAbsence.comment" class="bg-slate-50 p-4 rounded-lg">
          <h4 class="text-sm font-bold text-slate-500 uppercase mb-3">Comments</h4>
          <pre class="text-sm whitespace-pre-wrap font-mono">{{ selectedAbsence.comment }}</pre>
        </div>

        <!-- Follow-up Notes -->
        <div v-if="selectedAbsence.follow_up_notes" class="bg-slate-50 p-4 rounded-lg">
          <h4 class="text-sm font-bold text-slate-500 uppercase mb-3">Follow-up Notes</h4>
          <pre class="text-sm whitespace-pre-wrap font-mono">{{ selectedAbsence.follow_up_notes }}</pre>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-2 pt-4 border-t">
          <button
            @click="reasonModalOpen = true; reasonForm.reason = selectedAbsence.absence_reason || ''"
            class="px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2"
          >
            <FileText class="size-4" />
            Update Reason
          </button>
          <button
            @click="commentModalOpen = true; commentForm.comment = ''"
            class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-200 transition-colors flex items-center gap-2"
          >
            <MessageSquare class="size-4" />
            Add Comment
          </button>
          <button
            @click="followUpModalOpen = true; followUpForm.follow_up_notes = ''"
            class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-200 transition-colors flex items-center gap-2"
          >
            <Edit3 class="size-4" />
            Add Follow-up
          </button>
          <button
            @click="statusForm.status = selectedAbsence.absence_status; statusModalOpen = true"
            class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-200 transition-colors flex items-center gap-2"
          >
            <Clock class="size-4" />
            Update Status
          </button>
        </div>
      </div>
    </Modal>

    <!-- Reason Modal -->
    <Modal :is-open="reasonModalOpen" title="Update Absence Reason" size="md" @close="reasonModalOpen = false">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-bold text-slate-700 mb-2">Reason</label>
          <textarea
            v-model="reasonForm.reason"
            rows="4"
            class="w-full px-4 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
            placeholder="Enter absence reason..."
          ></textarea>
        </div>
        <div class="flex justify-end gap-2">
          <button
            @click="reasonModalOpen = false"
            class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-200"
          >
            Cancel
          </button>
          <button
            @click="updateReason"
            :disabled="!reasonForm.reason.trim()"
            class="px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/90 disabled:opacity-50"
          >
            Save
          </button>
        </div>
      </div>
    </Modal>

    <!-- Comment Modal -->
    <Modal :is-open="commentModalOpen" title="Add Comment" size="md" @close="commentModalOpen = false">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-bold text-slate-700 mb-2">Comment</label>
          <textarea
            v-model="commentForm.comment"
            rows="4"
            class="w-full px-4 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
            placeholder="Enter comment..."
          ></textarea>
        </div>
        <div class="flex justify-end gap-2">
          <button
            @click="commentModalOpen = false"
            class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-200"
          >
            Cancel
          </button>
          <button
            @click="addComment"
            :disabled="!commentForm.comment.trim()"
            class="px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/90 disabled:opacity-50"
          >
            Save
          </button>
        </div>
      </div>
    </Modal>

    <!-- Follow-up Modal -->
    <Modal :is-open="followUpModalOpen" title="Add Follow-up Notes" size="md" @close="followUpModalOpen = false">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-bold text-slate-700 mb-2">Follow-up Notes</label>
          <textarea
            v-model="followUpForm.follow_up_notes"
            rows="4"
            class="w-full px-4 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
            placeholder="Enter follow-up notes..."
          ></textarea>
        </div>
        <div class="flex justify-end gap-2">
          <button
            @click="followUpModalOpen = false"
            class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-200"
          >
            Cancel
          </button>
          <button
            @click="addFollowUp"
            :disabled="!followUpForm.follow_up_notes.trim()"
            class="px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/90 disabled:opacity-50"
          >
            Save
          </button>
        </div>
      </div>
    </Modal>

    <!-- Status Modal -->
    <Modal :is-open="statusModalOpen" title="Update Status" size="md" @close="statusModalOpen = false">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-bold text-slate-700 mb-2">Absence Status</label>
          <div class="grid grid-cols-3 gap-3">
            <button
              v-for="option in statusOptions"
              :key="option.value"
              @click="statusForm.status = option.value"
              :class="[
                'p-3 rounded-lg border-2 transition-all text-center',
                statusForm.status === option.value 
                  ? 'border-primary bg-primary/10' 
                  : 'border-slate-200 hover:border-slate-300'
              ]"
            >
              <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold', option.color]">
                {{ option.label }}
              </span>
            </button>
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <button
            @click="statusModalOpen = false"
            class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-200"
          >
            Cancel
          </button>
          <button
            @click="updateStatus"
            class="px-4 py-2 bg-primary text-white text-sm font-bold rounded-lg hover:bg-primary/90"
          >
            Save
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>
