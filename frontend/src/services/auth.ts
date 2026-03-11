import { ref } from "vue";

const TOKEN_KEY = "attendx_token";
const USER_KEY = "attendx_user";

export const getToken = () => {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(TOKEN_KEY);
};

export const setToken = (token) => {
  if (typeof window !== "undefined") {
    localStorage.setItem(TOKEN_KEY, token);
  }
  isLoggedIn.value = true;
};

export const clearToken = () => {
  if (typeof window !== "undefined") {
    localStorage.removeItem(TOKEN_KEY);
  }
  isLoggedIn.value = false;
};

export const getUser = () => {
  if (typeof window === "undefined") return null;
  const userStr = localStorage.getItem(USER_KEY);
  if (userStr) {
    try {
      return JSON.parse(userStr);
    } catch {
      return null;
    }
  }
  return null;
};

export const setUser = (user) => {
  if (typeof window !== "undefined") {
    localStorage.setItem(USER_KEY, JSON.stringify(user));
  }
  currentUser.value = user;
};

export const clearUser = () => {
  if (typeof window !== "undefined") {
    localStorage.removeItem(USER_KEY);
  }
  currentUser.value = null;
};

export const isLoggedIn = ref(Boolean(getToken()));
export const currentUser = ref(getUser());

export const logout = () => {
  clearToken();
  clearUser();
};

export const updateProfile = (name, avatar) => {
  if (currentUser.value) {
    currentUser.value.name = name;
    currentUser.value.avatar = avatar;
    setUser(currentUser.value);
  }
};
