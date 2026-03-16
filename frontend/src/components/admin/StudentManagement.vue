<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import Modal from './Modal.vue';
import ConfirmationModal from '../common/ConfirmationModal.vue';
import {
  Search,
  UserPlus,
  Upload,
  QrCode,
  Printer,
  Edit3,
  Trash2,
  LayoutGrid,
  List,
  Filter,
  Camera,
} from 'lucide-vue-next';
import { studentService } from '../../services/studentService';
import { adminAcademicService } from '../../services/adminAcademicService';

type Student = {
  dbId: number;
  name: string;
  id: string;
  class: string;
  parent: string;
  contact: string;
  gender: 'Male' | 'Female';
  classId: number | null;
  academicYearId: number | null;
  email: string;
  photo?: string;
  generation?: string;
  // Biometric fields
  cardId?: string;
  fingerprintEnrolled?: boolean;
  lastBiometricScan?: string;
};

type StudentForm = {
  name: string;
  class: string;
  parent: string;
  contact: string;
  gender: 'Male' | 'Female';
  classId: string;
  generation: string;
  photo: string;
  // Biometric fields
  cardId: string;
  fingerprintEnrolled: boolean;
};

type BackendStudent = {
  id: number;
  fullname?: string;
  username?: string;
  class?: string | { id?: number; class_name?: string };
  class_id?: number | null;
  academic_year_id?: number | null;
  parent_number?: string;
  contact?: string;
  gender?: 'Male' | 'Female';
  profile?: string | null;
  generation?: string;
  email?: string;
  // Biometric fields
  card_id?: string | null;
  fingerprint_enrolled?: boolean;
  last_biometric_scan?: string | null;
};

type BackendClass = {
  id: number;
  class_name: string;
  room_number: string;
  academic_year_id: number | null;
};

const DEFAULT_STUDENT_PHOTO = '/PictureUseInPageLogin.png';

const students = ref<Student[]>([]);
const classes = ref<BackendClass[]>([]);
const loading = ref(false);
const saving = ref(false);
const uploadingPhoto = ref(false);
const errorMessage = ref('');
const isAddModalOpen = ref(false);
const isBulkModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isPreviewModalOpen = ref(false);
const selectedStudent = ref<Student | null>(null);
const editingStudent = ref<Student | null>(null);
const searchQuery = ref('');
const classFilter = ref('All Classes');
const subjectFilter = ref('All Subjects');
const sectionFilter = ref('All Sections');
const viewMode = ref<'table' | 'grid'>('table');
const studentsPerPage = 12;
const currentPage = ref(1);
const totalStudents = ref(0);
const totalPages = ref(1);
const pageFrom = ref(0);
const pageTo = ref(0);
const newStudent = ref<StudentForm>({
  name: '',
  class: '',
  parent: '',
  contact: '',
  gender: 'Male',
  classId: '',
  generation: 'PNC2026',
  photo: '',
  cardId: '',
  fingerprintEnrolled: false,
});
const bulkData = ref('');

const classOptions = computed(() =>
  classes.value.map((item) => ({
    id: item.id,
    name: item.class_name,
    academicYearId: item.academic_year_id,
  }))
);

const generationOptions = computed(() => {
  const set = new Set(
    students.value
      .map((student) => String(student.generation || '').trim())
      .filter(Boolean)
  );

  if (set.size === 0) {
    set.add(`PNC${new Date().getFullYear()}`);
  }

  return Array.from(set).sort();
});

const subjectOptions = computed(() => {
  const set = new Set(
    students.value
      .map((student) => String(student.generation || '').trim())
      .filter(Boolean)
  );
  return ['All Subjects', ...Array.from(set).sort()];
});

const sectionOptions = computed(() => {
  const set = new Set(
    classOptions.value
      .map((item) => String(item.name || '').trim())
      .filter(Boolean)
  );
  return ['All Sections', ...Array.from(set).sort()];
});

const toUiStudent = (student: BackendStudent): Student => {
  const classFromRelation =
    typeof student?.class === 'object' && student?.class
      ? String(student.class.class_name || '')
      : '';

  const className =
    typeof student?.class === 'string'
      ? student.class
      : classFromRelation;

  return {
    dbId: Number(student?.id || 0),
    name: String(student?.fullname || 'Unknown'),
    id: String(student?.username || `STU-${student?.id || ''}`),
    class: String(className || 'Unknown'),
    parent: String(student?.parent_number || ''),
    contact: String(student?.contact || ''),
    gender: student?.gender === 'Female' ? 'Female' : 'Male',
    classId:
      typeof student?.class === 'object' && student?.class?.id
        ? Number(student.class.id)
        : student?.class_id
          ? Number(student.class_id)
          : null,
    academicYearId: student?.academic_year_id ? Number(student.academic_year_id) : null,
    email: String(student?.email || ''),
    photo: String(student?.profile || DEFAULT_STUDENT_PHOTO),
    generation: String(student?.generation || ''),
    // Biometric fields
    cardId: String(student?.card_id || ''),
    fingerprintEnrolled: Boolean(student?.fingerprint_enrolled),
    lastBiometricScan: student?.last_biometric_scan || undefined,
  };
};

