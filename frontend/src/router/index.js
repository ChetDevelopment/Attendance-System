<<<<<<< HEAD
import { createRouter, createWebHistory } from 'vue-router'
import { getToken } from '../services/auth'
import AppLayout from '../layouts/AppLayout.vue'
import LoginPage from '../pages/LoginPage.vue'
import RegisterPage from '../pages/RegisterPage.vue'

const routes = [
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/login',
    name: 'login',
    component: LoginPage,
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterPage,
    meta: { guestOnly: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: AppLayout,
    meta: { requiresAuth: false },
  },
]
=======
import { createRouter, createWebHistory } from 'vue-router';
>>>>>>> feature/student-Dashboard

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: () => import('../layouts/AppLayout.vue'),
      children: [
        {
          path: '',
          redirect: '/dashboard'
        },
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('../components/Student/DashboardStudent.vue')
        },
        {
          path: 'attendance',
          name: 'attendance',
          component: () => import('../components/Student/AttendanceStudent.vue')
        },
        {
          path: 'history',
          name: 'history',
          component: () => import('../components/Student/HistoryStudent.vue')
        },
        {
          path: 'settings',
          name: 'settings',
          component: () => import('../components/Student/SettingsStudent.vue')
        }
      ]
    }
  ]
});

export default router;
