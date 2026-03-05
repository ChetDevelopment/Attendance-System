<script setup lang="ts">
import { ref, computed } from 'vue';
import Modal from './Modal.vue';
import { Calendar, Layers, Users, ArrowUpCircle, Plus, Search } from 'lucide-vue-next';

const isClassModalOpen = ref(false);
const isPromoteModalOpen = ref(false);
const isSessionModalOpen = ref(false);
const isAllClassesModalOpen = ref(false);
const isAssignModalOpen = ref(false);
const isEditClassModalOpen = ref(false);
const selectedClass = ref<any>(null);
const classSearchQuery = ref('');

const classes = [
  { name: 'Class 10A', teacher: 'Dr. Smith', count: 32, room: '302' },
  { name: 'Class 10B', teacher: 'Ms. Johnson', count: 30, room: '305' },
  { name: 'Class 11A', teacher: 'Mr. Brown', count: 28, room: '401' },
  { name: 'Class 11B', teacher: 'Dr. White', count: 25, room: '402' },
  { name: 'Class 12A', teacher: 'Ms. Davis', count: 35, room: '501' },
];

const filteredClasses = computed(() => 
  classes.filter(c => 
    c.name.toLowerCase().includes(classSearchQuery.value.toLowerCase()) || 
    c.teacher.toLowerCase().includes(classSearchQuery.value.toLowerCase())
  )
);

const handleEditClass = (cls: any) => {
  selectedClass.value = cls;
  isEditClassModalOpen.value = true;
};
</script>

