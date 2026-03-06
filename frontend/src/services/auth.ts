import { ref } from 'vue';
import { MOCK_STUDENT } from '../../types';

const TOKEN_KEY = 'attendx_token';

export const getToken = () => {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem(TOKEN_KEY);
};

export const setToken = (token) => {
  if (typeof window !== 'undefined') {
    localStorage.setItem(TOKEN_KEY, token);
  }
  isLoggedIn.value = true;
};

export const clearToken = () => {
  if (typeof window !== 'undefined') {
    localStorage.removeItem(TOKEN_KEY);
  }
  isLoggedIn.value = false;
};

export const isLoggedIn = ref(Boolean(getToken()));
export const studentProfile = ref({ ...MOCK_STUDENT });

export const logout = () => {
  clearToken();
  if (typeof window !== 'undefined') {
    localStorage.removeItem('user_data');
  }
};

export const updateProfile = (name, avatar) => {
  studentProfile.value.name = name;
  studentProfile.value.avatar = avatar;
};
