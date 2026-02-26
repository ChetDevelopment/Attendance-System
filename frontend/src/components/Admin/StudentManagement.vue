<script setup lang="ts">
import { computed, ref } from 'vue';
import Modal from './Modal.vue';
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

type Student = {
  name: string;
  id: string;
  class: string;
  parent: string;
  photo?: string;
  generation?: string;
};

type StudentForm = {
  name: string;
  class: string;
  parent: string;
  id: string;
  generation: string;
  photo: string;
};

const initialStudents: Student[] = [
  { name: 'Sat Vichet', id: 'PNC2026-053', class: '10A', parent: '+1 234 567 890' },
  { name: 'Lara Croft', id: 'PNC2026-124', class: '12B', parent: '+1 987 654 321' },
];

const students = ref<Student[]>(initialStudents);
const isAddModalOpen = ref(false);
const isBulkModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isPreviewModalOpen = ref(false);
const selectedStudent = ref<Student | null>(null);
const editingStudent = ref<Student | null>(null);
const searchQuery = ref('');
const classFilter = ref('All Classes');
const viewMode = ref<'table' | 'grid'>('table');
const newStudent = ref<StudentForm>({
  name: '',
  class: '10A',
  parent: '',
  id: '',
  generation: 'PNC2026',
  photo: '',
});
const bulkData = ref('');

const handlePhotoUpload = (e: Event, isEdit = false) => {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onloadend = () => {
    if (isEdit && editingStudent.value) {
      editingStudent.value = { ...editingStudent.value, photo: String(reader.result) };
    } else {
      newStudent.value = { ...newStudent.value, photo: String(reader.result) };
    }
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
  ...newStudent.value,
  id: currentNewStudentId.value,
}));

const handleAddStudent = () => {
  if (newStudent.value.name && newStudent.value.parent) {
    students.value = [...students.value, { ...newStudent.value, id: currentNewStudentId.value }];
    isAddModalOpen.value = false;
    newStudent.value = {
      name: '',
      class: '10A',
      parent: '',
      id: '',
      generation: 'PNC2026',
      photo: '',
    };
  }
};

const handleBulkAdd = () => {
  const names = bulkData.value
    .split('\n')
    .map((n) => n.trim())
    .filter((n) => n !== '');

  if (names.length === 0) return;

  const nextId = getNextIdForGeneration('PNC2026');
  const imported: Student[] = names.map((name, index) => ({
    name,
    id: `PNC2026-${String(nextId + index).padStart(3, '0')}`,
    class: '10A',
    parent: 'Not Provided',
  }));

  students.value = [...students.value, ...imported];
  isBulkModalOpen.value = false;
  bulkData.value = '';
};

const openEditStudent = (student: Student) => {
  editingStudent.value = { ...student };
  isEditModalOpen.value = true;
};

const handleEditStudent = () => {
  if (!editingStudent.value) return;
  const updated = editingStudent.value;
  students.value = students.value.map((s) => (s.id === updated.id ? updated : s));
  isEditModalOpen.value = false;
  editingStudent.value = null;
};

const handleDeleteStudent = (id: string) => {
  students.value = students.value.filter((s) => s.id !== id);
};

const handlePrint = () => {
  window.print();
};

const filteredStudents = computed(() =>
  students.value.filter((s) => {
    const matchesSearch =
      s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      s.id.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesClass = classFilter.value === 'All Classes' || s.class === classFilter.value;
    return matchesSearch && matchesClass;
  })
);

const studentGeneration = (student: Student | null) =>
  student?.generation || student?.id?.split('-')[0] || 'PNC2026';

