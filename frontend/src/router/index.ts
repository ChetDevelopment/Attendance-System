import { createRouter, createWebHistory } from 'vue-router'
import LoginPage from '../pages/LoginPage.vue'
import RegisterPage from '../pages/RegisterPage.vue'
import AppLayout from '../layouts/AppLayout.vue'
import DashboardStudent from '../components/Student/DashboardStudent.vue'
import AttendancePage from '../components/Student/AttendancePage.vue'
import HistoryStudent from '../components/Student/HistoryStudent.vue'
import SettingsStudent from '../components/Student/SettingsStudent.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/dashboard' },
    { path: '/login', name: 'login', component: LoginPage },
    { path: '/register', name: 'register', component: RegisterPage },
    {
      path: '/',
      component: AppLayout,
      children: [
        { path: 'dashboard', name: 'dashboard', component: DashboardStudent },
        { path: 'attendance', name: 'attendance', component: AttendancePage },
        { path: 'history', name: 'history', component: HistoryStudent },
        { path: 'settings', name: 'settings', component: SettingsStudent },
      ],
    },
  ],
})

export default router
