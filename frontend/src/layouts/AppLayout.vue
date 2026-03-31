<template>
  <div class="flex min-h-screen bg-slate-50">
    <Sidebar :current-module="currentModule" @module-change="setCurrentModule" />

    <main class="flex min-w-0 flex-1 flex-col overflow-hidden">
      <Navbar @navigate="setCurrentModule" />

      <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
        <div class="mx-auto w-full max-w-[1600px]">
          <KeepAlive>
            <component
              :is="activeModule"
              :key="currentModule"
              :classes-refresh-key="classesRefreshKey"
              @classes-updated="handleClassesUpdated"
            />
          </KeepAlive>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, watchEffect } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Sidebar from './Sidebar.vue';
import Navbar from './Navbar.vue';
import Dashboard from '../components/admin/Dashboard.vue';
import UserManagement from '../components/admin/UserManagement.vue';
import AcademicStructure from '../components/admin/AcademicStructure.vue';
import StudentManagement from '../components/admin/StudentManagement.vue';
import Profile from '../components/admin/Profile.vue';
import AbsenceManagement from '../components/admin/AbsenceManagement.vue';
import AttendanceControl from '../components/admin/AttendanceControl.vue';
import SystemSettings from '../components/admin/SystemSettings.vue';
import BiometricManagement from '../components/admin/BiometricManagement.vue';
import { getUserRole } from '../services/auth';

const route = useRoute();
const router = useRouter();
const classesRefreshKey = ref(0);
const userRole = computed(() => getUserRole());

const moduleMap = computed(() => {
  if (userRole.value === 'student') {
    return {
      profile: Profile,
    } as const;
  } else if (userRole.value === 'teacher') {
    return {
      dashboard: Dashboard,
      attendance: AttendanceControl,
      biometric: BiometricManagement,
      absences: AbsenceManagement,
    } as const;
  } else if (userRole.value === 'education') {
    return {
      dashboard: Dashboard,
      users: UserManagement,
      academic: AcademicStructure,
      students: StudentManagement,
      attendance: AttendanceControl,
      biometric: BiometricManagement,
      absences: AbsenceManagement,
      settings: SystemSettings,
      profile: Profile,
    } as const;
  } else {
    return {
      dashboard: Dashboard,
      users: UserManagement,
      academic: AcademicStructure,
      students: StudentManagement,
      attendance: AttendanceControl,
      biometric: BiometricManagement,
      absences: AbsenceManagement,
      settings: SystemSettings,
      profile: Profile,
    } as const;
  }
});

const defaultModule = computed(() => {
  if (userRole.value === 'student') return 'profile';
  return 'dashboard';
});

const moduleStorageKey = computed(
  () => `app_layout_current_module_${userRole.value || 'guest'}`,
);

const normalizeModule = (value: unknown): string | null => {
  const raw = Array.isArray(value) ? value[0] : value;

  if (typeof raw !== 'string') {
    return null;
  }

  return raw in moduleMap.value ? raw : null;
};

const getStoredModule = () => {
  if (typeof window === 'undefined') {
    return null;
  }

  return normalizeModule(window.localStorage.getItem(moduleStorageKey.value));
};

const currentModule = ref(
  normalizeModule(route.query.module) ?? getStoredModule() ?? defaultModule.value,
);

const activeModule = computed(
  () => moduleMap.value[currentModule.value as keyof typeof moduleMap.value] ?? Dashboard,
);

const persistCurrentModule = (module: string) => {
  if (!(module in moduleMap.value)) {
    return;
  }

  if (typeof window !== 'undefined') {
    window.localStorage.setItem(moduleStorageKey.value, module);
  }

  const routeModule = normalizeModule(route.query.module);
  if (routeModule !== module) {
    router.replace({
      path: route.path,
      query: {
        ...route.query,
        module,
      },
    });
  }
};

watchEffect(() => {
  if (!(currentModule.value in moduleMap.value)) {
    currentModule.value = getStoredModule() ?? defaultModule.value;
  }
});

watch(
  () => route.query.module,
  (moduleFromRoute) => {
    const normalizedModule = normalizeModule(moduleFromRoute);

    if (normalizedModule && normalizedModule !== currentModule.value) {
      currentModule.value = normalizedModule;
      return;
    }

    if (!normalizedModule) {
      persistCurrentModule(currentModule.value);
    }
  },
);

watch(
  currentModule,
  (module) => {
    persistCurrentModule(module);
  },
  { immediate: true },
);

const setCurrentModule = (module: string) => {
  if (module in moduleMap.value) {
    currentModule.value = module;
  }
};

const handleClassesUpdated = () => {
  classesRefreshKey.value += 1;
};
</script>

<style scoped>
.module-fade-enter-active,
.module-fade-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
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
