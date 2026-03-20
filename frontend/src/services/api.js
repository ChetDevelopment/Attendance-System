import axios from 'axios'
import { clearAllAuthData, getStudentSession, getToken } from './auth'

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
  const studentSession = getStudentSession()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  if (studentSession) {
    config.headers['X-Student-Session'] = studentSession
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
      clearAllAuthData()
    }

    return Promise.reject(error)
  }
)

export default api

const formatTimeValue = (value) => {
  if (!value) return ''

  const date = new Date(value)
  if (!Number.isNaN(date.getTime())) {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }

  const raw = String(value)
  return raw.length >= 5 ? raw.slice(0, 5) : raw
}

const formatDateValue = (value) => {
  if (!value) return ''

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)

  return date.toLocaleDateString([], {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

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

export const fetchStudentDashboardStats = async () => {
  try {
    const response = await api.get('/student/dashboard/stats')
    return response.data
  } catch (error) {
    console.error('Failed to fetch student dashboard stats:', error)
    throw error
  }
}

export const checkIn = async (payload) => {
  try {
    const response = await api.post('/student/attendance/check-in', payload)
    return response.data
  } catch (error) {
    console.error('Failed to submit student check-in:', error)
    throw error.response?.data || error
  }
}

export const submitManualAttendanceRequest = async (payload) => {
  try {
    const response = await api.post('/student/attendance/request', payload)
    return response.data
  } catch (error) {
    console.error('Failed to submit manual attendance request:', error)
    throw error.response?.data || error
  }
}

export const buildStudentDashboardSummary = (stats, history = []) => {
  const recentRecords = Array.isArray(history) ? history.slice(0, 3) : []

  return {
    studentName: stats?.student?.fullname || stats?.student?.username || 'Student',
    monthlyPercentage: Number(stats?.monthlyPercentage || 0),
    presentCount: Number(stats?.presentCount || 0),
    lateCount: Number(stats?.lateCount || 0),
    absencesCount: Number(stats?.absencesCount || 0),
    totalSessions: Number(stats?.totalSessions || 0),
    targetPercentage: Number(stats?.targetPercentage || 75),
    currentSession: stats?.currentSession || null,
    todayAttendance: stats?.todayAttendance || null,
    recentRecords,
    recentTrendValues: recentRecords
      .slice()
      .reverse()
      .map((record) => {
        if (record.status === 'PRESENT') return 100
        if (record.status === 'LATE') return 70
        if (record.status === 'ABSENT') return 0
        return 50
      }),
    todayAttendanceLabel: stats?.todayAttendance
      ? `${stats.todayAttendance.course_name} at ${formatTimeValue(stats.todayAttendance.check_in_time)}`
      : 'No attendance recorded today',
  }
}

export const normalizeDetailedAttendanceRecord = (record) => {
  const status = String(record?.status || 'pending').toUpperCase()

  return {
    id: String(record?.id || ''),
    status: ['PRESENT', 'ABSENT', 'LATE', 'PENDING'].includes(status) ? status : 'PENDING',
    courseName: record?.course_name || 'Session',
    date: formatDateValue(record?.check_in_time),
    timeSlot: [formatTimeValue(record?.session_start), formatTimeValue(record?.session_end)]
      .filter(Boolean)
      .join(' - '),
    timestamp: record?.check_in_time || null,
  }
}

// Global loading getter for components
export const getIsGlobalLoading = () => getGlobalLoading().isLoading;

