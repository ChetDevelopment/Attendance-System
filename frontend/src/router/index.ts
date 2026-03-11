import { createRouter, createWebHistory } from "vue-router";
import { getToken } from "../services/auth";
import AppLayout from "../layouts/AppLayout.vue";
import LoginPage from "../pages/LoginPage.vue";
import RegisterPage from "../pages/RegisterPage.vue";

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
    name: "dashboard",
    component: AppLayout,
    meta: { requiresAuth: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

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
