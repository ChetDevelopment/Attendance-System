import { clearToken, getToken } from './auth'
import type { AttendanceRecord, AttendanceStatus } from '../../types'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'

type RequestOptions = {
  method?: string
  body?: unknown
  headers?: Record<string, string>
}

const parseResponse = async (response: Response) => {
  const contentType = response.headers.get('content-type') || ''
  if (contentType.includes('application/json')) return response.json()
  return response.text()
}

const request = async (path: string, options: RequestOptions = {}) => {
  const { method = 'GET', body, headers = {} } = options
  const token = getToken()

  const requestHeaders: Record<string, string> = {
    Accept: 'application/json',
    ...headers,
  }

  if (token) requestHeaders.Authorization = `Bearer ${token}`

  let requestBody: BodyInit | undefined
  if (body !== undefined && body !== null) {
    if (body instanceof FormData) {
      requestBody = body
    } else {
      requestHeaders['Content-Type'] = 'application/json'
      requestBody = JSON.stringify(body)
    }
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    method,
    headers: requestHeaders,
    body: requestBody,
  })

  const data = await parseResponse(response)

  if (response.status === 401) {
    clearToken()
  }

  if (!response.ok) {
    const message = (data && typeof data === 'object' && 'message' in data)
      ? String((data as { message: string }).message)
      : `Request failed with status ${response.status}`
    const error = new Error(message) as Error & { response?: { status: number; data: unknown } }
    error.response = { status: response.status, data }
    throw error
  }

  return { data, status: response.status }
}

const api = {
  get: (path: string, config = {}) => request(path, { ...config, method: 'GET' }),
  post: (path: string, body?: unknown, config = {}) => request(path, { ...config, method: 'POST', body }),
  put: (path: string, body?: unknown, config = {}) => request(path, { ...config, method: 'PUT', body }),
  patch: (path: string, body?: unknown, config = {}) => request(path, { ...config, method: 'PATCH', body }),
  delete: (path: string, config = {}) => request(path, { ...config, method: 'DELETE' }),
}

export const submitAttendance = async (payload: {
  class_id: number
  attendance_date: string
  session_id: number
  records: Array<{ student_id: number; status: 'present' | 'absent' | 'late' }>
}) => {
  const { data } = await api.post('/attendances/submit', payload)
  return data
}

const toUiStatus = (status: string): AttendanceStatus => {
  const normalized = String(status || '').toLowerCase()
  if (normalized === 'late') return 'LATE'
  if (normalized === 'absent') return 'ABSENT'
  return 'PRESENT'
}

export const fetchAttendanceHistory = async (): Promise<AttendanceRecord[]> => {
  const { data } = await api.get('/attendances')
  const list = Array.isArray(data) ? data : []

  return list.map((item: any) => ({
    id: String(item.id ?? Date.now()),
    studentId: String(item.student_id ?? item.user_id ?? 'N/A'),
    courseName: 'General Session',
    instructor: 'System',
    date: item.date ?? new Date().toISOString().split('T')[0],
    timeSlot: item.check_in && item.check_out ? `${item.check_in} - ${item.check_out}` : 'N/A',
    status: toUiStatus(item.status),
    type: 'API',
  }))
}

export const checkIn = async (record: Partial<AttendanceRecord>) => {
  const payload = {
    date: new Date().toISOString().split('T')[0],
    status: 'present',
    notes: JSON.stringify(record),
  }
  const { data } = await api.post('/attendances', payload)
  return data
}

export const submitManualAttendanceRequest = async (payload: Record<string, unknown>) => {
  const body = {
    date: new Date().toISOString().split('T')[0],
    status: 'absent',
    notes: JSON.stringify(payload),
  }
  const { data } = await api.post('/attendances', body)
  return data
}

export default api
