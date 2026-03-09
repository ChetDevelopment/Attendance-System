import { createRouter, createWebHistory } from 'vue-router'
import { getUserRole, hasSession } from '../services/auth'
import AppLayout from '../layouts/AppLayout.vue'
import LoginPage from '../pages/LoginPage.vue'
import TeacherDashboardPage from '../pages/TeacherDashboardPage.vue'
import EducationDashboardPage from '../pages/EducationDashboardPage.vue'
import StudentDashboardPage from '../pages/StudentDashboardPage.vue'
import ReportsPage from '../pages/ReportsPage.vue'

const routes = [
  {
    path: '/',
    redirect: () => {
      const role = getUserRole()
      if (role === 'teacher') return '/teacher/dashboard'
      if (role === 'education') return '/education/dashboard'
      if (role === 'student') return '/student/dashboard'
      if (role === 'admin') return '/dashboard'
      return '/dashboard'
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
    meta: { requiresAuth: true },
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

  if (to.meta.requiresAuth && !loggedIn) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && loggedIn) {
    if (role === 'teacher') return { name: 'teacher-dashboard' }
    if (role === 'education') return { name: 'education-dashboard' }
    if (role === 'student') return { name: 'student-dashboard' }
    return { name: 'dashboard' }
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

  if (to.name === 'teacher-dashboard' && role && role !== 'teacher') {
    return { name: 'dashboard' }
  }

  if (to.name === 'education-dashboard' && role && role !== 'education') {
    return { name: 'dashboard' }
  }

  if (to.name === 'student-dashboard' && role && role !== 'student') {
    return { name: 'dashboard' }
  }

  return true
})

export default router