const normalizeStudentData = (payload: any): BackendStudent[] => {
  if (Array.isArray(payload?.data)) return payload.data;
  if (Array.isArray(payload)) return payload;
  return [];
};

const buildStudentFilters = () => {
  const filters: Record<string, string | number> = {};
  const search = searchQuery.value.trim();

  if (search) {
    filters.search = search;
  }

  if (subjectFilter.value !== 'All Subjects') {
    filters.generation = subjectFilter.value;
  }

  if (sectionFilter.value !== 'All Sections') {
    filters.section = sectionFilter.value;
  }

  if (classFilter.value !== 'All Classes') {
    const selected = classOptions.value.find((item) => item.name === classFilter.value);
    if (selected) {
      filters.class_id = selected.id;
    }
  }

  return filters;
};

const loadStudents = async () => {
  const response = await studentService.getStudents(
    currentPage.value,
    studentsPerPage,
    buildStudentFilters()
  );
  const batch = normalizeStudentData(response);

  students.value = batch.map(toUiStudent);
  totalStudents.value = Number(response?.total || batch.length || 0);
  totalPages.value = Math.max(Number(response?.last_page || 1), 1);
  pageFrom.value = Number(response?.from || (batch.length ? 1 : 0));
  pageTo.value = Number(response?.to || batch.length || 0);

  if (batch.length === 0 && totalStudents.value > 0 && currentPage.value > totalPages.value) {
    currentPage.value = totalPages.value;
  }
};

const loadClasses = async () => {
  const classData = await adminAcademicService.getClasses();
  classes.value = Array.isArray(classData) ? classData : [];
};

const loadData = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    await Promise.all([loadStudents(), loadClasses()]);
    if (!newStudent.value.class && classOptions.value.length > 0) {
      newStudent.value.class = classOptions.value[0].name;
      newStudent.value.classId = String(classOptions.value[0].id);
    }
  } catch (error: any) {
    errorMessage.value = error?.message || 'Failed to load students from backend.';
  } finally {
    loading.value = false;
  }
};

const applyClassSelection = (form: StudentForm) => {
  const selected = classOptions.value.find((item) => String(item.id) === form.classId)
    || classOptions.value.find((item) => item.name === form.class)
    || null;

  return {
    className: selected?.name || form.class || null,
    classId: selected?.id || (form.classId ? Number(form.classId) : null),
    academicYearId: selected?.academicYearId || null,
  };
};

const buildEmail = (username: string) =>
  `${username.toLowerCase().replace(/[^a-z0-9.]/g, '')}@student.passerellesnumeriques.org`;

// Generate email from student name (e.g., "Sok Thalita" → "thalita.sok@student.passerellesnumeriques.org")
const buildEmailFromName = (name: string): string => {
  if (!name) return '';
  // Split name into parts
  const nameParts = name.trim().split(/\s+/);
  if (nameParts.length === 0) return '';
  
  // For "Sok Thalita": last name first, then first name
  // Take all parts after first as last name, first part as first name
  const firstName = nameParts[0].toLowerCase().replace(/[^a-z0-9]/g, '');
  const lastName = nameParts.length > 1 
    ? nameParts[nameParts.length - 1].toLowerCase().replace(/[^a-z0-9]/g, '') 
    : '';
  
  // Build email: lastName.firstName@domain or firstName@domain if no last name
  // Ensure it doesn't start or end with a dot
  let emailPrefix = lastName 
    ? `${lastName}.${firstName}` 
    : firstName;
  
  // Clean up any double dots or leading/trailing dots
  emailPrefix = emailPrefix.replace(/\.+/g, '.').replace(/^\.|\.$/g, '');
  
  return `${emailPrefix}@student.passerellesnumeriques.org`;
};

const normalizeProfileValue = (value: string | undefined) => {
  const trimmed = String(value || '').trim();
  if (!trimmed) return DEFAULT_STUDENT_PHOTO;
  // Backend `profile` column is varchar(255). Skip oversized base64 payloads.
  if (trimmed.length > 255) return DEFAULT_STUDENT_PHOTO;
  return trimmed;
};