<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Academic Structure</h2>
        <p class="text-sm text-slate-500 font-medium">Manage years, classes, and student promotions</p>
      </div>
      <div class="flex gap-3">
        <button 
          @click="isPromoteModalOpen = true"
          class="flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg font-bold text-sm shadow-lg shadow-amber-500/20 hover:bg-amber-600 transition-all"
        >
          <ArrowUpCircle class="size-4" />
          Promote Students
        </button>
        <button 
          @click="isClassModalOpen = true"
          class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-bold text-sm shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all"
        >
          <Plus class="size-4" />
          New Class
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Academic Year Card -->
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center gap-3">
          <div class="size-10 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600">
            <Calendar class="size-5" />
          </div>
          <div>
            <h4 class="font-bold text-slate-900">Academic Year</h4>
            <p class="text-[10px] text-slate-500 uppercase font-bold">Session 2023-2024</p>
          </div>
        </div>
        <div class="p-3 bg-slate-50 rounded-lg border border-slate-100">
          <div class="flex justify-between items-center mb-2">
            <span class="text-xs font-medium text-slate-600">Current Term</span>
            <span class="px-2 py-0.5 bg-green-100 text-green-600 text-[9px] font-black rounded uppercase">Active</span>
          </div>
          <p class="text-sm font-bold text-slate-900">Term 2 (Spring)</p>
        </div>
        <button 
          @click="isSessionModalOpen = true"
          class="w-full py-2 text-xs font-bold text-primary hover:bg-primary/5 rounded-lg transition-colors"
        >
          Manage Sessions
        </button>
      </div>

      <!-- Class Manager Card -->
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center gap-3">
          <div class="size-10 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600">
            <Layers class="size-5" />
          </div>
          <div>
            <h4 class="font-bold text-slate-900">Class Manager</h4>
            <p class="text-[10px] text-slate-500 uppercase font-bold">{{ classes.length }} Active Classes</p>
          </div>
        </div>
        <div class="flex -space-x-2">
          <div v-for="(c, i) in classes.slice(0, 4)" :key="i" class="size-8 rounded-full bg-slate-200 border-2 border-white flex items-center justify-center text-[10px] font-bold text-slate-600">
            {{ c.name.split(' ')[1] }}
          </div>
          <div v-if="classes.length > 4" class="size-8 rounded-full bg-slate-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-slate-400">
            +{{ classes.length - 4 }}
          </div>
        </div>
        <button 
          @click="isAllClassesModalOpen = true"
          class="w-full py-2 text-xs font-bold text-primary hover:bg-primary/5 rounded-lg transition-colors"
        >
          View All Classes
        </button>
      </div>

      <!-- Student Assignment Card -->
      <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center gap-3">
          <div class="size-10 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600">
            <Users class="size-5" />
          </div>
          <div>
            <h4 class="font-bold text-slate-900">Assignments</h4>
            <p class="text-[10px] text-slate-500 uppercase font-bold">1,240 Students</p>
          </div>
        </div>
        <p class="text-xs text-slate-500">12 students currently unassigned to any class for the current session.</p>
        <button 
          @click="isAssignModalOpen = true"
          class="w-full py-2 text-xs font-bold text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
        >
          Assign Students
        </button>
      </div>
    </div>

    <!-- Class List Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-900">Class Overview</h3>
        <div class="relative max-w-xs w-full">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 size-4" />
          <input 
            type="text" 
            placeholder="Search classes..." 
            v-model="classSearchQuery"
            class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" 
          />
        </div>
      </div>
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold">
          <tr>
            <th class="px-6 py-4">Class Name</th>
            <th class="px-6 py-4">Teacher</th>
            <th class="px-6 py-4">Students</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="(c, i) in filteredClasses" :key="i" class="hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4 font-bold text-slate-900">{{ c.name }}</td>
            <td class="px-6 py-4 text-slate-600">{{ c.teacher }}</td>
            <td class="px-6 py-4 font-mono">{{ c.count }}</td>
            <td class="px-6 py-4 text-right">
              <button 
                @click="handleEditClass(c)"
                class="text-xs font-bold text-primary hover:underline"
              >
                Edit
              </button>
            </td>
          </tr>
          <tr v-if="filteredClasses.length === 0">
            <td colSpan="4" class="px-6 py-10 text-center text-slate-400 italic">No classes found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- New Class Modal -->
    <Modal :is-open="isClassModalOpen" @close="isClassModalOpen = false" title="Create New Class">
      <div class="space-y-4">
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Class Name</label>
          <input type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" placeholder="e.g. 10A" />
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Assigned Teacher</label>
          <select class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20">
            <option>Dr. Smith</option>
            <option>Ms. Johnson</option>
            <option>Mr. Brown</option>
          </select>
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Room Number</label>
          <input type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" placeholder="e.g. 302" />
        </div>
        <div class="pt-4 flex justify-end gap-3">
          <button @click="isClassModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg">Create Class</button>
        </div>
      </div>
    </Modal>

    <!-- More modals... -->
    <Modal :is-open="isEditClassModalOpen" @close="isEditClassModalOpen = false" :title="`Edit ${selectedClass?.name}`">
      <div class="space-y-4">
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Class Name</label>
          <input 
            type="text" 
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" 
            :value="selectedClass?.name" 
          />
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Assigned Teacher</label>
          <select class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" :value="selectedClass?.teacher">
            <option>Dr. Smith</option>
            <option>Ms. Johnson</option>
            <option>Mr. Brown</option>
            <option>Dr. White</option>
            <option>Ms. Davis</option>
          </select>
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Room Number</label>
          <input 
            type="text" 
            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20" 
            :value="selectedClass?.room" 
          />
        </div>
        <div class="pt-4 flex justify-end gap-3">
          <button @click="isEditClassModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg">Save Changes</button>
        </div>
      </div>
    </Modal>

    <Modal :is-open="isSessionModalOpen" @close="isSessionModalOpen = false" title="Manage Academic Sessions">
      <div class="space-y-4">
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-sm font-bold text-slate-900">Term 2 (Spring)</span>
            <span class="px-2 py-0.5 bg-green-100 text-green-600 text-[10px] font-black rounded uppercase">Current</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-slate-600">Term 1 (Fall)</span>
            <span class="px-2 py-0.5 bg-slate-200 text-slate-500 text-[10px] font-black rounded uppercase">Closed</span>
          </div>
        </div>
        <button class="w-full py-2 bg-primary text-white rounded-lg font-bold text-sm">Start New Session</button>
      </div>
    </Modal>

    <Modal :is-open="isAllClassesModalOpen" @close="isAllClassesModalOpen = false" title="All Classes" size="lg">
      <div class="space-y-4">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
          <div v-for="(c, i) in classes" :key="i" class="p-4 bg-slate-50 border border-slate-200 rounded-xl hover:border-primary transition-colors cursor-pointer">
            <div class="font-bold text-slate-900">{{ c.name }}</div>
            <div class="text-[10px] text-slate-500 uppercase font-bold">{{ c.teacher }}</div>
            <div class="mt-2 text-xs text-slate-400">{{ c.count }} Students</div>
          </div>
        </div>
      </div>
    </Modal>

    <Modal :is-open="isAssignModalOpen" @close="isAssignModalOpen = false" title="Assign Students to Classes" size="lg">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">Select unassigned students and assign them to a class.</p>
        <div class="max-h-60 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100">
          <div v-for="(s, i) in [{ name: 'Alice Wonder', id: 'S-2001' }, { name: 'Bob Builder', id: 'S-2002' }, { name: 'Charlie Brown', id: 'S-2003' }]" :key="i" class="p-3 flex items-center justify-between hover:bg-slate-50">
            <div>
              <div class="text-sm font-bold text-slate-900">{{ s.name }}</div>
              <div class="text-[10px] text-slate-400 font-mono">{{ s.id }}</div>
            </div>
            <select class="text-xs border border-slate-200 rounded px-2 py-1 outline-none">
              <option>Select Class</option>
              <option v-for="(c, j) in classes" :key="j">{{ c.name }}</option>
            </select>
          </div>
        </div>
        <div class="pt-4 flex justify-end gap-3">
          <button @click="isAssignModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg">Confirm Assignments</button>
        </div>
      </div>
    </Modal>

    <Modal :is-open="isPromoteModalOpen" @close="isPromoteModalOpen = false" title="Promote Students to Next Year">
      <div class="space-y-4">
        <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl text-amber-800 text-xs">
          <p class="font-bold mb-1">Warning: Irreversible Action</p>
          <p>This will move all students in the selected classes to their next grade level for the 2024-2025 session.</p>
        </div>
        <div class="space-y-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Source Grade</label>
          <select class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/20">
            <option>Grade 10</option>
            <option>Grade 11</option>
            <option>Grade 12</option>
          </select>
        </div>
        <div class="pt-4 flex justify-end gap-3">
          <button @click="isPromoteModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button class="px-4 py-2 text-sm font-bold text-white bg-amber-500 rounded-lg">Confirm Promotion</button>
        </div>
      </div>
    </Modal>
  </div>
</template>