const studentPhoto = (student: Student | null) =>
  student?.photo ||
  (student?.id
    ? `https://picsum.photos/seed/${student.id}/200/200`
    : 'https://picsum.photos/seed/placeholder/200/200');
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
            <option>10A</option>
            <option>10B</option>
            <option>11A</option>
            <option>12B</option>
          </select>
          <select class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/20 min-w-[140px]">
            <option>Select Subject</option>
            <option>Mathematics</option>
            <option>Science</option>
            <option>English</option>
          </select>
          <select class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/20 min-w-[140px]">
            <option>Select Section</option>
            <option>Section A</option>
            <option>Section B</option>
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
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="(s, i) in filteredStudents" :key="i" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="size-8 rounded-full bg-slate-100 overflow-hidden">
                  <img :src="s.photo || `https://picsum.photos/seed/${s.id}/100/100`" alt="" class="w-full h-full object-cover" />
                </div>
                <div>
                  <div class="font-bold text-slate-900">{{ s.name }}</div>
                  <div class="text-[10px] text-slate-400 font-mono">{{ s.id }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 font-medium text-slate-600">{{ s.class }}</td>
            <td class="px-6 py-4 text-slate-500">{{ s.parent }}</td>
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
                  @click="handleDeleteStudent(s.id)"
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
        <div v-for="(s, i) in filteredStudents" :key="i" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
          <div class="absolute top-0 right-0 p-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
            <button @click="openEditStudent(s)" class="p-1.5 bg-white border border-slate-200 rounded-lg text-slate-400 hover:text-primary shadow-sm">
              <Edit3 class="size-3.5" />
            </button>
            <button @click="handleDeleteStudent(s.id)" class="p-1.5 bg-white border border-slate-200 rounded-lg text-slate-400 hover:text-red-500 shadow-sm">
              <Trash2 class="size-3.5" />
            </button>
          </div>

          <div class="flex flex-col items-center text-center space-y-4">
            <div class="size-24 rounded-full border-4 border-slate-50 p-1 bg-white shadow-inner overflow-hidden">
              <img :src="s.photo || `https://picsum.photos/seed/${s.id}/200/200`" alt="" class="w-full h-full object-cover rounded-full" />
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

      <div v-if="filteredStudents.length === 0" class="p-12 text-center">
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
                  <option value="PNC2024">PNC2024</option>
                  <option value="PNC2025">PNC2025</option>
                  <option value="PNC2026">PNC2026</option>
                  <option value="PNC2027">PNC2027</option>
                  <option value="PNC2028">PNC2028</option>
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
                <select v-model="newStudent.class" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20">
                  <option>10A</option>
                  <option>10B</option>
                  <option>11A</option>
                  <option>12B</option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase">Gender</label>
                <select class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20">
                  <option>Male</option>
                  <option>Female</option>
                </select>
              </div>
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Parent Name</label>
              <input v-model="newStudent.parent" type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase">Contact Number</label>
              <input type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button @click="isAddModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
            <button @click="handleAddStudent" class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg shadow-lg shadow-primary/20">Save Student</button>
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
          <p class="text-xs text-blue-700 leading-relaxed"><strong>Instructions:</strong> Enter student names one per line. We will automatically assign IDs and set the default class to 10A. You can edit individual details later.</p>
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Student Names (One per line)</label>
          <textarea v-model="bulkData" class="w-full h-48 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20 font-mono" placeholder="John Doe&#10;Jane Smith&#10;Michael Brown"></textarea>
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button @click="isBulkModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button @click="handleBulkAdd" class="px-6 py-2 text-sm font-bold text-white bg-primary rounded-lg shadow-lg shadow-primary/20">Import Students</button>
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
              <img :src="editingStudent.photo || `https://picsum.photos/seed/${editingStudent.id}/200/200`" alt="" class="w-full h-full object-cover" />
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
            <select v-model="editingStudent.class" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20">
              <option>10A</option>
              <option>10B</option>
              <option>11A</option>
              <option>12B</option>
            </select>
          </div>
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Parent Contact</label>
            <input v-model="editingStudent.parent" type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" />
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button @click="isEditModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button @click="handleEditStudent" class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg shadow-lg shadow-primary/20">Update Student</button>
        </div>
      </div>
    </Modal>
  </div>
</template>