const handlePhotoUpload = async (e: Event, isEdit = false) => {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;

  if (isEdit && editingStudent.value?.dbId) {
    uploadingPhoto.value = true;
    errorMessage.value = '';
    try {
      const response = await studentService.uploadStudentPhoto(editingStudent.value.dbId, file);
      editingStudent.value = {
        ...editingStudent.value,
        photo: String(response?.profile || ''),
      };
      await loadStudents();
    } catch (error: any) {
      errorMessage.value = error?.message || 'Failed to upload student photo.';
    } finally {
      uploadingPhoto.value = false;
      input.value = '';
    }
    return;
  }

  const reader = new FileReader();
  reader.onloadend = () => {
    if (isEdit && editingStudent.value) {
      editingStudent.value = { ...editingStudent.value, photo: String(reader.result) };
    } else {
      newStudent.value = { ...newStudent.value, photo: String(reader.result) };
    }
    input.value = '';
  };
  reader.readAsDataURL(file);
};

const getNextIdForGeneration = (gen: string) => {
  const genStudents = students.value.filter((s) => s.id.startsWith(gen));
  if (genStudents.length === 0) return 1;
  const maxId = Math.max(...genStudents.map((s) => parseInt(s.id.split('-')[1]) || 0));
  return maxId + 1;
};

const currentNextId = computed(() => getNextIdForGeneration(newStudent.value.generation));
const currentNewStudentId = computed(
  () => `${newStudent.value.generation}-${String(currentNextId.value).padStart(3, '0')}`
);

const previewNewStudent = computed<Student>(() => ({
  dbId: 0,
  name: newStudent.value.name,
  class: newStudent.value.class,
  parent: newStudent.value.parent,
  contact: newStudent.value.contact,
  gender: newStudent.value.gender,
  classId: newStudent.value.classId ? Number(newStudent.value.classId) : null,
  academicYearId: null,
  email: '',
  photo: newStudent.value.photo,
  generation: newStudent.value.generation,
  id: currentNewStudentId.value,
}));

const resetNewStudent = () => {
  const defaultClass = classOptions.value[0] || null;
  newStudent.value = {
    name: '',
    class: defaultClass?.name || '',
    parent: '',
    contact: '',
    gender: 'Male',
    classId: defaultClass ? String(defaultClass.id) : '',
    generation: generationOptions.value[0] || `PNC${new Date().getFullYear()}`,
    photo: '',
    cardId: '',
    fingerprintEnrolled: false,
  };
};

const handleAddStudent = async () => {
  if (saving.value) return;
  if (!newStudent.value.name || !newStudent.value.parent || !newStudent.value.contact) return;

  saving.value = true;
  errorMessage.value = '';

  const classSelection = applyClassSelection(newStudent.value);
  const username = currentNewStudentId.value;

  try {
    const response = await studentService.createStudent({
      fullname: newStudent.value.name,
      username,
      email: buildEmailFromName(newStudent.value.name), // Auto-generate email from name
      generation: newStudent.value.generation,
      class: classSelection.className,
      class_id: classSelection.classId,
      academic_year_id: classSelection.academicYearId,
      profile: normalizeProfileValue(newStudent.value.photo),
      gender: newStudent.value.gender,
      parent_number: newStudent.value.parent,
      contact: newStudent.value.contact,
      // Biometric fields
      card_id: newStudent.value.cardId || null,
      fingerprint_enrolled: newStudent.value.fingerprintEnrolled,
    });

    // Handle the new response format with student and user data
    // Note: studentService returns response.data already, so we access response.student directly
    if (response.student) {
      // Normalize the backend student data before adding to list
      const normalizedStudent = normalizeStudentData({ data: [response.student] });
      if (normalizedStudent.length > 0) {
        students.value.unshift(toUiStudent(normalizedStudent[0]));
      }
    } else {
      // Fallback for other response formats
      students.value.unshift(response);
    }

    isAddModalOpen.value = false;
    resetNewStudent();
    await loadStudents();
  } catch (error: any) {
    errorMessage.value = error?.message || 'Failed to create student.';
  } finally {
    saving.value = false;
  }
};

const handleBulkAdd = async () => {
  if (saving.value) return;

  const names = bulkData.value
    .split('\n')
    .map((n) => n.trim())
    .filter((n) => n !== '');

  if (names.length === 0) return;

  saving.value = true;
  errorMessage.value = '';

  const selectedClass = classOptions.value[0] || null;
  const generation = generationOptions.value[0] || `PNC${new Date().getFullYear()}`;
  let nextId = getNextIdForGeneration(generation);

  try {
    const studentsPayload = names.map((name) => {
      const username = `${generation}-${String(nextId).padStart(3, '0')}`;
      nextId += 1;

      return {
        fullname: name,
        username,
        email: buildEmailFromName(name), // Auto-generate email from name
        generation,
        class: selectedClass?.name || null,
        class_id: selectedClass?.id || null,
        academic_year_id: selectedClass?.academicYearId || null,
        profile: DEFAULT_STUDENT_PHOTO,
        gender: 'Male',
        parent_number: 'N/A',
        contact: 'N/A',
      };
    });

    await studentService.bulkCreateStudents({
      students: studentsPayload,
      default_password: 'password123',
    });

    isBulkModalOpen.value = false;
    bulkData.value = '';
    await loadStudents();
  } catch (error: any) {
    errorMessage.value = error?.message || 'Failed to import students.';
  } finally {
    saving.value = false;
  }
};

