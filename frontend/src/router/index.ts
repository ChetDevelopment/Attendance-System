<<<<<<< HEAD:frontend/src/router/index.js
<<<<<<< HEAD
import { createRouter, createWebHistory } from 'vue-router'
=======
import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
>>>>>>> feature/login:frontend/src/router/index.ts
import { getToken } from '../services/auth'
import AppLayout from '../layouts/AppLayout.vue'
import LoginPage from '../pages/LoginPage.vue'
import RegisterPage from '../pages/RegisterPage.vue'
<<<<<<< HEAD:frontend/src/router/index.js
=======
import DashboardPage from '../pages/DashboardPage.vue'
import AttendancePage from '../pages/DashboardPage.vue'
>>>>>>> feature/login:frontend/src/router/index.ts

const routes: RouteRecordRaw[] = [
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
<<<<<<< HEAD:frontend/src/router/index.js
    meta: { requiresAuth: false },
=======
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/dashboard',
      },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: DashboardPage,
        meta: { title: 'Dashboard' },
      },
      {
        path: 'attendance',
        name: 'attendance',
        component: AttendancePage,
        meta: { title: 'Attendance' },
      },
    ],
>>>>>>> feature/login:frontend/src/router/index.ts
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
