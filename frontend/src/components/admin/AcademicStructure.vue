<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Modal from './Modal.vue'
import ConfirmationModal from '../common/ConfirmationModal.vue'
import { Plus, Search, Trash2, Pencil } from 'lucide-vue-next'
import { adminAcademicService } from '../../services/adminAcademicService'

type AcademicYear = {
  id: number
  name: string
  current_term: 'Term1' | 'Term2' | 'Term3' | 'Term4'
  status: 'Current' | 'Close'
  classes_count?: number
}

type SchoolClass = {
  id: number
  class_name: string
  room_number: string
  academic_year_id: number | null
  academic_year?: { id: number; name: string } | null
  students_count?: number
}

const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')
const validationErrors = ref<Record<string, string[]>>({})

const years = ref<AcademicYear[]>([])
const classes = ref<SchoolClass[]>([])
const searchQuery = ref('')

const isYearModalOpen = ref(false)
const isClassModalOpen = ref(false)
const editingYearId = ref<number | null>(null)
const editingClassId = ref<number | null>(null)

const isYearDeleteModalOpen = ref(false)
const isClassDeleteModalOpen = ref(false)
const yearToDelete = ref<AcademicYear | null>(null)
const classToDelete = ref<SchoolClass | null>(null)

const yearForm = ref({
  name: '',
  current_term: 'Term1' as AcademicYear['current_term'],
  status: 'Current' as AcademicYear['status'],
})

const classForm = ref({
  class_name: '',
  room_number: '',
  academic_year_id: '',
})

const filteredClasses = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return classes.value

  return classes.value.filter((c) => {
    const yearName = c.academic_year?.name || ''
    return (
      c.class_name.toLowerCase().includes(q) ||
      c.room_number.toLowerCase().includes(q) ||
      yearName.toLowerCase().includes(q)
    )
  })
})

const activeYear = computed(() => years.value.find((y) => y.status === 'Current') || null)

const resetForms = () => {
  yearForm.value = { name: '', current_term: 'Term1', status: 'Current' }
  classForm.value = { class_name: '', room_number: '', academic_year_id: '' }
  editingYearId.value = null
  editingClassId.value = null
  validationErrors.value = {}
}

const loadData = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const [yearData, classData] = await Promise.all([
      adminAcademicService.getAcademicYears(),
      adminAcademicService.getClasses(),
    ])

    years.value = Array.isArray(yearData) ? yearData : []
    classes.value = Array.isArray(classData) ? classData : []
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load academic data.'
  } finally {
    loading.value = false
  }
}

const openCreateYear = () => {
  resetForms()
  isYearModalOpen.value = true
}

const openEditYear = (year: AcademicYear) => {
  yearForm.value = {
    name: year.name,
    current_term: year.current_term,
    status: year.status,
  }
  editingYearId.value = year.id
  validationErrors.value = {}
  isYearModalOpen.value = true
}

const saveYear = async () => {
  if (saving.value) return
  saving.value = true
  errorMessage.value = ''
  validationErrors.value = {}

  try {
    if (editingYearId.value) {
      await adminAcademicService.updateAcademicYear(editingYearId.value, yearForm.value)
    } else {
      await adminAcademicService.createAcademicYear(yearForm.value)
    }
    isYearModalOpen.value = false
    await loadData()
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to save academic year.'
    validationErrors.value = error.errors || {}
  } finally {
    saving.value = false
  }
}

const deleteYear = async (id: number) => {
  try {
    await adminAcademicService.deleteAcademicYear(id)
    await loadData()
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to delete academic year.'
  }
}

const openYearDeleteModal = (year: AcademicYear) => {
  yearToDelete.value = year
  isYearDeleteModalOpen.value = true
}

const closeYearDeleteModal = () => {
  isYearDeleteModalOpen.value = false
  yearToDelete.value = null
}

const confirmDeleteYear = async () => {
  if (!yearToDelete.value) return
  const id = yearToDelete.value.id
  closeYearDeleteModal()
  await deleteYear(id)
}

const openCreateClass = () => {
  classForm.value = {
    class_name: '',
    room_number: '',
    academic_year_id: activeYear.value ? String(activeYear.value.id) : '',
  }
  editingClassId.value = null
  validationErrors.value = {}
  isClassModalOpen.value = true
}

const openEditClass = (item: SchoolClass) => {
  classForm.value = {
    class_name: item.class_name,
    room_number: item.room_number,
    academic_year_id: item.academic_year_id ? String(item.academic_year_id) : '',
  }
  editingClassId.value = item.id
  validationErrors.value = {}
  isClassModalOpen.value = true
}