const openEditStudent = (student: Student) => {
  editingStudent.value = { ...student };
  if (!editingStudent.value.classId) {
    const matched = classOptions.value.find((item) => item.name === editingStudent.value?.class);
    editingStudent.value.classId = matched?.id || null;
  }
  isEditModalOpen.value = true;
};

const handleEditStudent = async () => {
  if (!editingStudent.value) return;
  if (saving.value) return;

  saving.value = true;
  errorMessage.value = '';

  const selected = classOptions.value.find(
    (item) => item.id === editingStudent.value?.classId || item.name === editingStudent.value?.class
  );

  try {
    const payload: Record<string, unknown> = {
      fullname: editingStudent.value.name,
      class: selected?.name || editingStudent.value.class || null,
      class_id: selected?.id || editingStudent.value.classId || null,
      academic_year_id: selected?.academicYearId || editingStudent.value.academicYearId || null,
      profile: normalizeProfileValue(editingStudent.value.photo),
      gender: editingStudent.value.gender || 'Male',
      parent_number: editingStudent.value.parent,
      contact: editingStudent.value.contact || editingStudent.value.parent,
      // Biometric fields
      card_id: editingStudent.value.cardId || null,
      fingerprint_enrolled: editingStudent.value.fingerprintEnrolled || false,
    };

    const generation = String(editingStudent.value.generation || '').trim();
    if (generation) {
      payload.generation = generation;
    }

    await studentService.updateStudent(editingStudent.value.dbId, payload);

    isEditModalOpen.value = false;
    editingStudent.value = null;
    await loadStudents();
  } catch (error: any) {
    errorMessage.value = error?.message || 'Failed to update student.';
  } finally {
    saving.value = false;
  }
};

const handleDeleteStudent = async (id: string) => {
  const target = students.value.find((student) => student.id === id);
  if (!target) return;

  errorMessage.value = '';
  try {
    await studentService.deleteStudent(target.dbId);
    await loadStudents();
  } catch (error: any) {
    errorMessage.value = error?.message || 'Failed to delete student.';
  }
};

const isDeleteModalOpen = ref(false);
const studentToDelete = ref<Student | null>(null);

const openDeleteModal = (student: Student) => {
  studentToDelete.value = student;
  isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false;
  studentToDelete.value = null;
};

const confirmDeleteStudent = async () => {
  if (!studentToDelete.value) return;
  
  const target = studentToDelete.value;
  closeDeleteModal();
  
  errorMessage.value = '';
  try {
    await studentService.deleteStudent(target.dbId);
    await loadStudents();
  } catch (error: any) {
    errorMessage.value = error?.message || 'Failed to delete student.';
  }
};

const handlePrint = () => {
  window.print();
};

const paginatedStudents = computed(() => students.value);

const pageNumbers = computed(() =>
  Array.from({ length: totalPages.value }, (_, index) => index + 1)
);

let studentReloadDebounce: ReturnType<typeof setTimeout> | null = null;

const reloadStudentsDebounced = () => {
  if (studentReloadDebounce) {
    clearTimeout(studentReloadDebounce);
  }
  studentReloadDebounce = setTimeout(() => {
    loadStudents();
  }, 250);
};

watch([searchQuery, classFilter, subjectFilter, sectionFilter], () => {
  if (currentPage.value !== 1) {
    currentPage.value = 1;
    return;
  }
  reloadStudentsDebounced();
});

watch(currentPage, () => {
  loadStudents();
});

const studentGeneration = (student: Student | null) =>
  student?.generation || student?.id?.split('-')[0] || 'PNC2026';

const studentPhoto = (student: Student | null) =>
  student?.photo || DEFAULT_STUDENT_PHOTO;

