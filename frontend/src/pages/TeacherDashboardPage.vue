<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import Sidebar, { ViewType, User } from "../components/Teacher/Sidebar.vue";
import Header from "../components/Teacher/Header.vue";
import TeacherDashboard from "../components/Teacher/TeacherDashboard.vue";
import ScheduleView from "../components/Teacher/ScheduleView.vue";
import AttendanceSessionView from "../components/Teacher/AttendanceSessionView.vue";
import HistoryView from "../components/Teacher/HistoryView.vue";
import StudentManagement from "../components/Teacher/StudentManagement.vue";
import StudentRecordsView from "../components/Teacher/StudentRecordsView.vue";
import MessagesView from "../components/Teacher/MessagesView.vue";
import SettingsView from "../components/Teacher/SettingsView.vue";
import NotificationsView from "../components/Teacher/NotificationsView.vue";
import api from "../services/api";
import { teacherService } from "../services/teacherService";
import { profileService } from "../services/profileService";
import {
  clearStudentSession,
  clearToken,
  clearUser,
  clearUserRole,
  getUser,
  setUser,
} from "../services/auth";

const router = useRouter();
const route = useRoute();
const DEFAULT_TEACHER_PHOTO = "/default-teacher.svg";
const TEACHER_VIEW_STORAGE_KEY = "teacher_dashboard_current_view";
const VALID_VIEWS: ViewType[] = [
  "dashboard",
  "schedule",
  "attendance",
  "history",
  "students",
  "messages",
  "management",
  "settings",
  "notifications",
];

const normalizeView = (value: unknown): ViewType | null => {
  const raw = Array.isArray(value) ? value[0] : value;

  if (typeof raw !== "string") {
    return null;
  }

  return VALID_VIEWS.includes(raw as ViewType) ? (raw as ViewType) : null;
};

const getStoredView = () => {
  if (typeof window === "undefined") {
    return null;
  }

  return normalizeView(window.localStorage.getItem(TEACHER_VIEW_STORAGE_KEY));
};

// Fetch fresh user profile from backend
const fetchAndUpdateUserProfile = async () => {
  try {
    const profile = await profileService.getProfile();
    if (profile) {
      // Update local storage with fresh data
      setUser(profile);
      return profile;
    }
  } catch (e) {
    console.error("Failed to fetch user profile:", e);
  }
  return getUser();
};

// Initialize with stored user (will be refreshed on mount)
const initialUser = getUser();

// Get the proper fields from the backend user response
// Backend returns: name, email, role, profile_image, calendar_id
// We need to map profile_image to photo for the frontend interface
const getUserPhoto = (user: any) => {
  // First check for profile_image (backend field)
  if (user?.profile_image) {
    return user.profile_image;
  }
  // Fallback to photo if exists
  if (user?.photo) {
    return user.photo;
  }
  // Try to get image from teacherFaces folder based on name
  if (user?.name) {
    const nameLower = user.name.toLowerCase();
    return `/teacherFaces/${nameLower}.jpg`;
  }
  // Default image
  return DEFAULT_TEACHER_PHOTO;
};

// Only show the current logged-in user, not all teachers
const currentUserData = ref<any>(initialUser);

// Update user data after fetching fresh profile
const updateCurrentUser = (profileData: any) => {
  if (profileData) {
    currentUserData.value = profileData;
  }
};

const MOCK_USERS = ref<User[]>([
  {
    name: currentUserData.value?.name || "Teacher",
    role: "teacher",
    department:
      currentUserData.value?.department ||
      currentUserData.value?.role?.name ||
      "Teacher",
    photo: getUserPhoto(currentUserData.value),
  },
]);

const currentView = ref<ViewType>(
  normalizeView(route.query.view) ?? getStoredView() ?? "dashboard",
);

// Computed user that updates when MOCK_USERS changes
const selectedUserIndex = ref(0);

const user = computed<User>(() => {
  const users = MOCK_USERS.value;
  if (users && users.length > 0) {
    return users[selectedUserIndex.value] || users[0];
  }
  return {
    name: "Teacher",
    role: "teacher" as const,
    department: "Teacher",
    photo: DEFAULT_TEACHER_PHOTO,
  };
});

// Academic year state
const academicYears = ref<any[]>([]);
const selectedAcademicYearId = ref<number | null>(null);

