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
          component: () => import('../pages/DashboardStudent.vue')
        },
        {
          path: 'attendance',
          name: 'attendance',
          component: () => import('../pages/AttendanceStudent.vue')
        },
        {
          path: 'history',
          name: 'history',
          component: () => import('../pages/HistoryStudent.vue')
        },
        {
          path: 'settings',
          name: 'settings',
          component: () => import('../pages/SettingsStudent.vue')
        }
      ]
    }
  ]
});

export default router;