const saveClass = async () => {
  if (saving.value) return
  saving.value = true
  errorMessage.value = ''
  validationErrors.value = {}

  const payload = {
    class_name: classForm.value.class_name,
    room_number: classForm.value.room_number,
    academic_year_id: classForm.value.academic_year_id
      ? Number(classForm.value.academic_year_id)
      : null,
  }

  try {
    if (editingClassId.value) {
      await adminAcademicService.updateClass(editingClassId.value, payload)
    } else {
      await adminAcademicService.createClass(payload)
    }
    isClassModalOpen.value = false
    await loadData()
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to save class.'
    validationErrors.value = error.errors || {}
  } finally {
    saving.value = false
  }
}

const deleteClass = async (id: number) => {
  try {
    await adminAcademicService.deleteClass(id)
    await loadData()
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to delete class.'
  }
}

const openClassDeleteModal = (item: SchoolClass) => {
  classToDelete.value = item
  isClassDeleteModalOpen.value = true
}

const closeClassDeleteModal = () => {
  isClassDeleteModalOpen.value = false
  classToDelete.value = null
}

const confirmDeleteClass = async () => {
  if (!classToDelete.value) return
  const id = classToDelete.value.id
  closeClassDeleteModal()
  await deleteClass(id)
}

onMounted(loadData)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Academic Structure</h2>
        <p class="text-sm text-slate-500 font-medium">Manage academic years and classes from backend data</p>
      </div>
      <div class="flex gap-3">
        <button
          class="flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-bold"
          @click="openCreateYear"
        >
          <Plus class="size-4" />
          New Academic Year
        </button>
        <button
          class="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-white text-sm font-bold"
          @click="openCreateClass"
        >
          <Plus class="size-4" />
          New Class
        </button>
      </div>
    </div>

    <p v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm">{{ errorMessage }}</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] uppercase font-bold text-slate-500">Academic Years</p>
        <p class="text-2xl font-black text-slate-900">{{ years.length }}</p>
      </div>
      <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] uppercase font-bold text-slate-500">Classes</p>
        <p class="text-2xl font-black text-slate-900">{{ classes.length }}</p>
      </div>
      <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <p class="text-[10px] uppercase font-bold text-slate-500">Current Year</p>
        <p class="text-lg font-black text-slate-900">{{ activeYear?.name || '-' }}</p>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between gap-3">
        <h3 class="text-sm font-bold text-slate-900">Class List</h3>
        <div class="relative max-w-xs w-full">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search classes..."
            class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none"
          />
        </div>
      </div>

      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold">
          <tr>
            <th class="px-6 py-4">Class</th>
            <th class="px-6 py-4">Room</th>
            <th class="px-6 py-4">Academic Year</th>
            <th class="px-6 py-4">Students</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td :colspan="5" class="px-6 py-10 text-center text-slate-400 italic">Loading classes...</td>
          </tr>
          <tr v-for="item in filteredClasses" :key="item.id" class="hover:bg-slate-50">
            <td class="px-6 py-4 font-bold text-slate-900">{{ item.class_name }}</td>
            <td class="px-6 py-4 text-slate-600">{{ item.room_number }}</td>
            <td class="px-6 py-4 text-slate-600">{{ item.academic_year?.name || '-' }}</td>
            <td class="px-6 py-4 font-bold">{{ item.students_count ?? 0 }}</td>
            <td class="px-6 py-4 text-right">
              <div class="flex justify-end gap-2">
                <button class="p-2 rounded-lg hover:bg-sky-50 text-sky-600" @click="openEditClass(item)">
                  <Pencil class="size-4" />
                </button>
                <button class="p-2 rounded-lg hover:bg-rose-50 text-rose-600" @click="openClassDeleteModal(item)">
                  <Trash2 class="size-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!loading && filteredClasses.length === 0">
            <td :colspan="5" class="px-6 py-10 text-center text-slate-400 italic">No classes found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 bg-slate-50/50">
        <h3 class="text-sm font-bold text-slate-900">Academic Years</h3>
      </div>
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold">
          <tr>
            <th class="px-6 py-4">Name</th>
            <th class="px-6 py-4">Current Term</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4">Classes</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="year in years" :key="year.id" class="hover:bg-slate-50">
            <td class="px-6 py-4 font-bold">{{ year.name }}</td>
            <td class="px-6 py-4">{{ year.current_term }}</td>
            <td class="px-6 py-4">
              <span
                :class="[
                  'px-2 py-1 text-[10px] rounded font-bold uppercase',
                  year.status === 'Current' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600',
                ]"
              >
                {{ year.status }}
              </span>
            </td>
            <td class="px-6 py-4 font-bold">{{ year.classes_count ?? 0 }}</td>
            <td class="px-6 py-4 text-right">
              <div class="flex justify-end gap-2">
                <button class="p-2 rounded-lg hover:bg-sky-50 text-sky-600" @click="openEditYear(year)">
                  <Pencil class="size-4" />
                </button>
                <button class="p-2 rounded-lg hover:bg-rose-50 text-rose-600" @click="openYearDeleteModal(year)">
                  <Trash2 class="size-4" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="years.length === 0">
            <td :colspan="5" class="px-6 py-10 text-center text-slate-400 italic">No academic years found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Modal
      :is-open="isYearModalOpen"
      :title="editingYearId ? 'Edit Academic Year' : 'Create Academic Year'"
      @close="isYearModalOpen = false"
    >
      <div class="space-y-4">
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase">Name</label>
          <input v-model="yearForm.name" type="text" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded" />
          <p v-if="validationErrors.name" class="text-xs text-red-500 mt-1">{{ validationErrors.name[0] }}</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-bold text-slate-500 uppercase">Current Term</label>
            <select v-model="yearForm.current_term" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded">
              <option value="Term1">Term1</option>
              <option value="Term2">Term2</option>
              <option value="Term3">Term3</option>
              <option value="Term4">Term4</option>
            </select>
          </div>
          <div>
            <label class="text-xs font-bold text-slate-500 uppercase">Status</label>
            <select v-model="yearForm.status" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded">
              <option value="Current">Current</option>
              <option value="Close">Close</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button class="px-4 py-2 rounded bg-slate-100 text-slate-700 text-sm font-bold" @click="isYearModalOpen = false">Cancel</button>
          <button class="px-4 py-2 rounded bg-primary text-white text-sm font-bold" :disabled="saving" @click="saveYear">
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </Modal>

    <Modal
      :is-open="isClassModalOpen"
      :title="editingClassId ? 'Edit Class' : 'Create Class'"
      @close="isClassModalOpen = false"
    >
      <div class="space-y-4">
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase">Class Name</label>
          <input v-model="classForm.class_name" type="text" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded" />
          <p v-if="validationErrors.class_name" class="text-xs text-red-500 mt-1">{{ validationErrors.class_name[0] }}</p>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase">Room Number</label>
          <input v-model="classForm.room_number" type="text" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded" />
          <p v-if="validationErrors.room_number" class="text-xs text-red-500 mt-1">{{ validationErrors.room_number[0] }}</p>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase">Academic Year</label>
          <select v-model="classForm.academic_year_id" class="mt-1 w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded">
            <option value="">None</option>
            <option v-for="year in years" :key="year.id" :value="String(year.id)">{{ year.name }}</option>
          </select>
          <p v-if="validationErrors.academic_year_id" class="text-xs text-red-500 mt-1">{{ validationErrors.academic_year_id[0] }}</p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button class="px-4 py-2 rounded bg-slate-100 text-slate-700 text-sm font-bold" @click="isClassModalOpen = false">Cancel</button>
          <button class="px-4 py-2 rounded bg-primary text-white text-sm font-bold" :disabled="saving" @click="saveClass">
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </Modal>

    <!-- Year Delete Confirmation -->
    <ConfirmationModal
      :is-open="isYearDeleteModalOpen"
      title="Delete Academic Year"
      :message="`Are you sure you want to delete academic year '${yearToDelete?.name}'? This will also affect all associated classes. This action cannot be undone.`"
      confirm-text="Delete"
      cancel-text="Cancel"
      variant="danger"
      @confirm="confirmDeleteYear"
      @cancel="closeYearDeleteModal"
    />

    <!-- Class Delete Confirmation -->
    <ConfirmationModal
      :is-open="isClassDeleteModalOpen"
      title="Delete Class"
      :message="`Are you sure you want to delete class '${classToDelete?.class_name}'? This action cannot be undone.`"
      confirm-text="Delete"
      cancel-text="Cancel"
      variant="danger"
      @confirm="confirmDeleteClass"
      @cancel="closeClassDeleteModal"
    />
  </div>
</template>

