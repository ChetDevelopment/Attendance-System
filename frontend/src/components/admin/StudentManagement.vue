<script setup lang="ts">
import { ref, computed } from 'vue';
import Modal from './Modal.vue';
import { Search, UserPlus, Upload, QrCode, Printer, Edit3, Trash2, LayoutGrid, List, Camera } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

interface Student {
  name: string;
  id: string;
  class: string;
  parent: string;
  photo?: string;
  generation?: string;
}

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
const editingStudent = ref<any>(null);
const searchQuery = ref('');
const classFilter = ref('All Classes');
const viewMode = ref<'table' | 'grid'>('table');
const newStudent = ref({ name: '', class: '10A', parent: '', id: '', generation: 'PNC2026', photo: '' });
const bulkData = ref('');

const handlePhotoUpload = (e: Event, isEdit: boolean = false) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    const reader = new FileReader();
    reader.onloadend = () => {
      if (isEdit) {
        editingStudent.value = { ...editingStudent.value, photo: reader.result as string };
      } else {
        newStudent.value = { ...newStudent.value, photo: reader.result as string };
      }
    };
    reader.readAsDataURL(file);
  }
};

const getNextIdForGeneration = (gen: string) => {
  const genStudents = students.value.filter(s => s.id.startsWith(gen));
  if (genStudents.length === 0) return 1;
  
  const maxId = Math.max(...genStudents.map(s => {
    const parts = s.id.split('-');
    return parseInt(parts[1]) || 0;
  }));
  return maxId + 1;
};

const currentNextId = computed(() => getNextIdForGeneration(newStudent.value.generation));

const handleAddStudent = () => {
  if (newStudent.value.name && newStudent.value.parent) {
    const studentId = `${newStudent.value.generation}-${String(currentNextId.value).padStart(3, '0')}`;
    const student = {
      ...newStudent.value,
      id: studentId
    };
    students.value.push(student);
    isAddModalOpen.value = false;
    newStudent.value = { name: '', class: '10A', parent: '', id: '', generation: 'PNC2026', photo: '' };
  }
};

const handleBulkAdd = () => {
  const names = bulkData.value.split('\n').map(n => n.trim()).filter(n => n !== '');
  if (names.length > 0) {
    let nextId = getNextIdForGeneration('PNC2026');
    const newStudents = names.map((name, index) => ({
      name,
      id: `PNC2026-${String(nextId + index).padStart(3, '0')}`,
      class: '10A',
      parent: 'Not Provided'
    }));
    students.value.push(...newStudents);
    isBulkModalOpen.value = false;
    bulkData.value = '';
  }
};

const handleEditStudent = () => {
  if (editingStudent.value) {
    students.value = students.value.map(s => s.id === editingStudent.value.id ? editingStudent.value : s);
    isEditModalOpen.value = false;
    editingStudent.value = null;
  }
};

const handlePrint = () => {
  window.print();
};

