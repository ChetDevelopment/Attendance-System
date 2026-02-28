<template>
  <div class="flex h-screen overflow-hidden bg-background-light">
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
import Dashboard from '../components/Dashboard.vue';
import UserManagement from '../components/UserManagement.vue';
import AcademicStructure from '../components/AcademicStructure.vue';
import StudentManagement from '../components/StudentManagement.vue';
import AttendanceControl from '../components/AttendanceControl.vue';
import SystemSettings from '../components/SystemSettings.vue';
import Profile from '../components/Profile.vue';

const currentModule = ref('dashboard');

const moduleMap = {
  dashboard: Dashboard,
  users: UserManagement,
  academic: AcademicStructure,
  students: StudentManagement,
  attendance: AttendanceControl,
  settings: SystemSettings,
  profile: Profile,
} as const;

const activeModule = computed(
  () => moduleMap[currentModule.value as keyof typeof moduleMap] ?? Dashboard
);

const setCurrentModule = (module: string) => {
  currentModule.value = module;
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