onMounted(async () => {
  await loadData();
  resetNewStudent();
});
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Student Management</h2>
        <p class="text-sm text-slate-500 font-medium">Add, edit, and manage student identification</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          @click="isBulkModalOpen = true"
          class="flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-700 rounded-lg font-bold text-sm hover:bg-slate-200 transition-all"
        >
          <Upload class="size-4" />
          Bulk Import
        </button>
        <button
          @click="isAddModalOpen = true"
          class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all"
        >
          <UserPlus class="size-4" />
          Add Student
        </button>
      </div>
    </div>

    <p v-if="errorMessage" class="p-3 rounded-lg bg-rose-50 text-rose-700 text-sm">{{ errorMessage }}</p>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <div class="relative max-w-xs w-full">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search students..."
              class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
            />
          </div>
          <select
            v-model="classFilter"
            class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/20 min-w-[140px]"
          >
            <option>All Classes</option>
            <option v-for="item in classOptions" :key="item.id" :value="item.name">{{ item.name }}</option>
          </select>
          <select v-model="subjectFilter" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/20 min-w-[140px]">
            <option v-for="subject in subjectOptions" :key="subject" :value="subject">{{ subject }}</option>
          </select>
          <select v-model="sectionFilter" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/20 min-w-[140px]">
            <option v-for="section in sectionOptions" :key="section" :value="section">{{ section }}</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <button class="flex items-center gap-2 px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50">
            <Filter class="size-4" />
            Filters
          </button>
          <button @click="handlePrint" class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 transition-all" title="Print List">
            <Printer class="size-4 text-slate-500" />
          </button>
          <div class="flex items-center bg-slate-100 p-1 rounded-lg border border-slate-200">
            <button
              @click="viewMode = 'table'"
              :class="[
                'p-1.5 rounded-md transition-all',
                viewMode === 'table' ? 'bg-white shadow-sm text-primary' : 'text-slate-400 hover:text-slate-600',
              ]"
            >
              <List class="size-4" />
            </button>
            <button
              @click="viewMode = 'grid'"
              :class="[
                'p-1.5 rounded-md transition-all',
                viewMode === 'grid' ? 'bg-white shadow-sm text-primary' : 'text-slate-400 hover:text-slate-600',
              ]"
            >
              <LayoutGrid class="size-4" />
            </button>
          </div>
        </div>
      </div>

      <table v-if="viewMode === 'table'" class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold tracking-wider">
          <tr>
            <th class="px-6 py-4">Student</th>
            <th class="px-6 py-4">Class</th>
            <th class="px-6 py-4">Parent Contact</th>
            <th class="px-6 py-4">Biometric</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td :colspan="5" class="px-6 py-10 text-center text-slate-400 italic">Loading students...</td>
          </tr>
          <tr v-for="s in paginatedStudents" :key="s.dbId || s.id" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="size-8 rounded-full bg-slate-100 overflow-hidden">
                  <img :src="studentPhoto(s)" alt="" class="w-full h-full object-cover" />
                </div>
                <div>
                  <div class="font-bold text-slate-900">{{ s.name }}</div>
                  <div class="text-[10px] text-slate-400 font-mono">{{ s.id }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 font-medium text-slate-600">{{ s.class }}</td>
            <td class="px-6 py-4 text-slate-500">{{ s.parent }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <span v-if="s.cardId" class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium" title="Card ID: {{ s.cardId }}">
                  Card
                </span>
                <span v-if="s.fingerprintEnrolled" class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">
                  Fingerprint
                </span>
                <span v-if="!s.cardId && !s.fingerprintEnrolled" class="text-slate-400 text-xs">
                  Not enrolled
                </span>
              </div>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-1">
                <button
                  @click="selectedStudent = s; isPreviewModalOpen = true"
                  class="p-2 text-slate-400 hover:text-primary hover:bg-primary/5 rounded-lg"
                  title="ID Preview"
                >
                  <QrCode class="size-4" />
                </button>
                <button
                  @click="openEditStudent(s)"
                  class="p-2 text-slate-400 hover:text-primary hover:bg-primary/5 rounded-lg"
                  title="Edit"
                >
                  <Edit3 class="size-4" />
                </button>
                <button
                  @click="openDeleteModal(s)"
                  class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg"
                  title="Delete"
                >
                  <Trash2 class="size-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 bg-slate-50/30">
        <div v-for="s in paginatedStudents" :key="s.dbId || s.id" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
          <div class="absolute top-0 right-0 p-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
            <button @click="openEditStudent(s)" class="p-1.5 bg-white border border-slate-200 rounded-lg text-slate-400 hover:text-primary shadow-sm">
              <Edit3 class="size-3.5" />
            </button>
            <button @click="openDeleteModal(s)" class="p-1.5 bg-white border border-slate-200 rounded-lg text-slate-400 hover:text-red-500 shadow-sm">
              <Trash2 class="size-3.5" />
            </button>
          </div>

          <div class="flex flex-col items-center text-center space-y-4">
            <div class="size-24 rounded-full border-4 border-slate-50 p-1 bg-white shadow-inner overflow-hidden">
              <img :src="studentPhoto(s)" alt="" class="w-full h-full object-cover rounded-full" />
            </div>
            <div>
              <h4 class="font-bold text-slate-900">{{ s.name }}</h4>
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ s.id }}</p>
            </div>
            <div class="flex items-center gap-2 w-full">
              <div class="flex-1 px-3 py-1.5 bg-slate-50 rounded-lg text-[10px] font-bold text-slate-600 border border-slate-100">CLASS {{ s.class }}</div>
              <button @click="selectedStudent = s; isPreviewModalOpen = true" class="p-1.5 bg-primary/10 text-primary rounded-lg hover:bg-primary/20 transition-colors">
                <QrCode class="size-4" />
              </button>
            </div>
            <div class="flex items-center justify-center gap-3 pt-2 w-full">
              <button class="size-8 rounded-full border border-green-200 text-green-500 font-bold text-xs flex items-center justify-center hover:bg-green-500 hover:text-white transition-all">P</button>
              <button class="size-8 rounded-full border border-red-200 text-red-500 font-bold text-xs flex items-center justify-center hover:bg-red-500 hover:text-white transition-all">A</button>
              <button class="size-8 rounded-full border border-amber-200 text-amber-500 font-bold text-xs flex items-center justify-center hover:bg-amber-500 hover:text-white transition-all">L</button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!loading && totalStudents > 0" class="px-4 sm:px-6 py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/70">
        <div class="flex items-center gap-2 text-xs">
          <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-white border border-slate-200 text-slate-700 font-semibold">
            Showing {{ pageFrom }}-{{ pageTo }} of {{ totalStudents }}
          </span>
        </div>
        <div v-if="totalPages > 1" class="w-full sm:w-auto flex items-center gap-1 overflow-x-auto">
          <button
            @click="currentPage = Math.max(currentPage - 1, 1)"
            :disabled="currentPage === 1"
            class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-100 transition-colors"
          >
            Previous
          </button>
          <button
            v-for="page in pageNumbers"
            :key="page"
            @click="currentPage = page"
            :class="[
              'min-w-9 px-3 py-2 text-xs font-semibold rounded-lg border transition-colors',
              page === currentPage
                ? 'bg-primary text-white border-primary'
                : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100',
            ]"
          >
            {{ page }}
          </button>
          <button
            @click="currentPage = Math.min(currentPage + 1, totalPages)"
            :disabled="currentPage === totalPages"
            class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-100 transition-colors"
          >
            Next
          </button>
        </div>
      </div>

      <div v-if="!loading && totalStudents === 0" class="p-12 text-center">
        <div class="size-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
          <Search class="size-8 text-slate-300" />
        </div>
        <p class="text-slate-400 italic">No students found matching your criteria.</p>
      </div>
    </div>

    <Modal :is-open="isAddModalOpen" title="Add New Student" size="lg" @close="isAddModalOpen = false">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="space-y-6">
          <div class="flex items-center gap-4">
            <div class="relative group">
              <div class="size-20 rounded-full bg-slate-100 border-2 border-slate-200 overflow-hidden flex items-center justify-center">
                <img v-if="newStudent.photo" :src="newStudent.photo" alt="" class="w-full h-full object-cover" />
                <Camera v-else class="size-8 text-slate-300" />
              </div>
              <label class="absolute inset-0 flex items-center justify-center bg-black/40 text-white opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer rounded-full">
                <Upload class="size-5" />
                <input type="file" class="hidden" accept="image/*" @change="handlePhotoUpload($event)" />
              </label>
            </div>
            <div>
              <h4 class="font-bold text-slate-900">Student Photo</h4>
              <p class="text-xs text-slate-500">Upload a clear portrait photo</p>
            </div>
          </div>

          <div class="space-y-4">
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Full Name</label>
              <input v-model="newStudent.name" type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" placeholder="e.g. Sat Vichet" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase">Generation</label>
                <select v-model="newStudent.generation" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20">
                  <option v-for="generation in generationOptions" :key="generation" :value="generation">{{ generation }}</option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase">Student ID (Auto)</label>
                <input type="text" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm outline-none" :value="currentNewStudentId" readonly />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase">Class</label>
                <select
                  v-model="newStudent.classId"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
                >
                  <option v-for="item in classOptions" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase">Gender</label>
                <select v-model="newStudent.gender" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20">
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Parent Name</label>
              <input v-model="newStudent.parent" type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Contact Number</label>
              <input v-model="newStudent.contact" type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
            <p class="text-xs text-slate-500">Default password for new student account: <strong>password123</strong></p>

            <!-- Biometric Section -->
            <div class="border-t border-slate-200 pt-4 mt-4">
              <h4 class="font-bold text-slate-900 mb-3">Biometric Enrollment</h4>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase">Card ID (RFID/NFC)</label>
                  <input v-model="newStudent.cardId" type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" placeholder="e.g. CARD-001" />
                </div>
                <div class="space-y-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase">Fingerprint Status</label>
                  <div class="flex items-center gap-2 mt-2">
                    <input v-model="newStudent.fingerprintEnrolled" type="checkbox" id="fingerprintEnrolled" class="w-4 h-4 text-primary rounded" />
                    <label for="fingerprintEnrolled" class="text-sm text-slate-700">Enrolled</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button @click="isAddModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
            <button :disabled="saving" @click="handleAddStudent" class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg shadow-lg shadow-primary/20 disabled:opacity-60">
              {{ saving ? 'Saving...' : 'Save Student' }}
            </button>
          </div>
        </div>

        <div class="flex flex-col items-center justify-center bg-slate-50 rounded-2xl p-4 lg:p-8 border border-slate-100 overflow-hidden">
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Live ID Preview</p>
          <div class="scale-75 sm:scale-90 lg:scale-100 transition-transform origin-center">
            <div class="w-[280px] h-[400px] bg-white rounded-xl shadow-2xl overflow-hidden relative border border-slate-200 flex flex-col items-center p-6 text-slate-900 shrink-0">
              <svg class="absolute inset-0 w-full h-full opacity-10 pointer-events-none" viewBox="0 0 100 100" preserveAspectRatio="none">
                <path d="M0 20 L20 20 L30 10 M80 0 L80 20 L100 40 M0 80 L20 80 L40 100" stroke="currentColor" fill="none" stroke-width="0.5" />
                <circle cx="20" cy="20" r="1" fill="currentColor" />
                <circle cx="30" cy="10" r="1" fill="currentColor" />
                <circle cx="80" cy="20" r="1" fill="currentColor" />
              </svg>

              <div class="w-full flex justify-between items-start z-10">
                <div class="flex flex-col items-center">
                  <div class="size-10 bg-slate-900 rounded-full flex items-center justify-center text-white font-black text-lg relative">
                    PN
                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-6 h-1 bg-sky-500"></div>
                  </div>
                </div>
                <div class="text-xl font-black text-slate-800 tracking-tighter">{{ studentGeneration(previewNewStudent) }}</div>
              </div>

              <div class="mt-8 relative z-10">
                <div class="size-32 rounded-full border-4 border-sky-400 p-1 bg-white overflow-hidden">
                  <div class="w-full h-full rounded-full overflow-hidden bg-slate-100">
                    <img :src="studentPhoto(previewNewStudent)" alt="" class="w-full h-full object-cover" />
                  </div>
                </div>
                <div class="absolute -inset-2 border-t-4 border-l-4 border-sky-500 rounded-full opacity-50"></div>
              </div>

              <div class="mt-8 text-center z-10 space-y-1">
                <h3 class="text-2xl font-black text-sky-900 leading-tight">{{ previewNewStudent.name || 'Student Name' }}</h3>
                <p class="text-[10px] font-bold text-slate-500 tracking-[0.2em] uppercase">STUDENT</p>
              </div>

              <div class="mt-auto z-10 pb-2">
                <p class="text-base font-bold text-slate-800 tracking-tight">ID NB: <span class="font-black">{{ previewNewStudent.id || 'PNC2026-XXX' }}</span></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Modal>

    <Modal :is-open="isBulkModalOpen" title="Bulk Import Students" size="md" @close="isBulkModalOpen = false">
      <div class="space-y-4">
        <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl">
          <p class="text-xs text-blue-700 leading-relaxed"><strong>Instructions:</strong> Enter student names one per line. We automatically assign IDs and create accounts with default password <strong>password123</strong>. You can edit details later.</p>
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Student Names (One per line)</label>
          <textarea v-model="bulkData" class="w-full h-48 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20 font-mono" placeholder="John Doe&#10;Jane Smith&#10;Michael Brown"></textarea>
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button @click="isBulkModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button :disabled="saving" @click="handleBulkAdd" class="px-6 py-2 text-sm font-bold text-white bg-primary rounded-lg shadow-lg shadow-primary/20 disabled:opacity-60">
            {{ saving ? 'Importing...' : 'Import Students' }}
          </button>
        </div>
      </div>
    </Modal>

    <Modal :is-open="isPreviewModalOpen" title="Student ID Card" size="sm" @close="isPreviewModalOpen = false">
      <div class="space-y-6 flex flex-col items-center" v-if="selectedStudent">
        <div class="w-[280px] h-[400px] bg-white rounded-xl shadow-2xl overflow-hidden relative border border-slate-200 flex flex-col items-center p-6 text-slate-900 shrink-0">
          <svg class="absolute inset-0 w-full h-full opacity-10 pointer-events-none" viewBox="0 0 100 100" preserveAspectRatio="none">
            <path d="M0 20 L20 20 L30 10 M80 0 L80 20 L100 40 M0 80 L20 80 L40 100" stroke="currentColor" fill="none" stroke-width="0.5" />
            <circle cx="20" cy="20" r="1" fill="currentColor" />
            <circle cx="30" cy="10" r="1" fill="currentColor" />
            <circle cx="80" cy="20" r="1" fill="currentColor" />
          </svg>

          <div class="w-full flex justify-between items-start z-10">
            <div class="flex flex-col items-center">
              <div class="size-10 bg-slate-900 rounded-full flex items-center justify-center text-white font-black text-lg relative">
                PN
                <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-6 h-1 bg-sky-500"></div>
              </div>
            </div>
            <div class="text-xl font-black text-slate-800 tracking-tighter">{{ studentGeneration(selectedStudent) }}</div>
          </div>

          <div class="mt-8 relative z-10">
            <div class="size-32 rounded-full border-4 border-sky-400 p-1 bg-white overflow-hidden">
              <div class="w-full h-full rounded-full overflow-hidden bg-slate-100">
                <img :src="studentPhoto(selectedStudent)" alt="" class="w-full h-full object-cover" />
              </div>
            </div>
            <div class="absolute -inset-2 border-t-4 border-l-4 border-sky-500 rounded-full opacity-50"></div>
          </div>

          <div class="mt-8 text-center z-10 space-y-1">
            <h3 class="text-2xl font-black text-sky-900 leading-tight">{{ selectedStudent.name || 'Student Name' }}</h3>
            <p class="text-[10px] font-bold text-slate-500 tracking-[0.2em] uppercase">STUDENT</p>
          </div>

          <div class="mt-auto z-10 pb-2">
            <p class="text-base font-bold text-slate-800 tracking-tight">ID NB: <span class="font-black">{{ selectedStudent.id || 'PNC2026-XXX' }}</span></p>
          </div>
        </div>

        <button @click="handlePrint" class="w-full flex items-center justify-center gap-2 py-3 bg-slate-900 text-white rounded-xl font-bold text-sm shadow-xl hover:bg-slate-800 transition-all">
          <Printer class="size-4" />
          Print ID Card
        </button>
      </div>
    </Modal>

    <Modal :is-open="isEditModalOpen" title="Edit Student Information" size="lg" @close="isEditModalOpen = false">
      <div class="space-y-6" v-if="editingStudent">
        <div class="flex items-center gap-4">
          <div class="relative group">
            <div class="size-20 rounded-full bg-slate-100 border-2 border-slate-200 overflow-hidden flex items-center justify-center">
              <img :src="studentPhoto(editingStudent)" alt="" class="w-full h-full object-cover" />
            </div>
            <label class="absolute inset-0 flex items-center justify-center bg-black/40 text-white opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer rounded-full">
              <Upload class="size-5" />
              <input type="file" class="hidden" accept="image/*" @change="handlePhotoUpload($event, true)" />
            </label>
          </div>
          <div>
            <h4 class="font-bold text-slate-900">Update Photo</h4>
            <p class="text-xs text-slate-500">Change the student's identification photo</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Full Name</label>
            <input v-model="editingStudent.name" type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" />
          </div>
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Student ID</label>
            <input type="text" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm outline-none" :value="editingStudent.id" readonly />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Class</label>
            <select v-model.number="editingStudent.classId" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20">
              <option v-for="item in classOptions" :key="item.id" :value="item.id">{{ item.name }}</option>
            </select>
          </div>
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Parent Contact</label>
            <input v-model="editingStudent.parent" type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" />
          </div>
        </div>

        <!-- Biometric Section in Edit Modal -->
        <div class="border-t border-slate-200 pt-4 mt-4">
          <h4 class="font-bold text-slate-900 mb-3">Biometric Enrollment</h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Card ID (RFID/NFC)</label>
              <input v-model="editingStudent.cardId" type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" placeholder="e.g. CARD-001" />
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Fingerprint Status</label>
              <div class="flex items-center gap-2 mt-2">
                <input v-model="editingStudent.fingerprintEnrolled" type="checkbox" id="editFingerprintEnrolled" class="w-4 h-4 text-primary rounded" />
                <label for="editFingerprintEnrolled" class="text-sm text-slate-700">Enrolled</label>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button @click="isEditModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button :disabled="saving" @click="handleEditStudent" class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg shadow-lg shadow-primary/20 disabled:opacity-60">
            {{ saving ? 'Updating...' : 'Update Student' }}
          </button>
        </div>
      </div>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      :is-open="isDeleteModalOpen"
      title="Delete Student"
      :message="`Are you sure you want to delete ${studentToDelete?.name || 'this student'}? This action cannot be undone.`"
      confirm-text="Delete"
      cancel-text="Cancel"
      variant="danger"
      @confirm="confirmDeleteStudent"
      @cancel="closeDeleteModal"
    />
  </div>
</template>