const filteredStudents = computed(() => {
  return students.value.filter(s => {
    const matchesSearch = s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                         s.id.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesClass = classFilter.value === 'All Classes' || s.class === classFilter.value;
    return matchesSearch && matchesClass;
  });
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

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <div class="relative max-w-xs w-full">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
            <input 
              type="text" 
              placeholder="Search students..." 
              v-model="searchQuery"
              class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" 
            />
          </div>
          <select v-model="classFilter" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/20 min-w-[140px]">
            <option>All Classes</option>
            <option>10A</option>
            <option>10B</option>
            <option>11A</option>
            <option>12B</option>
          </select>
        </div>
        
        <div class="flex items-center gap-2">
          <button 
            @click="handlePrint"
            class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 transition-all" 
            title="Print List"
          >
            <Printer class="size-4 text-slate-500" />
          </button>
          <div class="flex items-center bg-slate-100 p-1 rounded-lg border border-slate-200">
            <button 
              @click="viewMode = 'table'"
              :class="cn(
                'p-1.5 rounded-md transition-all',
                viewMode === 'table' ? 'bg-white shadow-sm text-primary' : 'text-slate-400 hover:text-slate-600'
              )"
            >
              <List class="size-4" />
            </button>
            <button 
              @click="viewMode = 'grid'"
              :class="cn(
                'p-1.5 rounded-md transition-all',
                viewMode === 'grid' ? 'bg-white shadow-sm text-primary' : 'text-slate-400 hover:text-slate-600'
              )"
            >
              <LayoutGrid class="size-4" />
            </button>
          </div>
        </div>
      </div>

      <div v-if="viewMode === 'table'">
        <table class="w-full text-left text-sm">
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
                    @click="editingStudent = { ...s }; isEditModalOpen = true"
                    class="p-2 text-slate-400 hover:text-primary hover:bg-primary/5 rounded-lg" 
                    title="Edit"
                  >
                    <Edit3 class="size-4" />
                  </button>
                  <button 
                    @click="students = students.filter(st => st.id !== s.id)"
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
      </div>
      <div v-else class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 bg-slate-50/30">
        <div v-for="(s, i) in filteredStudents" :key="i" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
          <div class="flex flex-col items-center text-center space-y-4">
            <div class="size-24 rounded-full border-4 border-slate-50 p-1 bg-white shadow-inner overflow-hidden">
              <img :src="s.photo || `https://picsum.photos/seed/${s.id}/200/200`" alt="" class="w-full h-full object-cover rounded-full" />
            </div>
            <div>
              <h4 class="font-bold text-slate-900">{{ s.name }}</h4>
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ s.id }}</p>
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

    <Modal :is-open="isAddModalOpen" @close="isAddModalOpen = false" title="Add New Student" size="lg">
      <div class="space-y-4">
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Full Name</label>
          <input 
            type="text" 
            v-model="newStudent.name"
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" 
            placeholder="e.g. Sat Vichet"
          />
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Class</label>
          <select 
            v-model="newStudent.class"
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
          >
            <option>10A</option>
            <option>10B</option>
            <option>11A</option>
            <option>12B</option>
          </select>
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Parent Contact</label>
          <input 
            type="text" 
            v-model="newStudent.parent"
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" 
          />
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button @click="isAddModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button 
            @click="handleAddStudent"
            class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg shadow-lg shadow-primary/20"
          >
            Save Student
          </button>
        </div>
      </div>
    </Modal>

    <Modal :is-open="isBulkModalOpen" @close="isBulkModalOpen = false" title="Bulk Import Students" size="md">
      <div class="space-y-4">
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Student Names (One per line)</label>
          <textarea 
            v-model="bulkData"
            class="w-full h-48 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20 font-mono"
            placeholder="John Doe&#10;Jane Smith&#10;Michael Brown"
          />
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button @click="isBulkModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button 
            @click="handleBulkAdd"
            class="px-6 py-2 text-sm font-bold text-white bg-primary rounded-lg shadow-lg shadow-primary/20"
          >
            Import Students
          </button>
        </div>
      </div>
    </Modal>

    <Modal :is-open="isPreviewModalOpen" @close="isPreviewModalOpen = false" title="Student ID Card" size="sm">
      <div class="space-y-6 flex flex-col items-center">
        <div class="text-center">
          <p class="text-sm font-bold text-slate-900">{{ selectedStudent?.name }}</p>
          <p class="text-[10px] text-slate-400 font-mono">{{ selectedStudent?.id }}</p>
        </div>
        <button class="w-full flex items-center justify-center gap-2 py-3 bg-slate-900 text-white rounded-xl font-bold text-sm shadow-xl hover:bg-slate-800 transition-all">
          <Printer class="size-4" />
          Print ID Card
        </button>
      </div>
    </Modal>

    <Modal :is-open="isEditModalOpen" @close="isEditModalOpen = false" title="Edit Student Information" size="lg">
      <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Full Name</label>
            <input 
              type="text" 
              v-model="editingStudent.name"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" 
            />
          </div>
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Student ID</label>
            <input type="text" class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm outline-none" :value="editingStudent?.id || ''" readonly />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Class</label>
            <select 
              v-model="editingStudent.class"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20"
            >
              <option>10A</option>
              <option>10B</option>
              <option>11A</option>
              <option>12B</option>
            </select>
          </div>
          <div class="space-y-1">
            <label class="text-[10px] font-bold text-slate-500 uppercase">Parent Contact</label>
            <input 
              type="text" 
              v-model="editingStudent.parent"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" 
            />
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button @click="isEditModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button 
            @click="handleEditStudent"
            class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg shadow-lg shadow-primary/20"
          >
            Update Student
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>
