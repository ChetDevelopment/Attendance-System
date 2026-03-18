import axios from 'axios'
import { clearToken, clearUser, clearUserRole, getToken } from './auth'

// Global loading state (composable not available in interceptor context)
window.__apiLoading = window.__apiLoading || { count: 0 };

const getGlobalLoading = () => window.__apiLoading;

const envBaseUrl = import.meta.env.VITE_API_BASE_URL
const normalizedBaseUrl = typeof envBaseUrl === 'string' && envBaseUrl.trim()
  ? envBaseUrl.replace(/\/+$/, '')
  : '/api'

const api = axios.create({
  baseURL: normalizedBaseUrl,
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = getToken()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  // Global loading
  const loading = getGlobalLoading();
  loading.count++;
  loading.isLoading = loading.count > 0;

  return config
})

api.interceptors.response.use(
  (response) => {
    const loading = getGlobalLoading();
    loading.count = Math.max(0, loading.count - 1);
    loading.isLoading = loading.count > 0;
    return response
  },
  (error) => {
    const loading = getGlobalLoading();
    loading.count = Math.max(0, loading.count - 1);
    loading.isLoading = loading.count > 0;
    
    if (error.response?.status === 401) {
      clearToken()
      clearUser()
      clearUserRole()
    }

    return Promise.reject(error)
  }
)

export default api

// Student attendance history
export const fetchAttendanceHistory = async () => {
  try {
    const response = await api.get('/student/attendance/history')
    return response.data
  } catch (error) {
    console.error('Failed to fetch attendance history:', error)
    throw error
  }
}

// Global loading getter for components
export const getIsGlobalLoading = () => getGlobalLoading().isLoading;

