<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
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
import {
  clearStudentSession,
  clearToken,
  clearUser,
  clearUserRole,
  getUser,
} from "../services/auth";

const router = useRouter();

// Initialize with stored user
// The user data is set during login and stored in localStorage
const loggedUser = getUser();

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
    return `/teacherFaces/${nameLower}.png`;
  }
  // Default image
  return "https://www.shutterstock.com/image-vector/user-profile-icon-vector-avatar-600nw-2558760599.jpg";
};

// Only show the current logged-in user, not all teachers
const MOCK_USERS = ref<User[]>([
  {
    name: loggedUser?.name || "Teacher",
    role: "teacher",
    department: loggedUser?.department || loggedUser?.role?.name || "Teacher",
    photo: getUserPhoto(loggedUser),
  },
]);

const currentView = ref<ViewType>("dashboard");
const user = ref<User>(MOCK_USERS.value[0]);

// Academic year state
const academicYears = ref<any[]>([]);
const selectedAcademicYearId = ref<number | null>(null);

const loadAcademicYears = async () => {
  try {
    const years = await teacherService.getAcademicYears();
    academicYears.value = years;
    // Set default to active year or first one
    const activeYear = years.find((y: any) => y.is_active);
    selectedAcademicYearId.value = activeYear?.id || years[0]?.id || null;
  } catch (error) {
    console.error("Failed to load academic years:", error);
  }
};

onMounted(() => {
  loadAcademicYears();
});

const handleViewChange = (view: ViewType) => {
  currentView.value = view;
};

const handleUserChange = (newUser: User) => {
  user.value = newUser;
};

const handleLogout = async () => {
  try {
    await api.post("/auth/logout");
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
