import { createRouter, createWebHistory } from 'vue-router';

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
