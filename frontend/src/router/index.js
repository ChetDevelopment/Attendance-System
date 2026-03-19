import { createRouter, createWebHistory } from 'vue-router'
import { getUserRole, hasSession } from '../services/auth'

const AppLayout = () => import('../layouts/AppLayout.vue')
const LoginPage = () => import('../pages/LoginPage.vue')
const TeacherDashboardPage = () => import('../pages/TeacherDashboardPage.vue')
const EducationDashboardPage = () => import('../pages/EducationDashboardPage.vue')
const StudentDashboardPage = () => import('../pages/StudentDashboardPage.vue')
const StudentAttendancePage = () => import('../pages/StudentAttendancePage.vue')
const StudentBiometricScanPage = () => import('../pages/StudentBiometricScanPage.vue')
const StudentHistoryPage = () => import('../pages/StudentHistoryPage.vue')
const StudentSettingsPage = () => import('../pages/StudentSettingsPage.vue')
const ReportsPage = () => import('../pages/ReportsPage.vue')

const routes = [
  {
    path: '/',
    redirect: () => {
      const role = getUserRole()
      if (role === 'teacher') return '/teacher/dashboard'
      if (role === 'education') return '/education/dashboard'
      if (role === 'student') return '/student/dashboard'
      if (role === 'admin') return '/admin/dashboard'
      return '/login'
    },
  },
  {
    path: '/login',
    name: 'login',
    component: LoginPage,
    meta: { guestOnly: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: AppLayout,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    meta: { requiresAuth: true },
    redirect: { name: 'dashboard' },
  },
  {
    path: '/teacher/dashboard',
    name: 'teacher-dashboard',
    component: TeacherDashboardPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/education/dashboard',
    name: 'education-dashboard',
    component: EducationDashboardPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/student/dashboard',
    name: 'student-dashboard',
    component: StudentDashboardPage,
    meta: { requiresAuth: true, layout: 'StudentLayout', keepAlive: true },
  },
  {
    path: '/student/attendance',
    name: 'student-attendance',
    component: StudentAttendancePage,
    meta: { requiresAuth: true, layout: 'StudentLayout', keepAlive: true },
  },
  {
    path: '/student/biometric-scan',
    name: 'student-biometric-scan',
    component: StudentBiometricScanPage,
    meta: { requiresAuth: true, layout: 'StudentLayout', keepAlive: true },
  },
  {
    path: '/student/history',
    name: 'student-history',
    component: StudentHistoryPage,
    meta: { requiresAuth: true, layout: 'StudentLayout', keepAlive: true },
  },
  {
    path: '/student/settings',
    name: 'student-settings',
    component: StudentSettingsPage,
    meta: { requiresAuth: true, layout: 'StudentLayout', keepAlive: true },
  },
  {
    path: '/reports',
    name: 'reports',
    component: ReportsPage,
    meta: { requiresAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const loggedIn = hasSession()
  const role = getUserRole()

  // If accessing login page and already logged in, redirect to role-based dashboard
  if (to.name === 'login' && loggedIn) {
    if (role === 'teacher') return { name: 'teacher-dashboard' }
    if (role === 'education') return { name: 'education-dashboard' }
    if (role === 'student') return { name: 'student-dashboard' }
    if (role === 'admin') return { name: 'dashboard' }
  }

  // If accessing protected route without auth, go to login
  if (to.meta.requiresAuth && !loggedIn) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && loggedIn) {
    if (role === 'teacher') return { name: 'teacher-dashboard' }
    if (role === 'education') return { name: 'education-dashboard' }
    if (role === 'student') return { name: 'student-dashboard' }
    if (role === 'admin') return { name: 'dashboard' }
    return { name: 'login' }
  }

  if (to.name === 'dashboard' && role === 'teacher') {
    return { name: 'teacher-dashboard' }
  }

  if (to.name === 'dashboard' && role === 'education') {
    return { name: 'education-dashboard' }
  }

  if (to.name === 'dashboard' && role === 'student') {
    return { name: 'student-dashboard' }
  }

  if (to.name === 'dashboard' && role === 'admin') {
    return true
  }

  if (to.name === 'teacher-dashboard' && role && role !== 'teacher') {
    return { name: 'login' }
  }

  if (to.name === 'education-dashboard' && role && role !== 'education') {
    return { name: 'login' }
  }

  if (
    ['student-dashboard', 'student-attendance', 'student-biometric-scan', 'student-history', 'student-settings'].includes(to.name)
    && role
    && role !== 'student'
  ) {
    return { name: 'login' }
  }

  if (to.name === 'admin-dashboard' && role && role !== 'admin') {
    return { name: 'login' }
  }

  return true
})

export default router
