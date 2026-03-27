<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Fingerprint, CreditCard, Search, RefreshCw, Save } from 'lucide-vue-next'
import AdminPageHeader from './AdminPageHeader.vue'
import { biometricAdminService } from '../../services/biometricAdminService'

const loading = ref(false)
const savingStudentId = ref<number | null>(null)
const errorMessage = ref('')
const successMessage = ref('')
const searchQuery = ref('')
const filter = ref<'all' | 'enrolled' | 'not_enrolled'>('all')
const overview = ref<any>(null)
const students = ref<any[]>([])
const selectedStudent = ref<any | null>(null)
const history = ref<any[]>([])

const filteredStudents = computed(() => students.value)

const loadData = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const [overviewData, studentData] = await Promise.all([
      biometricAdminService.getOverview(),
      biometricAdminService.getStudents({
        search: searchQuery.value || undefined,
        enrollment: filter.value,
        per_page: 50,
      }),
    ])

    overview.value = overviewData
    students.value = Array.isArray(studentData?.data) ? studentData.data : []

    if (selectedStudent.value) {
      const stillSelected = students.value.find((item) => item.id === selectedStudent.value.id)
      selectedStudent.value = stillSelected || null
    }
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load biometric management data.'
  } finally {
    loading.value = false
  }
}

const openStudent = async (student: any) => {
  selectedStudent.value = { ...student }
  history.value = []

  try {
    const data = await biometricAdminService.getHistory(student.id)
    history.value = Array.isArray(data?.history) ? data.history : []
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to load biometric history.'
  }
}

const saveStudent = async () => {
  if (!selectedStudent.value) return

  savingStudentId.value = selectedStudent.value.id
  errorMessage.value = ''
  successMessage.value = ''

  try {
    await biometricAdminService.updateStudent(selectedStudent.value.id, {
      card_id: selectedStudent.value.card_id || null,
      fingerprint_enrolled: Boolean(selectedStudent.value.fingerprint_enrolled),
      last_biometric_scan: selectedStudent.value.last_biometric_scan || null,
    })
    successMessage.value = 'Biometric settings updated successfully.'
    await loadData()
    await openStudent(selectedStudent.value)
  } catch (error: any) {
    errorMessage.value = error.message || 'Failed to save biometric settings.'
  } finally {
    savingStudentId.value = null
  }
}

onMounted(loadData)
</script>

<template>
  <div class="space-y-8">
    <AdminPageHeader
      eyebrow="Biometric Access"
      title="Biometric Management"
      description="Manage RFID cards, fingerprint enrollment, and recent biometric history from one clearer workspace."
    >
      <template #actions>
        <button
          @click="loadData"
          class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white"
        >
          <RefreshCw class="size-4" />
          Refresh
        </button>
      </template>

      <template #meta>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Students</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ overview?.summary?.total_students || 0 }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Fingerprint Enrolled</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ overview?.summary?.fingerprint_enrolled || 0 }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">RFID Assigned</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ overview?.summary?.rfid_assigned || 0 }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400">Enrollment Rate</p>
            <p class="mt-2 text-2xl font-black text-slate-900">{{ overview?.summary?.enrollment_percentage || 0 }}%</p>
          </div>
        </div>
      </template>
    </AdminPageHeader>

    <p v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ errorMessage }}</p>
    <p v-if="successMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ successMessage }}</p>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-[1.2fr_1fr]">
      <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="mb-6">
            <h3 class="text-sm font-bold text-slate-900">Student Enrollment Directory</h3>
            <p class="mt-1 text-xs text-slate-500">Search by student, card, or email, then select a record to update enrollment details.</p>
          </div>

          <div class="mb-6 flex flex-col gap-4 md:flex-row">
            <div class="relative flex-1">
              <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
              <input v-model="searchQuery" type="text" placeholder="Search students, email, or card..." class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-3 text-sm outline-none" />
            </div>
            <select v-model="filter" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm outline-none">
              <option value="all">All Students</option>
              <option value="enrolled">Enrolled</option>
              <option value="not_enrolled">Not Enrolled</option>
            </select>
            <button @click="loadData" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700">Apply</button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
              <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                <tr>
                  <th class="px-4 py-3">Student</th>
                  <th class="px-4 py-3">Class</th>
                  <th class="px-4 py-3">Card ID</th>
                  <th class="px-4 py-3">Fingerprint</th>
                  <th class="px-4 py-3">Last Scan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-if="loading">
                  <td colspan="5" class="px-4 py-10 text-center text-slate-400">Loading biometric data...</td>
                </tr>
                <tr
                  v-for="student in filteredStudents"
                  :key="student.id"
                  class="cursor-pointer hover:bg-slate-50"
                  @click="openStudent(student)"
                >
                  <td class="px-4 py-4">
                    <p class="font-bold text-slate-900">{{ student.name }}</p>
                    <p class="text-xs text-slate-400">{{ student.student_code }}</p>
                  </td>
                  <td class="px-4 py-4 text-slate-600">{{ student.class_name || 'Unknown' }}</td>
                  <td class="px-4 py-4 text-slate-600">{{ student.card_id || 'Not assigned' }}</td>
                  <td class="px-4 py-4">
                    <span :class="student.fingerprint_enrolled ? 'text-emerald-600' : 'text-amber-600'" class="text-xs font-bold uppercase">
                      {{ student.fingerprint_enrolled ? 'Enrolled' : 'Pending' }}
                    </span>
                  </td>
                  <td class="px-4 py-4 text-slate-500">{{ student.last_biometric_scan || 'Never' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" v-if="selectedStudent">
          <div class="mb-6">
            <h3 class="text-lg font-bold text-slate-900">{{ selectedStudent.name }}</h3>
            <p class="text-sm text-slate-500">{{ selectedStudent.student_code }} | {{ selectedStudent.class_name || 'Unknown class' }}</p>
          </div>

          <div class="space-y-4">
            <div>
              <label class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-slate-500">RFID Card</label>
              <div class="relative">
                <CreditCard class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input v-model="selectedStudent.card_id" type="text" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-10 pr-3 text-sm outline-none" />
              </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <Fingerprint class="size-5 text-primary" />
                  <div>
                    <p class="text-sm font-bold text-slate-900">Fingerprint Enrollment</p>
                    <p class="text-xs text-slate-500">Enable or disable biometric fingerprint use for this student.</p>
                  </div>
                </div>
                <label class="inline-flex cursor-pointer items-center">
                  <input v-model="selectedStudent.fingerprint_enrolled" type="checkbox" class="peer sr-only" />
                  <span class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500"></span>
                </label>
              </div>
            </div>

            <button
              @click="saveStudent"
              :disabled="savingStudentId === selectedStudent.id"
              class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white shadow-lg shadow-primary/20"
            >
              <Save class="size-4" />
              {{ savingStudentId === selectedStudent.id ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h3 class="mb-4 text-lg font-bold text-slate-900">Recent Biometric History</h3>
          <div v-if="!selectedStudent" class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-400">
            Select a student to view biometric history.
          </div>
          <div v-else class="space-y-3">
            <div v-if="history.length === 0" class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-400">
              No biometric history found for this student.
            </div>
            <div v-for="item in history" :key="item.id" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="flex items-center justify-between gap-4">
                <div>
                  <p class="text-sm font-bold text-slate-900">{{ item.session_name }}</p>
                  <p class="text-xs text-slate-500">{{ item.attendance_date }} | {{ item.scan_method }}</p>
                </div>
                <span class="text-xs font-bold uppercase text-slate-600">{{ item.status }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
