import { ref } from "vue";
import axios from "axios";

const TOKEN_KEY = "attendx_token";
const USER_KEY = "attendx_user";

/* -----------------------------
   Reactive State
----------------------------- */
export const isLoggedIn = ref(false);
export const currentUser = ref<any>(null);

/* -----------------------------
   Token Functions
----------------------------- */
export const getToken = () => {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(TOKEN_KEY);
};

export const setToken = (token: string) => {
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

/* -----------------------------
   User Functions
----------------------------- */
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

export const setUser = (user: any) => {
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

/* -----------------------------
   Authentication API
----------------------------- */
export const login = async (email: string, password: string) => {
  const res = await axios.post("http://localhost:8000/api/auth/login", {
    email,
    password,
  });

  const data = res.data;

  setToken(data.token);
  setUser(data.user);

  return data;
};

export const register = async (
  name: string,
  email: string,
  password: string,
) => {
  const res = await axios.post("http://localhost:8000/api/auth/register", {
    name,
    email,
    password,
  });

  return res.data;
};

/* -----------------------------
   Logout
----------------------------- */
export const logout = () => {
  clearToken();
  clearUser();
};

/* -----------------------------
   Update Profile
----------------------------- */
export const updateProfile = (name: string, avatar: string) => {
  if (currentUser.value) {
    currentUser.value.name = name;
    currentUser.value.avatar = avatar;

    setUser(currentUser.value);
  }
};

/* -----------------------------
   Initialize State
----------------------------- */
isLoggedIn.value = Boolean(getToken());
currentUser.value = getUser();
