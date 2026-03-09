<template>
  <div class="flex h-screen overflow-hidden bg-slate-50">
    <Sidebar :current-module="currentModule" @module-change="setCurrentModule" />

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <Navbar @navigate="setCurrentModule" />

      <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
        <Transition name="module-fade" mode="out-in">
          <component :is="activeModule" :key="currentModule" />
        </Transition>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import Sidebar from './Sidebar.vue';
import Navbar from './Navbar.vue';
import Dashboard from '../components/Admin/Dashboard.vue';
import UserManagement from '../components/Admin/UserManagement.vue';
import AcademicStructure from '../components/Admin/AcademicStructure.vue';
import StudentManagement from '../components/Admin/StudentManagement.vue';
import Profile from '../components/Admin/Profile.vue';
import AbsenceManagement from '../components/Admin/AbsenceManagement.vue';
import AttendanceControl from '../components/Admin/AttendanceControl.vue';
import SystemSettings from '../components/Admin/SystemSettings.vue';
import { getUserRole } from '../services/auth';

const currentModule = ref('dashboard');
const userRole = computed(() => getUserRole());

const moduleMap = computed(() => {
  if (userRole.value === 'student') {
    return {
      dashboard: Dashboard,
      absences: AbsenceManagement,
    } as const;
  } else if (userRole.value === 'teacher') {
    return {
      dashboard: Dashboard,
      attendance: AttendanceControl,
      absences: AbsenceManagement,
    } as const;
  } else if (userRole.value === 'education') {
    return {
      dashboard: Dashboard,
      users: UserManagement,
      academic: AcademicStructure,
      students: StudentManagement,
      attendance: AttendanceControl,
      absences: AbsenceManagement,
      settings: SystemSettings,
      profile: Profile,
    } as const;
  } else {
    // Admin role
    return {
      dashboard: Dashboard,
      users: UserManagement,
      academic: AcademicStructure,
      students: StudentManagement,
      attendance: AttendanceControl,
      absences: AbsenceManagement,
      settings: SystemSettings,
      profile: Profile,
    } as const;
  }
});

const activeModule = computed(
  () => moduleMap.value[currentModule.value as keyof typeof moduleMap.value] ?? Dashboard
);

const setCurrentModule = (module: string) => {
  // Check if user has access to this module
  if (module in moduleMap.value) {
    currentModule.value = module;
  }
};
</script>

<style scoped>
.module-fade-enter-active,
.module-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.module-fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.module-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
