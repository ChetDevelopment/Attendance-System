import { createRouter, createWebHistory } from "vue-router";
import { getToken } from "../services/auth";

import AppLayout from "../layouts/AppLayout.vue";
import LoginPage from "../pages/LoginPage.vue";
import RegisterPage from "../pages/RegisterPage.vue";

// Admin module pages
import DashboardPage from "../components/admin/Dashboard.vue";
import UserManagement from "../components/admin/UserManagement.vue";
import AttendanceControl from "../components/admin/AttendanceControl.vue";
import Profile from "../components/admin/Profile.vue";

const routes = [
  {
    path: "/",
    redirect: "/login",
  },
  {
    path: "/login",
    name: "login",
    component: LoginPage,
    meta: { guestOnly: true },
  },
  {
    path: "/register",
    name: "register",
    component: RegisterPage,
    meta: { guestOnly: true },
  },
  {
    path: "/dashboard",
    component: AppLayout,
    meta: { requiresAuth: true },
    children: [
      { path: "", name: "dashboard", component: DashboardPage },
      { path: "users", name: "users", component: UserManagement },
      { path: "attendance", name: "attendance", component: AttendanceControl },
      { path: "profile", name: "profile", component: Profile },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation guard
router.beforeEach((to, _from, next) => {
  const token = getToken();

  if (to.meta?.requiresAuth && !token) {
    next({ name: "login" });
    return;
  }

  if (to.meta?.guestOnly && token) {
    next({ name: "dashboard" });
    return;
  }

  next();
});

export default router;