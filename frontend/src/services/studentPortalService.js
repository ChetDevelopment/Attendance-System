import api, {
  buildStudentDashboardSummary,
  normalizeDetailedAttendanceRecord,
} from './api'
import { clearCache, getDeduplicated, getOptimized, prefetch } from './apiOptimized'

const STUDENT_STATS_URL = '/student/dashboard/stats'
const STUDENT_HISTORY_URL = '/student/attendance/history'
const STUDENT_CHECKIN_URL = '/student/attendance/check-in'
const STUDENT_REQUEST_URL = '/student/attendance/request'
const BIOMETRIC_STATUS_URL = '/student/attendance/biometric-status'
const BIOMETRIC_HISTORY_URL = '/student/attendance/biometric-history'

const normalizeStatus = (value) => {
  const status = String(value || 'PENDING').toUpperCase()
  return ['PRESENT', 'ABSENT', 'LATE', 'PENDING'].includes(status) ? status : 'PENDING'
}

const normalizeHistoryRecord = (record) => {
  const normalized = normalizeDetailedAttendanceRecord(record)

  return {
    ...normalized,
    studentId: record?.studentId ? String(record.studentId) : '',
    instructor: record?.instructor || record?.teacher_name || 'N/A',
    status: normalizeStatus(record?.status ?? normalized.status),
  }
}

export const getStudentDashboardStats = async () => {
  const response = await api.get(STUDENT_STATS_URL)
  return response.data
}

export const getStudentHistory = async (limit = 50) => {
  const response = await api.get(STUDENT_HISTORY_URL, { params: { limit } })
  const data = response.data
  return Array.isArray(data) ? data.map(normalizeHistoryRecord) : []
}

export const getStudentPortalData = async () => {
  const [stats, history] = await Promise.all([
    getStudentDashboardStats(),
    getStudentHistory(),
  ])

  return {
    stats,
    history,
    summary: buildStudentDashboardSummary(stats, history),
  }
}

export const prefetchStudentPortalData = () => {
  prefetch(STUDENT_STATS_URL, { cacheTTL: 30000 })
  prefetch(STUDENT_HISTORY_URL, { cacheTTL: 60000 })
}

const invalidateStudentPortalCache = () => {
  clearCache(STUDENT_STATS_URL)
  clearCache(STUDENT_HISTORY_URL)
  clearCache(BIOMETRIC_STATUS_URL)
  clearCache(BIOMETRIC_HISTORY_URL)
}

export const submitStudentCheckIn = async (payload) => {
  const response = await api.post(STUDENT_CHECKIN_URL, payload)
  invalidateStudentPortalCache()
  return response.data
}

export const submitStudentManualRequest = async (payload) => {
  const response = await api.post(STUDENT_REQUEST_URL, payload)
  invalidateStudentPortalCache()
  return response.data
}

export const getBiometricStatus = () =>
  getOptimized(BIOMETRIC_STATUS_URL, {
    cache: true,
    cacheTTL: 10000,
  })

export const getBiometricHistory = () =>
  getOptimized(BIOMETRIC_HISTORY_URL, {
    cache: true,
    cacheTTL: 15000,
  })

export const validateBiometricScan = async (payload) => {
  const response = await api.post('/student/attendance/validate-biometric', payload)
  return response.data
}

export const scanCard = async (payload) => {
  const response = await api.post('/student/attendance/card-scan', payload)
  invalidateStudentPortalCache()
  return response.data
}

export const scanFingerprint = async (payload) => {
  const response = await api.post('/student/attendance/fingerprint-scan', payload)
  invalidateStudentPortalCache()
  return response.data
}

export const getStudentInfoAfterScan = async (payload) => {
  const response = await api.post('/student/attendance/student-info', payload)
  return response.data
}
