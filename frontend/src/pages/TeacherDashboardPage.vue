<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Sidebar, { ViewType, User } from '../components/Teacher/Sidebar.vue'
import Header from '../components/Teacher/Header.vue'
import TeacherDashboard from '../components/Teacher/TeacherDashboard.vue'
import ScheduleView from '../components/Teacher/ScheduleView.vue'
import AttendanceSessionView from '../components/Teacher/AttendanceSessionView.vue'
import HistoryView from '../components/Teacher/HistoryView.vue'
import StudentManagement from '../components/Teacher/StudentManagement.vue'
import StudentRecordsView from '../components/Teacher/StudentRecordsView.vue'
import MessagesView from '../components/Teacher/MessagesView.vue'
import SettingsView from '../components/Teacher/SettingsView.vue'
import NotificationsView from '../components/Teacher/NotificationsView.vue'
import api from '../services/api'
import { teacherService } from '../services/teacherService'
import { clearStudentSession, clearToken, clearUser, clearUserRole, getUser } from '../services/auth'

const router = useRouter()

const loggedUser = getUser()

const MOCK_USERS = ref<User[]>([
  {
    name: loggedUser?.name || 'Teacher',
    role: 'teacher',
    department: loggedUser?.department || 'Education',
    photo: loggedUser?.photo || 'https://picsum.photos/seed/teacher/200/200',
  },
])

const currentView = ref<ViewType>('dashboard')
const user = ref<User>(MOCK_USERS.value[0])

const handleViewChange = (view: ViewType) => {
  currentView.value = view
}

const handleUserChange = (newUser: User) => {
  user.value = newUser
}

const handleLogout = async () => {
  try {
    await api.post('/auth/logout')
  } catch {
    // Ignore API failures and proceed with local logout.
  } finally {
    clearToken()
    clearStudentSession()
    clearUser()
    clearUserRole()
    router.push({ name: 'login' })
  }
}

const loadTeachers = async () => {
  try {
    const data = await teacherService.getSchedule()
    const teachers = Array.isArray(data.teachers) ? data.teachers : []
    const mapped: User[] = teachers.map((t: any) => ({
      name: t.name,
      role: 'teacher',
      department: 'Education',
      photo: `https://picsum.photos/seed/teacher-${t.id}/200/200`,
    }))
    if (mapped.length > 0) {
      MOCK_USERS.value = mapped
      if (!mapped.find((m) => m.name === user.value.name)) {
        user.value = mapped[0]
      }
    }
  } catch {
    // keep currently loaded user
  }
}

loadTeachers()
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
      <Header :user="user" @navigate="handleViewChange" @logout="handleLogout" />

      <div class="flex-1 overflow-y-auto p-8">
        <Transition name="fade" mode="out-in">
          <div :key="currentView">
            <TeacherDashboard
              v-if="currentView === 'dashboard'"
              :user="user"
              @navigate="handleViewChange"
            />
            <ScheduleView
              v-else-if="currentView === 'schedule'"
              :user="user"
            />
            <AttendanceSessionView
              v-else-if="currentView === 'attendance'"
            />
            <HistoryView
              v-else-if="currentView === 'history'"
            />
            <StudentManagement
              v-else-if="currentView === 'management'"
            />
            <StudentRecordsView
              v-else-if="currentView === 'students'"
            />
            <MessagesView
              v-else-if="currentView === 'messages'"
              :user="user"
            />
            <SettingsView
              v-else-if="currentView === 'settings'"
            />
            <NotificationsView
              v-else-if="currentView === 'notifications'"
            />
            <div v-else class="flex items-center justify-center h-[60vh] text-slate-400">
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
  transition: opacity 0.2s ease, transform 0.2s ease;
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