const loadAcademicYears = async () => {
  try {
    const years = await teacherService.getAcademicYears();
    academicYears.value = years;
    // Preserve the current selection when it still exists; otherwise default to active year or first.
    const currentSelectionStillExists = years.some(
      (year: any) => Number(year.id) === Number(selectedAcademicYearId.value),
    );
    const activeYear = years.find((y: any) => y.is_active);
    selectedAcademicYearId.value = currentSelectionStillExists
      ? selectedAcademicYearId.value
      : activeYear?.id || years[0]?.id || null;
  } catch (error) {
    console.error("Failed to load academic years:", error);
  }
};

const persistCurrentView = (view: ViewType) => {
  if (typeof window !== "undefined") {
    window.localStorage.setItem(TEACHER_VIEW_STORAGE_KEY, view);
  }

  const routeView = normalizeView(route.query.view);
  if (routeView !== view) {
    router.replace({
      name: "teacher-dashboard",
      query: {
        ...route.query,
        view,
      },
    });
  }
};

onMounted(async () => {
  // Fetch fresh user profile from backend
  const freshProfile = await fetchAndUpdateUserProfile();
  if (freshProfile) {
    updateCurrentUser(freshProfile);
    // Update MOCK_USERS with fresh data
    MOCK_USERS.value = [
      {
        name: freshProfile.name || "Teacher",
        role: "teacher",
        department: freshProfile.department || freshProfile.role?.name || "Teacher",
        photo: getUserPhoto(freshProfile),
      },
    ];
  }
  loadAcademicYears();
  persistCurrentView(currentView.value);
});

const handleViewChange = (view: ViewType) => {
  currentView.value = view;
};

watch(
  () => route.query.view,
  (viewFromRoute) => {
    const normalizedView = normalizeView(viewFromRoute);

    if (normalizedView && normalizedView !== currentView.value) {
      currentView.value = normalizedView;
      return;
    }

    if (!normalizedView) {
      persistCurrentView(currentView.value);
    }
  },
);

watch(currentView, (view) => {
  persistCurrentView(view);
});

const handleUserChange = (newUser: User) => {
  // Find the index of the selected user and update
  const idx = MOCK_USERS.value.findIndex((u: User) => u.name === newUser.name);
  if (idx >= 0) {
    selectedUserIndex.value = idx;
  }
};

const handleLogout = async () => {
  try {
    await api.post("/auth/logout", {});
  } catch {
    // Ignore API failures and proceed with local logout.
  } finally {
    clearToken();
    clearStudentSession();
    clearUser();
    clearUserRole();
    router.push({ name: "login" });
  }
};
</script>

<template>
  <div class="flex min-h-screen bg-slate-50">
    <Sidebar
      :currentView="currentView"
      @viewChange="handleViewChange"
      :user="user"
      :mockUsers="MOCK_USERS"
      @userChange="handleUserChange"
      @logout="handleLogout"
    />

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <Header
        :user="user"
        @navigate="handleViewChange"
        @logout="handleLogout"
      />

      <div class="flex-1 overflow-y-auto p-8">
        <Transition name="fade" mode="out-in">
          <div :key="currentView">
            <TeacherDashboard
              v-if="currentView === 'dashboard'"
              :user="user"
              @navigate="handleViewChange"
            />
            <ScheduleView v-else-if="currentView === 'schedule'" :user="user" />
            <AttendanceSessionView
              v-else-if="currentView === 'attendance'"
              :academicYearId="selectedAcademicYearId"
              :academicYearOptions="academicYears"
              @update:academicYearId="selectedAcademicYearId = $event"
            />
            <HistoryView v-else-if="currentView === 'history'" />
            <StudentManagement
              v-else-if="currentView === 'management'"
              :academicYearId="selectedAcademicYearId"
              :academicYearOptions="academicYears"
              @update:academicYearId="selectedAcademicYearId = $event"
            />
            <StudentRecordsView v-else-if="currentView === 'students'" />
            <MessagesView v-else-if="currentView === 'messages'" :user="user" />
            <SettingsView v-else-if="currentView === 'settings'" />
            <NotificationsView v-else-if="currentView === 'notifications'" />
            <div
              v-else
              class="flex items-center justify-center h-[60vh] text-slate-400"
            >
              <p class="text-lg font-medium">This feature is coming soon...</p>
            </div>
          </div>
        </Transition>
      </div>
    </main>
  </div>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
